<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Development accounts may only be seeded in local or testing environments.');
        }

        foreach (Role::cases() as $role) {
            $email = ($role === Role::Laboratory ? 'lab' : $role->value).'@test.com';
            $user = User::firstOrNew(['email' => $email]);
            $user->name = $role->label().' Demo';
            $user->password = Hash::make('OliveTrace123!');
            $user->role = $role;
            $user->is_active = true;
            $user->email_verified_at = now();
            $user->save();
        }
    }
}
