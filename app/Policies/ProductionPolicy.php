<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\ProducerProfile;
use App\Models\User;

class ProductionPolicy
{
    public function view(User $user, ProducerProfile|Farm $record): bool
    {
        $profile = $record instanceof Farm ? $record->producerProfile : $record;

        return $user->is_active && ($user->isAdmin() || ($user->role === Role::Producer && $profile->user_id === $user->id));
    }

    public function update(User $user, ProducerProfile|Farm $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, ProducerProfile|Farm $record): bool
    {
        $profile = $record instanceof Farm ? $record->producerProfile : $record;

        return $user->is_active && $user->role === Role::Producer && $profile->user_id === $user->id;
    }
}
