<?php

namespace Tests\Feature;

use App\Models\Production\Farm;
use App\Models\Production\ProducerProfile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LinkedProductionDeletionTest extends TestCase
{
    // These tests create a generic downstream table; MySQL DDL needs migration isolation.
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('production_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_profile_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('farm_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('production_references');
        parent::tearDown();
    }

    public function test_linked_profile_and_its_private_logo_survive_a_rejected_delete(): void
    {
        Storage::fake('local');
        $profile = ProducerProfile::factory()->create(['logo_path' => 'producer-logos/linked.png']);
        Storage::disk('local')->put($profile->logo_path, 'test-only-image');
        DB::table('production_references')->insert(['producer_profile_id' => $profile->id]);
        $this->actingAs($profile->user)->delete(route('producer.profile.destroy'))->assertSessionHasErrors('profile');
        $this->assertModelExists($profile);
        $this->assertDatabaseCount('production_references', 1);
        Storage::disk('local')->assertExists($profile->logo_path);
    }

    public function test_linked_farm_cannot_be_deleted_and_can_be_archived_without_losing_history(): void
    {
        $farm = Farm::factory()->create();
        DB::table('production_references')->insert(['farm_id' => $farm->id]);
        $this->actingAs($farm->producerProfile->user)->delete(route('producer.farms.destroy', $farm->id))->assertSessionHasErrors('farm');
        $this->assertDatabaseHas('farms', ['id' => $farm->id, 'status' => 'active']);
        $this->patch(route('producer.farms.archive', $farm->id))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('farms', ['id' => $farm->id, 'status' => 'archived']);
        $this->assertDatabaseHas('production_references', ['farm_id' => $farm->id]);
    }
}
