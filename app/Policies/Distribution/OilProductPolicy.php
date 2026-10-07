<?php

namespace App\Policies\Distribution;

use App\Enums\Role;
use App\Models\Distribution\OilProduct;
use App\Models\User;

class OilProductPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active && $user->role === Role::Producer;
    }

    public function view(User $user, OilProduct $product): bool
    {
        return $user->is_active && ($user->isAdmin() || $this->owns($user, $product));
    }

    public function update(User $user, OilProduct $product): bool
    {
        return $this->view($user, $product);
    }

    public function archive(User $user, OilProduct $product): bool
    {
        return $this->view($user, $product);
    }

    public function toggleVisibility(User $user, OilProduct $product): bool
    {
        return $user->is_active && $this->owns($user, $product);
    }

    public function forceHide(User $user, OilProduct $product): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    private function owns(User $user, OilProduct $product): bool
    {
        return $user->role === Role::Producer && $product->created_by_user_id === $user->id;
    }
}
