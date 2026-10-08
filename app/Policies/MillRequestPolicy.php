<?php

namespace App\Policies;

use App\Enums\MillRequestStatus;
use App\Enums\Role;
use App\Models\Production\Harvest;
use App\Models\Production\MillRequest;
use App\Models\User;

class MillRequestPolicy
{
    public function create(User $user, Harvest $harvest): bool
    {
        return $user->is_active && $user->role === Role::Producer
            && $harvest->farm?->producerProfile?->user_id === $user->id;
    }

    public function view(User $user, MillRequest $request): bool
    {
        if (! $user->is_active) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        if ($request->isOwnedBy($user->id)) {
            return true;
        }

        return $request->mill?->user_id === $user->id;
    }

    public function cancel(User $user, MillRequest $request): bool
    {
        return $user->is_active && $user->role === Role::Producer
            && $request->isOwnedBy($user->id)
            && $request->status === MillRequestStatus::Pending;
    }

    public function update(User $user, MillRequest $request): bool
    {
        if (! $user->is_active) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $request->mill?->user_id === $user->id;
    }
}
