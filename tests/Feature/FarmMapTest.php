<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmMapTest extends TestCase
{
    use RefreshDatabase;

    private function producer(): User
    {
        return User::factory()->create(['role' => Role::Producer]);
    }

    private function farm(User $owner, array $data = []): Farm
    {
        $profile = $owner->producerProfile()->first() ?? ProducerProfile::factory()->for($owner)->create(['is_public' => true]);

        return Farm::factory()->for($profile, 'producerProfile')->create(array_replace([
            'gps_lat' => '34.1234567', 'gps_lng' => '10.7654321', 'is_public' => true,
        ], $data));
    }

    public function test_producer_map_contains_only_owned_coordinates_and_reports_missing_locations(): void
    {
        $owner = $this->producer();
        $located = $this->farm($owner, ['name' => 'Owned mapped farm']);
        $this->farm($owner, ['name' => 'Owned unlocated farm', 'gps_lat' => null, 'gps_lng' => null]);
        $this->farm($this->producer(), ['name' => 'Other private farm', 'gps_lat' => '36.9876543']);
        $response = $this->actingAs($owner)->get(route('producer.farms.map'))->assertOk()
            ->assertSee('Owned mapped farm')->assertDontSee('Other private farm')->assertDontSee('36.9876543')
            ->assertHeader('Cache-Control', 'no-store, private');
        $response->assertViewHas('total', 2)->assertViewHas('located', 1)
            ->assertViewHas('points', fn ($points) => count($points) === 1 && $points[0]['url'] === route('producer.farms.show', $located));
    }

    public function test_map_filters_match_farm_list_and_admin_can_inspect_all_producers(): void
    {
        $owner = $this->producer();
        $sfax = $this->farm($owner, ['name' => 'Sfax parcel']);
        $this->farm($owner, ['name' => 'Tunis parcel', 'governorate' => 'Tunis', 'status' => 'archived']);
        $other = $this->farm($this->producer(), ['name' => 'Another producer parcel']);
        $this->actingAs($owner)->get(route('producer.farms.map', ['search' => 'Sfax', 'governorate' => 'Sfax', 'status' => 'active']))
            ->assertOk()->assertViewHas('total', 1)->assertViewHas('points', fn ($points) => $points[0]['name'] === $sfax->name);
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get(route('admin.farms.map'))
            ->assertOk()->assertViewHas('total', 3)->assertSee($other->name)
            ->assertViewHas('points', fn ($points) => collect($points)->contains('url', route('admin.farms.show', $other)));
        $this->get(route('admin.farms.map', ['governorate' => 'Unknown']))->assertSessionHasErrors('governorate');
    }

    public function test_guest_other_roles_and_inactive_users_cannot_access_private_map(): void
    {
        $this->get(route('producer.farms.map'))->assertRedirect(route('login'));
        $this->get(route('admin.farms.map'))->assertRedirect(route('login'));
        foreach ([Role::Consumer, Role::Distributor, Role::Miller, Role::Laboratory] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('producer.farms.map'))->assertForbidden();
            $this->get(route('admin.farms.map'))->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => Role::Producer, 'is_active' => false]))
            ->get(route('producer.farms.map'))->assertRedirect(route('login'));
    }

    public function test_location_picker_keeps_server_validation_and_zero_coordinates_can_be_saved_and_cleared(): void
    {
        $owner = $this->producer();
        $farm = $this->farm($owner);
        $this->actingAs($owner)->get(route('producer.farms.edit', $farm))->assertOk()
            ->assertSee('Load private map')->assertSee('Clear coordinates')->assertSee('gps_lat');
        $data = $farm->only('name', 'governorate', 'delegation', 'area_ha', 'olive_variety', 'is_public');
        $data += ['farming_type' => $farm->farming_type->value, 'irrigation_type' => $farm->irrigation_type->value];
        $route = route('producer.farms.update', $farm);
        $this->patch($route, $data + ['gps_lat' => 0, 'gps_lng' => null])->assertSessionHasErrors('gps_lng');
        $this->patch($route, $data + ['gps_lat' => 91, 'gps_lng' => 181])->assertSessionHasErrors(['gps_lat', 'gps_lng']);
        $this->patch($route, $data + ['gps_lat' => '0.0000000', 'gps_lng' => '0.0000000'])->assertSessionHasNoErrors();
        $this->get(route('producer.farms.map'))->assertViewHas('points', fn ($points) => $points[0]['lat'] === 0.0 && $points[0]['lng'] === 0.0);
        $this->patch($route, $data + ['gps_lat' => '', 'gps_lng' => ''])->assertSessionHasNoErrors();
        $this->get(route('producer.farms.map'))->assertViewHas('located', 0)->assertSee('No matching farms have coordinates yet');
        $this->assertNull($farm->fresh()->gps_lat);
        $this->assertNull($farm->fresh()->gps_lng);
    }

    public function test_coordinates_and_map_data_never_appear_on_public_origin_and_names_are_escaped(): void
    {
        $farm = $this->farm($this->producer(), ['name' => '<img src=x onerror=alert(1)>']);
        $this->get(route('origin.farms.show', $farm))->assertOk()->assertDontSee('34.1234567')
            ->assertDontSee('10.7654321')->assertDontSee('farmMap(')->assertDontSee('Load private map');
        $this->actingAs($farm->producerProfile->user)->get(route('producer.farms.map'))->assertOk()
            ->assertSee($farm->name)->assertDontSee($farm->name, false);
        $this->actingAs($this->producer())->get(route('producer.farms.show', $farm))->assertForbidden();
        $this->get(route('producer.farms.edit', $farm))->assertForbidden();
    }
}
