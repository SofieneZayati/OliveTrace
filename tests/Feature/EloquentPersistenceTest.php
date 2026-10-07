<?php

namespace Tests\Feature;

use App\Enums\FarmingType;
use App\Enums\FarmStatus;
use App\Enums\IrrigationType;
use App\Models\Production\Farm;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class EloquentPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_read_existing_column_names_and_relations_without_changing_the_schema(): void
    {
        $user = User::factory()->create();
        $profileId = DB::table('producer_profiles')->insertGetId([
            'user_id' => $user->id, 'display_name' => 'Existing producer',
            'is_active' => true, 'is_public' => true, 'created_at' => '2026-10-06 10:00:00',
            'updated_at' => '2026-10-06 10:00:00',
        ]);
        $farmId = DB::table('farms')->insertGetId([
            'producer_profile_id' => $profileId, 'name' => 'Existing farm', 'governorate' => 'Sfax',
            'area_ha' => '12.50', 'olive_variety' => 'Chemlali', 'farming_type' => 'integrated',
            'irrigation_type' => 'rainfed', 'gps_lat' => '34.1234567', 'gps_lng' => '10.7654321',
            'status' => 'active', 'is_public' => true,
            'created_at' => '2026-10-06 10:00:00', 'updated_at' => '2026-10-06 10:00:00',
        ]);
        $farm = Farm::with('producerProfile.user')->findOrFail($farmId);
        $this->assertSame($profileId, $farm->producerProfile->id);
        $this->assertTrue($farm->producerProfile->user->is($user));
        $this->assertTrue($user->producerProfile->farms->first()->is($farm));
        $this->assertSame('12.50', $farm->area_ha);
        $this->assertSame('34.1234567', $farm->gps_lat);
        $this->assertSame(FarmingType::Integrated, $farm->farming_type);
        $this->assertSame(IrrigationType::Rainfed, $farm->irrigation_type);
        $this->assertSame(FarmStatus::Active, $farm->status);
        $this->assertTrue($farm->is_public);
        $this->assertSame('2026-10-06 10:00:00', $farm->created_at->format('Y-m-d H:i:s'));
        $farm->name = 'Updated farm';
        $farm->save();
        $this->assertSame('Updated farm', $farm->fresh()->name);
        $this->assertSame('2026-10-06 10:00:00', $farm->fresh()->created_at->format('Y-m-d H:i:s'));
        $this->artisan('olivetrace:check-database')->assertSuccessful();
    }

    public function test_one_transaction_rolls_back_shared_users_and_production_models_together(): void
    {
        try {
            DB::transaction(function () {
                $user = User::factory()->create();
                $profile = ProducerProfile::factory()->for($user)->create();
                Farm::factory()->for($profile, 'producerProfile')->create();
                throw new RuntimeException('Simulated integration failure');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated integration failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('producer_profiles', 0);
        $this->assertDatabaseCount('farms', 0);
    }
}
