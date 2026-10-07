<?php

namespace App\Policies\Distribution;

use App\Enums\Role;
use App\Models\Distribution\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active && $user->role === Role::Distributor;
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return $user->is_active && ($user->isAdmin() || $this->owns($user, $shipment));
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $this->view($user, $shipment);
    }

    public function cancel(User $user, Shipment $shipment): bool
    {
        return $user->is_active && ($user->isAdmin() || $this->owns($user, $shipment));
    }

    private function owns(User $user, Shipment $shipment): bool
    {
        return $user->role === Role::Distributor
            && $shipment->distributorProfile?->user_id === $user->id;
    }
}
