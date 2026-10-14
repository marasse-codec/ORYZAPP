<?php

namespace App\Domain\Acces\Services;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Domain\Stock\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Les comptes utilisateurs : créer, modifier, attribuer le rôle (un seul par
 * utilisateur). Le compte, son rôle, son agent de stock éventuel et la trace au
 * journal partent ensemble ou pas du tout.
 *
 * Un agent de service relève d'un service ; un gestionnaire de stock (comme
 * l'administrateur) signe des mouvements de stock : il lui faut un agent.
 */
class CompteService
{
    /** @param  array{name: string, email: string, password: string, role: string, id_service?: int|string|null}  $donnees */
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
            JournalAction::enregistrer(ActionJournalEnum::compteCree, $user, ['role' => $role->value]);

            return $user;
        });
    }

    /** Le mot de passe n'est changé que s'il est fourni. */
    public function modifier(User $user, array $donnees): User
    {
        return DB::transaction(function () use ($user, $donnees) {
            $role = RoleEnum::from($donnees['role']);

            $user->fill([
                'name' => $donnees['name'],
                'email' => $donnees['email'],
                'id_service' => $this->service($role, $donnees),
            ]);

            if (filled($donnees['password'] ?? null)) {
                $user->password = $donnees['password'];
            }

            $user->save();
            $this->attribuerRole($user, $role);
            JournalAction::enregistrer(ActionJournalEnum::compteModifie, $user, ['role' => $role->value]);

            return $user;
        });
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
