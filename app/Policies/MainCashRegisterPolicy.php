<?php

namespace App\Policies;

use App\Models\MainCashRegister;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MainCashRegisterPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MainCashRegister $mainCashRegister): bool
    {
        return false;
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
    public function update(User $user, MainCashRegister $mainCashRegister): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MainCashRegister $mainCashRegister): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MainCashRegister $mainCashRegister): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MainCashRegister $mainCashRegister): bool
    {
        return false;
    }
}
