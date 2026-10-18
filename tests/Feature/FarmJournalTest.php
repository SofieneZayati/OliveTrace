<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\FarmActivity;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use App\Repositories\Production\Farms;
use App\Services\Production\FarmJournal;
use App\Services\Production\PublicOrigin;
use Database\Seeders\DevelopmentUserSeeder;
use Database\Seeders\FarmJournalDemoSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FarmJournalTest extends TestCase
{
    use RefreshDatabase;

    private Farm $farm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 18)->startOfDay());
        $producer = User::factory()->create(['role' => Role::Producer]);
        $profile = ProducerProfile::factory()->for($producer)->create(['is_public' => true]);
        $this->farm = Farm::factory()->for($profile, 'producerProfile')->create(['is_public' => true]);
        $this->actingAs($producer);
    }

    private function data(array $overrides = []): array
    {
        return array_replace(['title' => 'Check drip lines', 'type' => 'irrigation', 'planned_on' => '2026-10-17', 'notes' => 'Private journal notes'], $overrides);
    }

    private function entry(array $overrides = []): FarmActivity
    {
        return $this->farm->activities()->create($this->data($overrides));
    }

    public function test_owner_can_plan_edit_and_complete_irrigation_and_view_recorded_totals(): void
    {
        $this->get(route('producer.farms.activities.create', $this->farm))->assertOk();
        $this->post(route('producer.farms.activities.store', $this->farm), $this->data())->assertSessionHasNoErrors()->assertRedirect();
        $entry = $this->farm->activities()->sole();
        $this->get(route('producer.farms.activities.edit', [$this->farm, $entry]))->assertOk();
        $this->patch(route('producer.farms.activities.update', [$this->farm, $entry]), $this->data(['title' => 'North parcel irrigation']))->assertSessionHasNoErrors();
        $this->get(route('producer.farms.activities.index', [$this->farm, 'status' => 'overdue']))->assertOk()->assertSee('North parcel irrigation')->assertSee('Overdue');
        $this->patch(route('producer.farms.activities.status', [$this->farm, $entry]), ['status' => 'completed', 'completed_on' => '2026-10-18', 'water_m3' => '12.50'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('farm_activities', ['id' => $entry->id, 'status' => 'completed', 'water_m3' => '12.50']);
        $this->get(route('producer.farms.activities.index', $this->farm))->assertOk()->assertSee('12.50 m³')->assertSee('producer declaration');
        $this->get(route('producer.farms.activities.index', [$this->farm, 'status' => 'overdue']))->assertDontSee('North parcel irrigation');
    }

    public function test_conditional_validation_rejects_invalid_dates_water_and_injected_ownership(): void
    {
        foreach ([['type' => 'harvest'], ['planned_on' => 'not-a-date'], ['planned_on' => '2030-01-01'], ['farm_id' => 999], ['status' => 'completed']] as $invalid) {
            $this->post(route('producer.farms.activities.store', $this->farm), $this->data($invalid))->assertSessionHasErrors(array_key_first($invalid));
        }
        $this->assertDatabaseCount('farm_activities', 0);
        $entry = $this->entry();
        foreach ([[], ['water_m3' => 0], ['water_m3' => -5], ['water_m3' => '1.234'], ['completed_on' => '2026-10-16', 'water_m3' => 10], ['completed_on' => '2026-10-19', 'water_m3' => 10]] as $invalid) {
            $this->patch(route('producer.farms.activities.status', [$this->farm, $entry]), array_replace(['status' => 'completed', 'completed_on' => '2026-10-18'], $invalid))->assertSessionHasErrors();
        }
        $this->assertDatabaseHas('farm_activities', ['id' => $entry->id, 'status' => 'planned', 'water_m3' => null]);
        $inspection = $this->entry(['type' => 'inspection']);
        $this->patch(route('producer.farms.activities.status', [$this->farm, $inspection]), ['status' => 'completed', 'completed_on' => '2026-10-18', 'water_m3' => 5])->assertSessionHasErrors('water_m3');
        $this->patch(route('producer.farms.activities.status', [$this->farm, $inspection]), ['status' => 'completed', 'completed_on' => '2026-10-18'])->assertSessionHasNoErrors();
    }

    public function test_terminal_entries_are_immutable_and_cancellation_does_not_count_water(): void
    {
        $entry = $this->entry();
        $this->patch(route('producer.farms.activities.status', [$this->farm, $entry]), ['status' => 'cancelled'])->assertSessionHasNoErrors();
        $this->patch(route('producer.farms.activities.update', [$this->farm, $entry]), $this->data(['title' => 'Rewrite history']))->assertSessionHasErrors('activity');
        $this->patch(route('producer.farms.activities.status', [$this->farm, $entry]), ['status' => 'completed', 'completed_on' => '2026-10-18', 'water_m3' => 9])->assertSessionHasErrors('activity');
        $this->get(route('producer.farms.activities.index', $this->farm))->assertSee('0.00 m³');
    }

    public function test_private_journal_is_scoped_to_owner_farm_and_admin_read_only(): void
    {
        $entry = $this->entry();
        $otherFarm = Farm::factory()->for($this->farm->producerProfile, 'producerProfile')->create();
        $this->get(route('producer.farms.activities.edit', [$otherFarm, $entry]))->assertNotFound();
        $otherUser = User::factory()->create(['role' => Role::Producer]);
        $this->actingAs($otherUser)->get(route('producer.farms.activities.index', $this->farm))->assertForbidden();
        $this->post(route('producer.farms.activities.store', $this->farm), $this->data())->assertForbidden();
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin)->get(route('admin.farms.activities.index', $this->farm))->assertOk()->assertSee('Private journal notes')->assertSee('read-only');
        $this->post(route('producer.farms.activities.store', $this->farm), $this->data())->assertForbidden();
        $consumer = User::factory()->create(['role' => Role::Consumer]);
        $this->actingAs($consumer)->get(route('producer.farms.activities.index', $this->farm))->assertForbidden();
    }

    public function test_archived_and_disabled_farms_or_profiles_keep_history_but_block_writes(): void
    {
        $entry = $this->entry();
        foreach (['archived', 'disabled'] as $status) {
            $this->farm->forceFill(['status' => $status])->save();
            $this->get(route('producer.farms.activities.index', $this->farm))->assertOk()->assertSee('read-only');
            $this->post(route('producer.farms.activities.store', $this->farm), $this->data())->assertSessionHasErrors('farm');
            $this->patch(route('producer.farms.activities.status', [$this->farm, $entry]), ['status' => 'cancelled'])->assertSessionHasErrors('farm');
        }
        $this->farm->forceFill(['status' => 'active'])->save();
        $this->farm->producerProfile->forceFill(['is_active' => false])->save();
        $this->post(route('producer.farms.activities.store', $this->farm), $this->data())->assertSessionHasErrors('farm');
    }

    public function test_activity_history_preserves_farm_and_public_origin_contract_is_unchanged(): void
    {
        $origin = app(PublicOrigin::class)->forFarm($this->farm->id);
        $this->entry();
        $this->assertSame($origin, app(PublicOrigin::class)->forFarm($this->farm->id));
        $this->assertSame([$this->farm->id], array_column(app(Farms::class)->selectableForUser(auth()->id()), 'id'));
        $this->get(route('origin.farms.show', $this->farm))->assertDontSee('Private journal notes');
        $this->delete(route('producer.farms.destroy', $this->farm))->assertRedirect();
        $this->assertDatabaseHas('farms', ['id' => $this->farm->id, 'status' => 'archived']);
        $this->assertDatabaseCount('farm_activities', 1);
    }

    public function test_optional_demo_seeder_is_repeatable_and_preserves_edits(): void
    {
        $this->seed([DevelopmentUserSeeder::class, ProductionSeeder::class, FarmJournalDemoSeeder::class]);
        $entry = FarmActivity::where('title', 'Demo: review soil cover')->sole();
        $entry->forceFill(['notes' => 'Keep my edit', 'status' => 'cancelled'])->save();
        $this->seed(FarmJournalDemoSeeder::class);
        $this->assertDatabaseCount('farm_activities', 2);
        $this->assertSame('Keep my edit', $entry->fresh()->notes);
        $this->assertSame('cancelled', $entry->fresh()->status->value);
        $this->assertSame('planned', FarmActivity::factory()->for($this->farm)->create()->status->value);
    }

    public function test_stale_farm_object_cannot_allow_work_after_archival(): void
    {
        $stale = $this->farm->fresh();
        $this->farm->forceFill(['status' => 'archived'])->save();
        try {
            app(FarmJournal::class)->create(auth()->user(), $stale, $this->data());
            $this->fail('Archived farm accepted work through a stale object.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('farm', $exception->errors());
        }
        $this->assertDatabaseCount('farm_activities', 0);
    }
}
