<?php

namespace App\Domain\Acces\Services;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * La gestion fine des droits : l'administrateur choisit, rôle par rôle, quels
 * droits élémentaires il accorde. Chaque compte n'a qu'un rôle ; changer les
 * droits d'un rôle change donc ceux de tous les comptes qui l'ont.
 *
 * Le rôle administrateur n'est pas modifiable : il a tous les droits, sinon on
 * risquerait de ne plus pouvoir administrer du tout.
 */
class DroitService
{
    /** Les droits proposés à l'écran : valeur => libellé lisible. */
    public function catalogue(): array
    {
        return collect(PermissionEnum::cases())
            ->mapWithKeys(fn (PermissionEnum $p) => [$p->value => $p->libelle()])
            ->all();
    }

    public function estModifiable(Role $role): bool
    {
        return $role->name !== RoleEnum::admin->value;
    }

    /**
     * Remplace les droits d'un rôle et note au journal ceux qui sont ajoutés et retirés.
     *
     * @param  array<int, string>  $droits  valeurs de PermissionEnum
     */
    public function modifier(Role $role, array $droits): void
    {
        if (! $this->estModifiable($role)) {
            throw new RegleMetierViolee("Le rôle administrateur garde tous les droits : il n'est pas modifiable.");
        }

        // Seuls des droits connus sont acceptés.
        $droits = array_values(array_intersect($droits, array_keys($this->catalogue())));

        DB::transaction(function () use ($role, $droits) {
            $avant = $role->permissions->pluck('name')->all();

            $role->syncPermissions($droits);

            $ajoutes = array_values(array_diff($droits, $avant));
            $retires = array_values(array_diff($avant, $droits));

            if ($ajoutes !== [] || $retires !== []) {
                JournalAction::enregistrer(ActionJournalEnum::droitsModifies, $role, [
                    'role' => $role->name,
                    'ajoutes' => $ajoutes,
                    'retires' => $retires,
                ]);
            }
        });
    }
}
