<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LinkedAccountDeletionTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        // Generic test-only reference; no module table is added to the base.
        Schema::create('account_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('account_references');
        parent::tearDown();
    }

    private function linkedUser(): User
    {
        $user = User::factory()->create();
        DB::table('account_references')->insert(['user_id' => $user->id]);

        return $user;
    }

    public function test_admin_must_deactivate_a_linked_account_instead_of_deleting_it(): void
    {
        $user = $this->linkedUser();
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin)->delete(route('admin.users.destroy', $user))->assertSessionHasErrors('user');
        $this->assertModelExists($user);
        $this->assertDatabaseCount('account_references', 1);
        $this->patch(route('admin.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'consumer', 'is_active' => false,
        ])->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_linked_profile_deletion_keeps_the_account_and_session(): void
    {
        $user = $this->linkedUser();
        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');
        $this->assertAuthenticatedAs($user);
        $this->assertModelExists($user);
    }
}
