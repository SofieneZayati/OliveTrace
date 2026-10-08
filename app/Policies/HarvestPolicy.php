<?php

namespace App\Policies;

use App\Enums\FarmStatus;
use App\Enums\HarvestStatus;
use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\Harvest;
use App\Models\User;

class HarvestPolicy
{
    public function create(User $user, Farm $farm): bool
    {
        return $user->is_active && $user->role === Role::Producer
            && $farm->producerProfile?->user_id === $user->id
            && $farm->status === FarmStatus::Active;
    }

    public function view(User $user, Harvest $harvest): bool
    {
        return $user->is_active && ($user->isAdmin() || $this->ownedBy($user, $harvest));
    }

    public function update(User $user, Harvest $harvest): bool
    {
        return $user->is_active && $harvest->status !== HarvestStatus::Milled && $this->ownedBy($user, $harvest);
    }

    public function delete(User $user, Harvest $harvest): bool
    {
        if (! $user->is_active || $harvest->millRequests()->exists()) {
            return false;
        }

        return $user->isAdmin() || ($user->role === Role::Producer && $this->ownedBy($user, $harvest));
    }

    private function ownedBy(User $user, Harvest $harvest): bool
    {
        return $user->role === Role::Producer
            && $harvest->farm?->producerProfile?->user_id === $user->id;
    }
}
