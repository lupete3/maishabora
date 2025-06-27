<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function isAdmin(User $user): bool
    {
        return $user->isAdmin();
    }
    /**
     * Determine whether the user can view any models.
     */
    public function isComptable(User $user): bool
    {
        return $user->isAdmin() || $user->isComptable();
    }
    /**
     * Determine whether the user can view the model.
     */
    public function isCaissier(User $user): bool
    {
        return $user->isAdmin() || $user->isCaissier();
    }
    /**
     * Determine whether the user can view any models.
     */
    public function isRecouvreur(User $user): bool
    {
        return $user->isAdmin() || $user->isRecouvreur();
    }
    /**
     * Determine whether the user can view any models.
     */
    public function isReceptionniste(User $user): bool
    {
        return $user->isReceptionniste();
    }

    /**
     * Determine whether the user can create models.
     */
    public function isMembre(User $user): bool
    {
        return $user->isMembre();
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewMembers(User $user): bool
    {
        return $user->isAdmin() || $user->isReceptionniste() || $user->isRecouvreur() || $user->isCaissier();
    }
    /**
     * Determine whether the user can view any models.
     */
    public function depotMembers(User $user): bool
    {
        return $user->isRecouvreur() || $user->isCaissier();
    }
    /**
     * Determine whether the user can view any models.
     */
    public function transfertVersCaisse(User $user): bool
    {
        return $user->isRecouvreur() || $user->isCaissier() || $user->isReceptionniste();
    }
    /**
     * Determine whether the user can view any models.
     */
    public function retraitCaisseCentrale(User $user): bool
    {
        return $user->isCaissier();
    }
    /**
     * Determine whether the user can view any models.
     */
    public function octroitCredit(User $user): bool
    {
        return $user->isCaissier();
    }
    public function viewDashBoardAdmin(User $user): bool
    {
        return $user->isAdmin() || $user->isCaissier() || $user->isComptable();
    }

    public function sellMemberShipCard(User $user): bool
    {
        return $user->isCaissier() || $user->isRecouvreur() || $user->isReceptionniste();
    }

    public function simulationLoan(User $user): bool
    {
        return $user->isAdmin() || $user->isComptable() || $user->isCaissier() || $user->isRecouvreur() || $user->isReceptionniste();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
