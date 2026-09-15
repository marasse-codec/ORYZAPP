<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate(['name' => $permission->value]);
        }

        // Les rôles du diagramme de cas d'utilisation, chacun cantonné à son
        // étape du cycle de vie de la demande.
        $attributions = [
            RoleEnum::admin->value => PermissionEnum::cases(),
            RoleEnum::acteur->value => [PermissionEnum::creerDemande],
            RoleEnum::dp->value => [PermissionEnum::creerDemande, PermissionEnum::editDemande],
            RoleEnum::agentService->value => [PermissionEnum::instruireDemande, PermissionEnum::attribuerAppui],
            RoleEnum::agentCommission->value => [PermissionEnum::gererCommission, PermissionEnum::attribuerAppui],
            RoleEnum::gestionnaireStock->value => [PermissionEnum::gererStock],
        ];

        foreach ($attributions as $nomRole => $permissions) {
            Role::firstOrCreate(['name' => $nomRole])
                ->syncPermissions(array_map(fn (PermissionEnum $p) => $p->value, $permissions));
        }
    }
}
