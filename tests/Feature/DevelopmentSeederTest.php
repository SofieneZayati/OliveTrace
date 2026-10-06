<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DevelopmentUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class DevelopmentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_seeder_creates_each_role_and_can_be_repeated(): void
    {
        $this->seed(DevelopmentUserSeeder::class);
        $this->seed(DevelopmentUserSeeder::class);
        $this->assertSame(6, User::count());
        foreach (Role::cases() as $role) {
            $user = User::where('role', $role->value)->sole();
            $this->assertTrue($user->is_active);
            $this->assertTrue(Hash::check('OliveTrace123!', $user->password));
        }
    }

    public function test_demo_accounts_cannot_be_seeded_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->expectException(LogicException::class);
        $this->seed(DevelopmentUserSeeder::class);
    }
}
