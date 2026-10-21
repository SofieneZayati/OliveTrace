<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductionServerValidationTest extends TestCase
{
    use RefreshDatabase;

    private function producer(): User
    {
        return User::factory()->create(['role' => Role::Producer]);
    }

    private function farmData(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Server validated parcel', 'governorate' => 'Sfax', 'delegation' => 'Agareb',
            'area_ha' => '3.50', 'olive_variety' => 'Chemlali', 'farming_type' => 'integrated',
            'irrigation_type' => 'drip', 'gps_lat' => '34.74', 'gps_lng' => '10.76',
            'description' => 'Private farm notes', 'is_public' => false,
        ], $overrides);
    }

    public function test_profile_errors_come_from_laravel_and_never_create_invalid_records(): void
    {
        $producer = $this->producer();
        $this->actingAs($producer)->get(route('producer.profile.create'))->assertOk()->assertSee('novalidate', false);
        $this->from(route('producer.profile.create'))->post(route('producer.profile.store'), [
            'display_name' => '', 'phone' => 'abc123', 'company_name' => str_repeat('x', 151),
            'is_public' => false, 'logo' => UploadedFile::fake()->createWithContent('logo.pdf', '%PDF-not-an-image'),
        ])->assertRedirect(route('producer.profile.create'))
            ->assertSessionHasErrors(['display_name', 'phone', 'company_name', 'logo']);
        $this->assertDatabaseCount('producer_profiles', 0);
        $this->get(route('producer.profile.create'))->assertOk()->assertSee('Please check the following:')
            ->assertSee('The display name field is required.')->assertSee('abc123');
    }

    public function test_invalid_profile_update_preserves_the_saved_profile_and_displays_server_errors(): void
    {
        $producer = $this->producer();
        $profile = ProducerProfile::factory()->for($producer)->create(['display_name' => 'Original producer']);
        $original = $profile->fresh()->getRawOriginal();
        $this->actingAs($producer)->from(route('producer.profile.edit'))->patch(route('producer.profile.update'), [
            'display_name' => '', 'phone' => 'invalid phone', 'is_public' => true,
        ])->assertRedirect(route('producer.profile.edit'))->assertSessionHasErrors(['display_name', 'phone']);
        $this->assertSame($original, $profile->fresh()->getRawOriginal());
        $this->get(route('producer.profile.edit'))->assertOk()->assertSee('novalidate', false)
            ->assertSee('Please check the following:')->assertSee('invalid phone');
    }

    public function test_invalid_farm_creation_and_update_are_rejected_without_trusting_html_constraints(): void
    {
        $producer = $this->producer();
        $profile = ProducerProfile::factory()->for($producer)->create();
        $this->actingAs($producer)->get(route('producer.farms.create'))->assertOk()->assertSee('novalidate', false);
        $invalid = $this->farmData(['area_ha' => 0, 'gps_lat' => 91, 'gps_lng' => 181, 'farming_type' => 'unsupported']);
        $this->from(route('producer.farms.create'))->post(route('producer.farms.store'), $invalid)
            ->assertRedirect(route('producer.farms.create'))->assertSessionHasErrors(['area_ha', 'gps_lat', 'gps_lng', 'farming_type']);
        $this->assertDatabaseCount('farms', 0);
        $this->get(route('producer.farms.create'))->assertOk()->assertSee('Please check the following:')->assertSee('Server validated parcel');

        $farm = Farm::factory()->for($profile, 'producerProfile')->create();
        $original = $farm->fresh()->getRawOriginal();
        $this->from(route('producer.farms.edit', $farm))->patch(route('producer.farms.update', $farm), $invalid)
            ->assertRedirect(route('producer.farms.edit', $farm))->assertSessionHasErrors(['area_ha', 'gps_lat', 'gps_lng', 'farming_type']);
        $this->assertSame($original, $farm->fresh()->getRawOriginal());
        $this->get(route('producer.farms.edit', $farm))->assertOk()->assertSee('novalidate', false)->assertSee('Please check the following:');
    }

    public function test_admin_profile_farm_and_moderation_forms_use_the_same_server_validation(): void
    {
        $profile = ProducerProfile::factory()->for($this->producer())->create();
        $farm = Farm::factory()->for($profile, 'producerProfile')->create();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $this->get(route('admin.producers.edit', $profile))->assertOk()->assertSee('novalidate', false);
        $this->get(route('admin.farms.edit', $farm))->assertOk()->assertSee('novalidate', false);
        $this->get(route('admin.farms.show', $farm))->assertOk()->assertSee('novalidate', false);
        $this->patch(route('admin.producers.update', $profile), ['display_name' => '', 'is_public' => false, 'is_active' => true])
            ->assertSessionHasErrors('display_name');
        $this->patch(route('admin.farms.update', $farm), $this->farmData(['area_ha' => -1]))->assertSessionHasErrors('area_ha');
        $this->patch(route('admin.farms.status', $farm), ['status' => 'invented'])->assertSessionHasErrors('status');
        $this->assertSame('active', $farm->fresh()->status->value);
    }

    public function test_journal_creation_and_completion_errors_are_rendered_after_server_validation(): void
    {
        $producer = $this->producer();
        $profile = ProducerProfile::factory()->for($producer)->create();
        $farm = Farm::factory()->for($profile, 'producerProfile')->create();
        $this->actingAs($producer)->get(route('producer.farms.activities.create', $farm))->assertOk()->assertSee('novalidate', false);
        $this->from(route('producer.farms.activities.create', $farm))->post(route('producer.farms.activities.store', $farm), [
            'title' => '', 'type' => 'invented', 'planned_on' => 'not-a-date',
        ])->assertSessionHasErrors(['title', 'type', 'planned_on']);
        $this->assertDatabaseCount('farm_activities', 0);
        $this->get(route('producer.farms.activities.create', $farm))->assertSee('Please check the following:');
        $entry = $farm->activities()->create(['title' => 'Irrigation check', 'type' => 'irrigation', 'planned_on' => today()->toDateString()]);
        $this->get(route('producer.farms.activities.index', $farm))->assertOk()->assertSee('novalidate', false);
        $this->from(route('producer.farms.activities.index', $farm))->patch(route('producer.farms.activities.status', [$farm, $entry]), [
            'status' => 'completed', 'completed_on' => today()->toDateString(), 'water_m3' => 0,
        ])->assertRedirect(route('producer.farms.activities.index', $farm))->assertSessionHasErrors('water_m3');
        $this->assertSame('planned', $entry->fresh()->status->value);
        $this->get(route('producer.farms.activities.index', $farm))->assertOk()->assertSee('Please check the following:');
    }

    public function test_failed_completion_does_not_change_the_journal_filter_selection(): void
    {
        $producer = $this->producer();
        $profile = ProducerProfile::factory()->for($producer)->create();
        $farm = Farm::factory()->for($profile, 'producerProfile')->create();
        $entry = $farm->activities()->create(['title' => 'Planned irrigation', 'type' => 'irrigation', 'planned_on' => today()->toDateString()]);
        $journal = route('producer.farms.activities.index', [$farm, 'status' => 'planned']);
        $this->actingAs($producer)->from($journal)->patch(route('producer.farms.activities.status', [$farm, $entry]), [
            'status' => 'completed', 'completed_on' => today()->toDateString(), 'water_m3' => 0,
        ])->assertRedirect($journal)->assertSessionHasErrors('water_m3');
        $response = $this->get($journal)->assertOk()->assertSee('Planned irrigation')->assertSee('Please check the following:');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $selected = (new \DOMXPath($document))->query('//select[@id="status"]/option[@selected]');
        $this->assertCount(1, $selected);
        $this->assertSame('planned', $selected->item(0)->getAttribute('value'));
    }
}
