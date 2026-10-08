<?php

namespace App\Policies;

use App\Enums\MillRequestStatus;
use App\Models\Production\OilLot;
use App\Models\User;

class OilLotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveProducer() || $user->isActiveAdmin();
    }

    public function view(User $user, OilLot $oilLot): bool
    {
        if ($user->isActiveAdmin()) {
            return true;
        }
        if ($user->isActiveMiller()) {
            return $oilLot->millRequest->mill?->user_id === $user->id;
        }

        return $oilLot->millRequest->harvest->farm->producerProfile->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isActiveMiller();
    }

    public function update(User $user, OilLot $oilLot): bool
    {
        if ($user->isActiveAdmin()) {
            return true;
        }

        return $oilLot->millRequest->mill?->user_id === $user->id && $oilLot->millRequest->status === MillRequestStatus::Completed;
    }

    public function delete(User $user, OilLot $oilLot): bool
    {
        return false;
    }
}
