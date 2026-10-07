<?php

namespace App\Services\Production;

use App\Enums\FarmStatus;
use App\Enums\Role;
use App\Models\User;
use App\Repositories\Production\Farms;

class PublicOrigin
{
    public function __construct(private Farms $farms) {}

    /** Reusable by Aymen: null means the origin is not currently approved for public display. */
    public function forFarm(int $id): ?array
    {
        $farm = $this->farms->find($id);
        $profile = $farm->producerProfile;
        $user = User::find($profile->userId);
        if (! $farm->isPublic || $farm->status !== FarmStatus::Active || ! $profile->isPublic
            || ! $profile->isActive || ! $user?->is_active || $user->role !== Role::Producer) {
            return null;
        }

        return $farm->publicOrigin();
    }
}
