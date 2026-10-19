<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\FarmActivity;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use Database\Seeders\FarmCareDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use LogicException;
use Tests\TestCase;

class FarmCareDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private function profile(): ProducerProfile
    {
        $owner = User::factory()->create(['role' => Role::Producer, 'email' => 'producer@test.com', 'name' => 'Keep my name']);

        return ProducerProfile::factory()->for($owner)->create();
    }

    public function test_demo_data_covers_map_points_journal_states_and_water_without_fake_ai(): void
    {
        Http::preventStrayRequests();
        $profile = $this->profile();
        $this->seed(FarmCareDemoSeeder::class);
        $this->assertSame(4, $profile->farms()->count());
        $this->assertDatabaseCount('farm_activities', 16);
        $this->assertSame(4, $profile->farms()->whereNotNull('gps_lat')->whereNotNull('gps_lng')->count());
        $this->assertSame(0, $profile->farms()->where('is_public', true)->count());
        $sfax = $profile->farms()->where('name', '[Demo] Sfax Coastal Grove')->sole();
        $this->assertSame(3, $sfax->activities()->where('status', 'planned')->count());
        $this->assertSame(2, $sfax->activities()->where('status', 'completed')->count());
        $this->assertSame(1, $sfax->activities()->where('status', 'cancelled')->count());
        $this->assertSame(1, $sfax->activities()->get()->filter->isOverdue()->count());
        $this->assertSame(24.5, (float) $sfax->activities()->where('status', 'completed')->sum('water_m3'));
        $this->assertSame(1, $profile->farms()->where('status', 'archived')->count());
        $this->assertSame(1, $profile->farms()->where('status', 'disabled')->count());
        $this->assertDatabaseCount('farm_advice_reports', 0);
        Http::assertNothingSent();
    }

    public function test_rerunning_preserves_existing_records_and_edits_to_demo_data(): void
    {
        $profile = $this->profile();
        $farm = Farm::factory()->for($profile, 'producerProfile')->create(['name' => 'My existing farm', 'gps_lat' => '34.1234567', 'gps_lng' => '10.7654321']);
        $entry = FarmActivity::factory()->for($farm)->create(['notes' => 'Keep my existing record']);
        $original = [$profile->user->fresh()->getRawOriginal(), $profile->fresh()->getRawOriginal(), $farm->fresh()->getRawOriginal(), $entry->fresh()->getRawOriginal()];
        $this->seed(FarmCareDemoSeeder::class);
        $demo = $profile->farms()->where('name', '[Demo] Sfax Coastal Grove')->sole();
        $demo->fill(['area_ha' => '20.00', 'gps_lat' => '34.9876543'])->save();
        $demoEntry = $demo->activities()->where('title', 'Demo: overdue follow-up inspection')->sole();
        $demoEntry->forceFill(['notes' => 'Keep my demo edit', 'status' => 'cancelled'])->save();
        $this->seed(FarmCareDemoSeeder::class);
        $this->assertDatabaseCount('farms', 5);
        $this->assertDatabaseCount('farm_activities', 17);
        $this->assertSame($original, [$profile->user->fresh()->getRawOriginal(), $profile->fresh()->getRawOriginal(), $farm->fresh()->getRawOriginal(), $entry->fresh()->getRawOriginal()]);
        $this->assertSame('20.00', $demo->fresh()->area_ha);
        $this->assertSame('34.9876543', $demo->fresh()->gps_lat);
        $this->assertSame('Keep my demo edit', $demoEntry->fresh()->notes);
        $this->assertSame('cancelled', $demoEntry->fresh()->status->value);
    }

    public function test_demo_seed_is_rejected_outside_development_and_testing(): void
    {
        $this->app['env'] = 'production';
        $this->expectException(LogicException::class);
        $this->seed(FarmCareDemoSeeder::class);
    }
}
