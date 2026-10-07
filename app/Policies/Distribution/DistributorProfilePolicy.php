<?php

namespace App\Policies\Distribution;

use App\Enums\Role;
use App\Models\Distribution\DistributorProfile;
use App\Models\User;

class DistributorProfilePolicy
{
    public function view(User $user, DistributorProfile $profile): bool
    {
        return $user->is_active
            && ($user->isAdmin() || ($user->role === Role::Distributor && $profile->user_id === $user->id));
    }

    public function update(User $user, DistributorProfile $profile): bool
    {
        return $user->is_active
            && $user->role === Role::Distributor
            && $profile->user_id === $user->id;
    }
}
