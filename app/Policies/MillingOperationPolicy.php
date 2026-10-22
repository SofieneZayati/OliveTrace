<?php

namespace App\Policies;

use App\Models\Production\MillingOperation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MillingOperationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return in_array($user->role->value, ['admin', 'miller', 'producer'], true);
    }

    public function view(User $user, MillingOperation $operation): bool
    {
        if ($user->role->value === 'admin') {
            return true;
        }
        if ($user->role->value === 'producer') {
            $producerUserId = $operation->millRequest?->harvest?->farm?->producerProfile?->user_id;

            return $producerUserId === $user->id;
        }
        if ($user->role->value === 'miller') {
            $millId = $user->mill?->id ?? $user->mill_id;

            return $millId && $operation->millRequest?->mill_id === $millId;
        }

        return false;
    }

    public function confirmDelivery(User $user, MillingOperation $operation): bool
    {
        if ($user->role->value !== 'producer') {
            return false;
        }
        $producerUserId = $operation->millRequest?->harvest?->farm?->producerProfile?->user_id;

        return $producerUserId === $user->id && $operation->status->value === 'scheduled' && ! $operation->producer_confirmed_at;
    }

    public function confirmReception(User $user, MillingOperation $operation): bool
    {
        if ($user->role->value !== 'miller') {
            return false;
        }
        $millId = $user->mill?->id ?? $user->mill_id;

        return $millId && $operation->millRequest?->mill_id === $millId && $operation->status->value === 'scheduled';
    }

    public function acknowledgeDiscrepancy(User $user, MillingOperation $operation): bool
    {
        if ($user->role->value !== 'producer') {
            return false;
        }
        $producerUserId = $operation->millRequest?->harvest?->farm?->producerProfile?->user_id;

        return $producerUserId === $user->id && $operation->miller_confirmed_at && (float) $operation->discrepancy_percent > 10.0 && ! $operation->discrepancy_acknowledged_at;
    }

    public function start(User $user, MillingOperation $operation): bool
    {
        if ($user->role->value !== 'miller') {
            return false;
        }
        $millId = $user->mill?->id ?? $user->mill_id;

        return $millId && $operation->millRequest?->mill_id === $millId && $operation->status->value === 'scheduled';
    }

    public function complete(User $user, MillingOperation $operation): bool
    {
        if ($user->role->value !== 'miller') {
            return false;
        }
        $millId = $user->mill?->id ?? $user->mill_id;

        return $millId && $operation->millRequest?->mill_id === $millId && $operation->status->value === 'in_progress';
    }

    public function cancel(User $user, MillingOperation $operation): bool
    {
        if ($user->role->value === 'admin') {
            return $operation->status->value !== 'completed';
        }
        if ($user->role->value === 'miller') {
            $millId = $user->mill?->id ?? $user->mill_id;

            return $millId && $operation->millRequest?->mill_id === $millId && in_array($operation->status->value, ['scheduled', 'in_progress'], true);
        }
        if ($user->role->value === 'producer') {
            $producerUserId = $operation->millRequest?->harvest?->farm?->producerProfile?->user_id;

            return $producerUserId === $user->id && $operation->status->value === 'scheduled';
        }

        return false;
    }
}
