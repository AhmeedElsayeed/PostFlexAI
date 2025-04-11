<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Offer;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfferPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any offers
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return true; // All authenticated users can view offers
    }

    /**
     * Determine whether the user can view the offer
     *
     * @param User $user
     * @param Offer $offer
     * @return bool
     */
    public function view(User $user, Offer $offer)
    {
        return $user->current_team_id === $offer->team_id;
    }

    /**
     * Determine whether the user can create offers
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->hasRole(['admin', 'marketer']);
    }

    /**
     * Determine whether the user can update the offer
     *
     * @param User $user
     * @param Offer $offer
     * @return bool
     */
    public function update(User $user, Offer $offer)
    {
        if ($user->current_team_id !== $offer->team_id) {
            return false;
        }

        return $user->hasRole(['admin', 'marketer']);
    }

    /**
     * Determine whether the user can delete the offer
     *
     * @param User $user
     * @param Offer $offer
     * @return bool
     */
    public function delete(User $user, Offer $offer)
    {
        if ($user->current_team_id !== $offer->team_id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can generate coupons for the offer
     *
     * @param User $user
     * @param Offer $offer
     * @return bool
     */
    public function generateCoupons(User $user, Offer $offer)
    {
        if ($user->current_team_id !== $offer->team_id) {
            return false;
        }

        return $user->hasRole(['admin', 'marketer']);
    }

    /**
     * Determine whether the user can assign coupons to clients
     *
     * @param User $user
     * @param Offer $offer
     * @return bool
     */
    public function assignToClients(User $user, Offer $offer)
    {
        if ($user->current_team_id !== $offer->team_id) {
            return false;
        }

        return $user->hasRole(['admin', 'marketer']);
    }

    /**
     * Determine whether the user can view offer statistics
     *
     * @param User $user
     * @return bool
     */
    public function viewStats(User $user)
    {
        return true; // All authenticated users can view stats
    }

    /**
     * Determine whether the user can auto-generate offers
     *
     * @param User $user
     * @return bool
     */
    public function autoGenerate(User $user)
    {
        return $user->hasRole(['admin', 'marketer']);
    }
} 