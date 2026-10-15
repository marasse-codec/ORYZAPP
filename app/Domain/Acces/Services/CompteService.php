<?php

namespace App\Domain\Acces\Services;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Journal\Support\Ecarts;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Domain\Stock\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Les comptes utilisateurs : créer, modifier, attribuer le rôle (un seul par
 * utilisateur), désactiver et réactiver. Le compte, son rôle, son agent de stock éventuel et la trace au
 * journal partent ensemble ou pas du tout.
 *
 * Un agent de service relève d'un service ; un délégué de pôle est responsable de
 * départements ; un gestionnaire de stock (comme l'administrateur) signe des
 * mouvements de stock : il lui faut un agent.
 */
class CompteService
{
    /** @param  array{name: string, email: string, password: string, role: string, id_service?: int|string|null, departements?: array<int, int|string>}  $donnees */
    public function creer(array $donnees): User
    {
        return DB::transaction(function () use ($donnees) {
            $role = RoleEnum::from($donnees['role']);

            $user = User::create([
                'name' => $donnees['name'],
                'email' => $donnees['email'],
                'password' => $donnees['password'],
                'id_service' => $this->service($role, $donnees),
            ]);

            $this->attribuerRole($user, $role);
            $this->rattacherDepartements($user, $role, $donnees);
            JournalAction::enregistrer(ActionJournalEnum::compteCree, $user, ['role' => $role->value]);

            return $user;
        });
    }

    /** Le mot de passe n'est changé que s'il est fourni. */
    public function modifier(User $user, array $donnees): User
    {
        return DB::transaction(function () use ($user, $donnees) {
            $role = RoleEnum::from($donnees['role']);

            $ancienRole = $user->roleEnum();

            $user->fill([
                'name' => $donnees['name'],
                'email' => $donnees['email'],
                'id_service' => $this->service($role, $donnees),
            ]);

            // Ce qui change, noté avant l'enregistrement (le mot de passe n'est jamais écrit au journal).
            $ecarts = Ecarts::duModele($user);

            if (filled($donnees['password'] ?? null)) {
                $user->password = $donnees['password'];
                $ecarts['mot_de_passe'] = ['avant' => '***', 'apres' => 'modifié'];
            }

            if ($ancienRole !== $role) {
                $ecarts['role'] = ['avant' => $ancienRole?->value, 'apres' => $role->value];
            }

            $user->save();
            $this->attribuerRole($user, $role);

            $departementsAvant = $user->departements()->pluck('departements.id_departement')->sort()->values()->all();
            $this->rattacherDepartements($user, $role, $donnees);
            $departementsApres = $user->departements()->pluck('departements.id_departement')->sort()->values()->all();

            if ($departementsAvant !== $departementsApres) {
                $ecarts['departements'] = ['avant' => $departementsAvant, 'apres' => $departementsApres];
            }

            JournalAction::enregistrer(ActionJournalEnum::compteModifie, $user, $ecarts);

            return $user;
        });
    }

    /**
     * Désactive un compte sans le supprimer : la personne ne peut plus se connecter,
     * mais tout ce qu'elle a fait reste à son nom. On ne se désactive pas soi-même,
     * et on ne désactive pas le dernier administrateur actif (plus personne ne
     * pourrait administrer).
     */
    public function desactiver(User $user, ?string $motif = null): void
    {
        DB::transaction(function () use ($user, $motif) {
            if ($user->is(Auth::user())) {
                throw new RegleMetierViolee('Vous ne pouvez pas désactiver votre propre compte.');
            }

            if ($user->hasRole(RoleEnum::admin->value)
                && User::role(RoleEnum::admin->value)->actifs()->whereKeyNot($user->id)->doesntExist()) {
                throw new RegleMetierViolee("C'est le dernier administrateur actif : il ne peut pas être désactivé.");
            }

            $user->update(['actif' => false, 'desactive_le' => now(), 'motif_desactivation' => $motif]);

            // Les sessions ouvertes sont fermées : la désactivation prend effet tout de suite.
            DB::table('sessions')->where('user_id', $user->id)->delete();

            JournalAction::enregistrer(ActionJournalEnum::compteDesactive, $user, ['motif' => $motif]);
        });
    }

    public function reactiver(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->update(['actif' => true, 'desactive_le' => null, 'motif_desactivation' => null]);

            JournalAction::enregistrer(ActionJournalEnum::compteReactive, $user);
        });
    }

    /**
     * Un délégué de pôle est responsable d'au moins un département : il voit les demandes
     * de ces départements et reçoit leurs notifications. Les autres rôles n'en ont pas.
     */
    private function rattacherDepartements(User $user, RoleEnum $role, array $donnees): void
    {
        if ($role !== RoleEnum::dp) {
            $user->departements()->detach();

            return;
        }

        $departements = array_filter((array) ($donnees['departements'] ?? []));

        if ($departements === []) {
            throw new RegleMetierViolee('Un délégué de pôle doit être responsable d\'au moins un département.');
        }

        $user->departements()->sync($departements);
    }

    /** Seul l'agent de service est rattaché à un service ; pour lui, c'est obligatoire. */
    private function service(RoleEnum $role, array $donnees): ?int
    {
        if ($role !== RoleEnum::agentService) {
            return null;
        }

        if (blank($donnees['id_service'] ?? null)) {
            throw new RegleMetierViolee('Un agent de service doit être rattaché à un service.');
        }

        return (int) $donnees['id_service'];
    }

    private function attribuerRole(User $user, RoleEnum $role): void
    {
        $user->syncRoles([$role->value]);

        if (in_array($role, [RoleEnum::gestionnaireStock, RoleEnum::admin], true)) {
            Agent::firstOrCreate(
                ['user_id' => $user->id],
                ['nom' => $user->name, 'matricule_agent' => 'AGENT-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT), 'actif' => true],
            );
        }
    }
}
