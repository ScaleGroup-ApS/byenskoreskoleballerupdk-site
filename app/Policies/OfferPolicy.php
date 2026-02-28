<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;

class OfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function view(User $user, Offer $offer): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Offer $offer): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Offer $offer): bool
    {
        return $user->isAdmin();
    }
}
