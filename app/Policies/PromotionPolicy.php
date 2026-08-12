<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $user->isAdmin();
    }
}
