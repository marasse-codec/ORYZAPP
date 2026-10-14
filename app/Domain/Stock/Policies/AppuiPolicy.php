<?php

namespace App\Domain\Stock\Policies;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Stock\Models\Appui;
use App\Models\User;

class AppuiPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Appui $appui): bool
    {
        return $user->hasPermissionTo(PermissionEnum::gererStock->value);    

    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Appui $appui): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Appui $appui): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Appui $appui): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Appui $appui): bool
    {
        return false;
    }
}
