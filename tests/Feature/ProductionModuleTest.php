<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use App\Repositories\Production\Farms;
use Database\Seeders\DevelopmentUserSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionModuleTest extends TestCase
{
    use RefreshDatabase;

    private function producer(): User
    {
        return User::factory()->create(['role' => Role::Producer]);
    }

    private function profile(User $user, array $overrides = []): ProducerProfile
    {
        return ProducerProfile::factory()->for($user)->create($overrides);
    }

    private function farm(ProducerProfile $profile, array $overrides = []): Farm
    {
        return Farm::factory()->for($profile, 'producerProfile')->create($overrides);
    }

    private function profileData(array $overrides = []): array
    {
        return array_replace(['display_name' => 'Sfax Olive Growers', 'phone' => '+216 20 000 000', 'address' => 'Private business address', 'company_name' => 'Demo Cooperative', 'description' => 'Our producer story.', 'is_public' => true], $overrides);
    }

    private function farmData(array $overrides = []): array
    {
        return array_replace(['name' => 'Farm El Baraka', 'governorate' => 'Sfax', 'delegation' => 'Agareb', 'area_ha' => '12.50', 'olive_variety' => 'Chemlali', 'farming_type' => 'integrated', 'irrigation_type' => 'rainfed', 'gps_lat' => '34.74', 'gps_lng' => '10.76', 'description' => 'Private parcel notes.', 'is_public' => true], $overrides);
    }

    public function test_producer_can_complete_profile_and_farm_crud_using_eloquent(): void
    {
        $producer = $this->producer();
        $this->actingAs($producer)->get(route('producer.profile.show'))->assertRedirect(route('producer.profile.create'));
        $this->get(route('producer.profile.create'))->assertOk();
        $this->post(route('producer.profile.store'), $this->profileData())->assertRedirect(route('producer.profile.show'));
        $this->assertDatabaseHas('producer_profiles', ['user_id' => $producer->id, 'display_name' => 'Sfax Olive Growers']);
        $this->get(route('producer.profile.show'))->assertOk()->assertSee('Sfax Olive Growers');
        $this->get(route('producer.profile.edit'))->assertOk();
        $this->patch(route('producer.profile.update'), $this->profileData(['display_name' => 'Updated cooperative']))->assertSessionHasNoErrors();
        $this->get(route('producer.farms.create'))->assertOk();
        $this->post(route('producer.farms.store'), $this->farmData())->assertSessionHasNoErrors();
        $farm = Farm::where('name', 'Farm El Baraka')->firstOrFail();
        $this->assertSame($producer->id, $farm->producerProfile->user_id);
        $this->assertSame('Updated cooperative', $farm->producerProfile->display_name);
        $this->assertCount(1, $farm->producerProfile->farms);
        $this->get(route('producer.farms.index'))->assertOk()->assertSee('Farm El Baraka');
        $this->get(route('producer.farms.show', $farm->id))->assertOk()->assertSee('Sustainability assistant');
        $this->get(route('producer.farms.edit', $farm->id))->assertOk();
        $this->patch(route('producer.farms.update', $farm->id), $this->farmData(['name' => 'Renamed parcel', 'area_ha' => '14.25']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('farms', ['id' => $farm->id, 'name' => 'Renamed parcel', 'area_ha' => '14.25']);
        $this->delete(route('producer.profile.destroy'))->assertSessionHasErrors('profile');
        $id = $farm->id;
        $this->delete(route('producer.farms.destroy', $id))->assertRedirect(route('producer.farms.index'));
        $this->assertDatabaseMissing('farms', ['id' => $id]);
        $this->delete(route('producer.profile.destroy'))->assertRedirect(route('producer.profile.create'));
        $this->assertDatabaseMissing('producer_profiles', ['user_id' => $producer->id]);
        $this->assertModelExists($producer);
    }

    public function test_other_producers_cannot_read_mutate_delete_or_request_ai_for_another_farm(): void
    {
        $owner = $this->producer();
        $profile = $this->profile($owner);
        $farm = $this->farm($profile);
        $other = $this->producer();
        $this->actingAs($other)->get(route('producer.farms.index'))->assertOk()->assertDontSee($farm->name);
        $this->get(route('producer.farms.index', ['search' => $profile->display_name]))->assertOk()->assertDontSee($farm->name);
        $this->get(route('producer.farms.show', $farm->id))->assertForbidden();
        $this->get(route('producer.farms.edit', $farm->id))->assertForbidden();
        $this->patch(route('producer.farms.update', $farm->id), $this->farmData())->assertForbidden();
        $this->delete(route('producer.farms.destroy', $farm->id))->assertForbidden();
        $this->patch(route('producer.farms.archive', $farm->id))->assertForbidden();
        $this->post(route('producer.farms.advice', $farm->id))->assertForbidden();
        $this->get(route('producer.logo', $profile->id))->assertForbidden();
        $this->assertDatabaseHas('farms', ['id' => $farm->id, 'name' => $farm->name, 'status' => 'active']);
    }

    public function test_guests_other_roles_and_inactive_accounts_cannot_manage_production(): void
    {
        $this->get(route('producer.farms.index'))->assertRedirect(route('login'));
        foreach ([Role::Consumer, Role::Miller, Role::Laboratory, Role::Distributor, Role::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('producer.farms.index'))->assertForbidden();
            $this->post(route('producer.profile.store'), $this->profileData())->assertForbidden();
        }
        $inactive = User::factory()->create(['role' => Role::Producer, 'is_active' => false]);
        $this->actingAs($inactive)->get(route('producer.farms.index'))->assertRedirect(route('login'));
    }

    public function test_validation_rejects_invalid_fields_and_ownership_or_status_injection(): void
    {
        $producer = $this->producer();
        $profile = $this->profile($producer);
        $this->actingAs($producer)->post(route('producer.farms.store'), $this->farmData([
            'name' => '', 'governorate' => 'Unknown', 'area_ha' => 0, 'farming_type' => 'certified',
            'irrigation_type' => 'unknown', 'gps_lat' => 95, 'gps_lng' => 190, 'producer_profile_id' => 123, 'status' => 'active',
        ]))->assertSessionHasErrors(['name', 'governorate', 'area_ha', 'farming_type', 'irrigation_type', 'gps_lat', 'gps_lng', 'producer_profile_id', 'status']);
        $this->post(route('producer.farms.store'), $this->farmData(['gps_lng' => null]))->assertSessionHasErrors('gps_lng');
        $this->post(route('producer.farms.store'), $this->farmData(['area_ha' => '0.001']))->assertSessionHasErrors('area_ha');
        $this->patch(route('producer.profile.update'), $this->profileData(['is_active' => true, 'user_id' => 123]))->assertSessionHasErrors(['is_active', 'user_id']);
        $this->post(route('producer.profile.store'), $this->profileData())->assertSessionHasErrors('profile');
        $this->assertDatabaseCount('producer_profiles', 1);
        $this->assertDatabaseCount('farms', 0);
    }

    public function test_admin_can_inspect_correct_and_disable_but_cannot_use_owner_deletion(): void
    {
        $profile = $this->profile($this->producer(), ['is_public' => true]);
        $farm = $this->farm($profile, ['is_public' => true]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin)->get(route('admin.producers.index', ['search' => $profile->display_name]))->assertOk()->assertSee($profile->display_name);
        $this->get(route('admin.producers.show', $profile->id))->assertOk()->assertSee($farm->name);
        $this->get(route('admin.producers.edit', $profile->id))->assertOk();
        $this->get(route('admin.farms.index', ['governorate' => 'Sfax']))->assertOk()->assertSee($farm->name);
        $this->get(route('admin.farms.show', $farm->id))->assertOk();
        $this->get(route('admin.farms.edit', $farm->id))->assertOk();
        $this->patch(route('admin.farms.update', $farm->id), $this->farmData(['name' => 'Corrected farm']))->assertSessionHasNoErrors();
        $this->patch(route('admin.farms.status', $farm->id), ['status' => 'disabled'])->assertSessionHasNoErrors();
        $this->actingAs($profile->user)->patch(route('producer.farms.archive', $farm->id))->assertSessionHasErrors('farm');
        $this->assertDatabaseHas('farms', ['id' => $farm->id, 'status' => 'disabled']);
        $this->actingAs($admin);
        $this->get(route('origin.farms.show', $farm->id))->assertNotFound();
        $this->assertCount(0, app(Farms::class)->selectableForUser($profile->user_id));
        $this->patch(route('admin.farms.status', $farm->id), ['status' => 'active'])->assertSessionHasNoErrors();
        $this->patch(route('admin.producers.update', $profile->id), $this->profileData(['is_active' => false]))->assertSessionHasNoErrors();
        $this->get(route('origin.farms.show', $farm->id))->assertNotFound();
        $this->assertCount(0, app(Farms::class)->selectableForUser($profile->user_id));
        $this->actingAs(User::find($profile->user_id))->post(route('producer.farms.store'), $this->farmData())->assertSessionHasErrors('profile');
    }

    public function test_public_origin_requires_both_opt_ins_and_never_exposes_private_data(): void
    {
        $user = $this->producer();
        $profile = $this->profile($user, ['phone' => 'private-phone-marker', 'address' => 'private-address-marker', 'is_public' => true]);
        $farm = $this->farm($profile, ['is_public' => true, 'gps_lat' => '34.1234567', 'gps_lng' => '10.7654321', 'description' => 'private-notes-marker']);
        $this->get(route('origin.farms.show', $farm->id))->assertOk()->assertSee($farm->name)->assertSee($profile->display_name)
            ->assertDontSee('private-phone-marker')->assertDontSee('private-address-marker')->assertDontSee('private-notes-marker')->assertDontSee('34.1234567')->assertDontSee($user->email);
        $profile->is_public = false;
        $profile->save();
        $this->get(route('origin.farms.show', $farm->id))->assertNotFound();
        $profile->is_public = true;
        $farm->is_public = false;
        $profile->save();
        $farm->save();
        $this->get(route('origin.farms.show', $farm->id))->assertNotFound();
        $farm->is_public = true;
        $farm->save();
        $user->is_active = false;
        $user->save();
        $this->get(route('origin.farms.show', $farm->id))->assertNotFound();
        $this->assertCount(0, app(Farms::class)->selectableForUser($user->id));
    }

    public function test_farm_deletion_archives_when_harvest_history_exists(): void
    {
        $user = $this->producer();
        $profile = $this->profile($user, ['is_public' => true]);
        $farm = $this->farm($profile, ['is_public' => true]);
        // A minimal downstream fixture verifies the agreed FK without implementing Mariem's module.
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->restrictOnDelete();
        });
        DB::table('harvests')->insert(['farm_id' => $farm->id]);
        try {
            $this->actingAs($user)->delete(route('producer.farms.destroy', $farm->id))->assertSessionHasNoErrors();
            $this->assertDatabaseHas('farms', ['id' => $farm->id, 'status' => 'archived']);
            $this->assertDatabaseHas('harvests', ['farm_id' => $farm->id]);
            $this->get(route('origin.farms.show', $farm->id))->assertNotFound();
            $this->assertCount(0, app(Farms::class)->selectableForUser($user->id));
        } finally {
            Schema::drop('harvests');
        }
    }

    public function test_private_image_upload_can_be_replaced_removed_and_is_not_publicly_readable(): void
    {
        Storage::fake('local');
        $user = $this->producer();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aU1cAAAAASUVORK5CYII=');
        $this->actingAs($user)->post(route('producer.profile.store'), $this->profileData(['logo' => UploadedFile::fake()->createWithContent('logo.png', $png)]))->assertSessionHasNoErrors();
        $profile = $user->producerProfile;
        $old = $profile->logo_path;
        Storage::disk('local')->assertExists($old);
        $this->get(route('producer.logo', $profile->id))->assertOk();
        $this->patch(route('producer.profile.update'), $this->profileData(['logo' => UploadedFile::fake()->createWithContent('new.png', $png)]))->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($old);
        $new = $profile->fresh()->logo_path;
        Storage::disk('local')->assertExists($new);
        $this->patch(route('producer.profile.update'), $this->profileData(['remove_logo' => true]))->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($new);
        $this->patch(route('producer.profile.update'), $this->profileData(['logo' => UploadedFile::fake()->createWithContent('logo.svg', '<svg></svg>')]))->assertSessionHasErrors('logo');
    }

    public function test_factories_and_seeder_support_the_shared_demo_contract(): void
    {
        $this->seed(DevelopmentUserSeeder::class);
        $this->seed(ProductionSeeder::class);
        $existing = ProducerProfile::firstOrFail();
        $existing->display_name = 'Edited demo producer';
        $existing->save();
        $existing->farms()->where('name', 'Farm El Baraka')->update(['area_ha' => '15.25']);
        $this->seed(ProductionSeeder::class);
        $this->assertDatabaseCount('producer_profiles', 1);
        $this->assertDatabaseCount('farms', 2);
        $farms = app(Farms::class)->selectableForUser(User::where('email', 'producer@test.com')->firstOrFail()->id);
        $this->assertCount(2, $farms);
        $this->assertSame('Chemlali', $farms[0]->olive_variety);
        $this->assertTrue($farms[0]->producerProfile->user->is($farms[1]->producerProfile->user));
        $this->assertDatabaseHas('farms', ['name' => 'Farm El Baraka', 'governorate' => 'Sfax']);
        $this->assertDatabaseHas('producer_profiles', ['id' => $existing->id, 'display_name' => 'Edited demo producer']);
        $this->assertDatabaseHas('farms', ['name' => 'Farm El Baraka', 'area_ha' => '15.25']);
    }

    public function test_shared_account_deletion_cannot_remove_a_producer_with_origin_history(): void
    {
        $user = $this->producer();
        $profile = $this->profile($user);
        $farm = $this->farm($profile);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin)->delete(route('admin.users.destroy', $user))->assertSessionHasErrors('user');
        $this->assertModelExists($user);
        $this->assertDatabaseHas('producer_profiles', ['id' => $profile->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('farms', ['id' => $farm->id]);
    }
}
