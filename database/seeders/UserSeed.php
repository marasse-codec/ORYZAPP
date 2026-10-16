<?php

namespace Database\Seeders;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Demande\Models\Acteur;
use App\Domain\Referentiel\Models\Departement;
use App\Domain\Referentiel\Models\Service;
use App\Domain\Stock\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeed extends Seeder
{
    public function run(): void
    {
        // Un compte par rôle, tous avec le mot de passe « password ».
        $comptes = [
            ['Admin', 'admin@example.com', RoleEnum::admin, null],
            ['Délégué de pôle', 'dp@example.com', RoleEnum::dp, null],
            ['Agent de commission', 'agent.commission@example.com', RoleEnum::agentCommission, null],
            ['Gestionnaire de stock', 'stock@example.com', RoleEnum::gestionnaireStock, null],
            ['Directeur', 'directeur@example.com', RoleEnum::directeur, null],
            ['Comptable', 'comptable@example.com', RoleEnum::comptable, null],
        ];

        // Un agent instructeur par service : chacun n'instruit que les appuis
        // que son service fournit.
        foreach (Service::orderBy('id_service')->get() as $index => $service) {
            $comptes[] = [
                'Agent '.$service->lib_service,
                'agent.service'.($index + 1).'@example.com',
                RoleEnum::agentService,
                $service->id_service,
            ];
        }

        foreach ($comptes as [$nom, $email, $role, $idService]) {
            User::create([
                'name' => $nom,
                'email' => $email,
                'password' => bcrypt('password'),
                'id_service' => $idService,
            ])->assignRole($role->value);
        }

        // Un demandeur d'exemple. Les acteurs de la filière n'ont pas de compte :
        // le délégué de pôle saisit leurs demandes.
        Acteur::create([
            'nom' => 'Degny Alfred',
            'date_naissance' => '2003-06-25',
            'telephone' => '0758221948',
            'email' => 'freddegny@gmail.com',
        ]);

        // Le DP de démonstration couvre tous les départements.
        User::where('email', 'dp@example.com')->first()?->departements()->sync(Departement::pluck('id_departement'));

        Agent::create([
            'nom' => 'Sam Stock',
            'user_id' => User::where('email', 'stock@example.com')->value('id'),
            'matricule_agent' => 'AGENT-STOCK-001',
        ]);

        Agent::create([
            'nom' => 'Jean Admin',
            'user_id' => User::where('email', 'admin@example.com')->value('id'),
            'matricule_agent' => 'AGENT-ADMIN-001',
        ]);
    }
}
