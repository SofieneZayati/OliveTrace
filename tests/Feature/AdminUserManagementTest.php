<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Services\UserAdministration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    private function attributes(User $user, array $overrides = []): array
    {
        return array_merge(['name' => $user->name, 'email' => $user->email, 'role' => $user->role->value, 'is_active' => true], $overrides);
    }

    public function test_guests_are_redirected_and_non_admins_cannot_manage_users(): void
    {
        $target = User::factory()->create();
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        foreach (Role::cases() as $role) {
            if ($role === Role::Admin) {
                continue;
            }
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('admin.dashboard'))->assertForbidden();
            $this->get(route('admin.users.index'))->assertForbidden();
            $this->get(route('admin.users.show', $target))->assertForbidden();
            $this->get(route('admin.users.edit', $target))->assertForbidden();
            $this->patch(route('admin.users.update', $target), $this->attributes($target))->assertForbidden();
            $this->delete(route('admin.users.destroy', $target))->assertForbidden();
        }
        $this->assertModelExists($target);
    }

    public function test_admin_can_view_search_and_filter_accounts(): void
    {
        $admin = $this->admin();
        $producer = User::factory()->create(['name' => 'Test Producer', 'role' => Role::Producer]);
        $consumer = User::factory()->create(['name' => 'Inactive Consumer', 'is_active' => false]);
        $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Test Producer', 'role' => 'producer', 'active' => '1']))
            ->assertOk()->assertSee($producer->email)->assertDontSee($consumer->email);
        $this->get(route('admin.users.index', ['active' => '0']))->assertOk()->assertSee($consumer->email)->assertDontSee($producer->email);
        $this->get(route('admin.users.show', $producer))->assertOk()->assertSee('Producer');
        $this->get(route('admin.users.edit', $producer))->assertOk();
        $this->get(route('admin.users.show', 999999))->assertNotFound();
    }

    public function test_admin_can_change_details_role_and_active_status(): void
    {
        $target = User::factory()->create();
        $this->actingAs($this->admin())->patch(route('admin.users.update', $target), $this->attributes($target, [
            'name' => 'Updated name', 'email' => 'updated@example.com', 'role' => 'laboratory', 'is_active' => false,
        ]))->assertRedirect(route('admin.users.show', $target));
        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Updated name', 'email' => 'updated@example.com', 'role' => 'laboratory', 'is_active' => false, 'email_verified_at' => null]);
        $this->patch(route('admin.users.update', $target), $this->attributes($target->fresh(), ['is_active' => true]))->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_invalid_updates_are_rejected(): void
    {
        $target = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($this->admin())->patch(route('admin.users.update', $target), $this->attributes($target, [
            'name' => '', 'email' => $other->email, 'role' => 'super-admin', 'is_active' => 'invalid',
        ]))->assertSessionHasErrors(['name', 'email', 'role', 'is_active']);
    }

    public function test_admin_can_delete_an_unlinked_account(): void
    {
        $target = User::factory()->create();
        $this->actingAs($this->admin())->delete(route('admin.users.destroy', $target))->assertRedirect(route('admin.users.index'));
        $this->assertModelMissing($target);
    }

    public function test_admin_cannot_remove_their_own_access_or_account(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->patch(route('admin.users.update', $admin), $this->attributes($admin, ['role' => 'consumer']))->assertSessionHasErrors('role');
        $this->patch(route('admin.users.update', $admin), $this->attributes($admin, ['is_active' => false]))->assertSessionHasErrors('role');
        $this->delete(route('admin.users.destroy', $admin))->assertSessionHasErrors('user');
        $this->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrorsIn('userDeletion', 'password');
        $this->assertModelExists($admin);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_final_active_admin_is_protected_by_the_service(): void
    {
        $target = $this->admin();
        $actor = User::factory()->create(['role' => Role::Admin, 'is_active' => false]);
        $this->expectException(ValidationException::class);
        app(UserAdministration::class)->delete($actor, $target);
    }

    public function test_profile_cannot_change_role_or_active_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'admin', 'is_active' => false,
        ])->assertSessionHasNoErrors();
        $this->assertSame(Role::Consumer, $user->fresh()->role);
        $this->assertTrue($user->fresh()->is_active);
    }
}
