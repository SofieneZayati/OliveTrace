<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_cannot_choose_a_privileged_role(): void
    {
        $this->post('/register', [
            'name' => 'New consumer', 'email' => 'new@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
            'role' => 'admin', 'is_active' => false,
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'consumer', 'is_active' => true]);
    }

    public function test_inactive_accounts_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_an_existing_session_is_rejected_after_deactivation(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_each_role_can_access_the_shared_dashboard(): void
    {
        foreach (Role::cases() as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/dashboard')->assertOk();
        }
    }

    public function test_role_middleware_accepts_only_the_allowed_roles(): void
    {
        Route::middleware(['web', 'auth', 'active', 'role:producer,miller'])->get('/role-check', fn () => 'Allowed');

        foreach (Role::cases() as $role) {
            $response = $this->actingAs(User::factory()->create(['role' => $role]))->get('/role-check');
            in_array($role, [Role::Producer, Role::Miller], true) ? $response->assertOk() : $response->assertForbidden();
        }
    }
}
