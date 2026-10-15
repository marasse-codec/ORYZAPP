<?php

namespace Database\Seeders;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Acces\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles et droits. Ce seeder repasse à chaque déploiement : il ne doit donc pas
 * écraser ce que l'administrateur a réglé lui-même dans « Rôles et droits ».
 *
 *  - un rôle qui n'existait pas reçoit ses droits par défaut (RoleEnum::droitsParDefaut) ;
 *  - un droit qui n'existait pas est donné aux rôles qui l'ont par défaut ;
 *  - le reste (les réglages de l'administrateur) n'est pas touché ;
 *  - l'administrateur reçoit toujours tous les droits ;
 *  - un rôle qui n'existe plus dans l'application (ex. l'ancien rôle « acteur »)
 *    est supprimé, s'il n'est plus porté par aucun compte.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $nouveauxDroits = [];

        foreach (PermissionEnum::cases() as $droit) {
            if (Permission::firstOrCreate(['name' => $droit->value])->wasRecentlyCreated) {
                $nouveauxDroits[] = $droit;
            }
        }

        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::firstOrCreate(['name' => $roleEnum->value]);
            $parDefaut = $roleEnum->droitsParDefaut();

            if ($roleEnum === RoleEnum::admin || $role->wasRecentlyCreated) {
                $role->syncPermissions(array_map(fn (PermissionEnum $d) => $d->value, $parDefaut));

                continue;
            }

            // Rôle existant : on ajoute seulement les droits apparus avec cette version.
            $aAjouter = array_filter($parDefaut, fn (PermissionEnum $d) => in_array($d, $nouveauxDroits, true));

            if ($aAjouter !== []) {
                $role->givePermissionTo(array_map(fn (PermissionEnum $d) => $d->value, $aAjouter));
            }
        }

        $rolesConnus = array_map(fn (RoleEnum $r) => $r->value, RoleEnum::cases());
        Role::whereNotIn('name', $rolesConnus)->whereDoesntHave('users')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
