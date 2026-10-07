<?php

namespace App\Policies;

use App\Entities\Production\Farm;
use App\Entities\Production\ProducerProfile;
use App\Enums\Role;
use App\Models\User;

class ProductionPolicy
{
    public function view(User $user, ProducerProfile|Farm $record): bool
    {
        $profile = $record instanceof Farm ? $record->producerProfile : $record;

        return $user->is_active && ($user->isAdmin() || ($user->role === Role::Producer && $profile->userId === $user->id));
    }

    public function update(User $user, ProducerProfile|Farm $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, ProducerProfile|Farm $record): bool
    {
        $profile = $record instanceof Farm ? $record->producerProfile : $record;

        return $user->is_active && $user->role === Role::Producer && $profile->userId === $user->id;
    }
}
