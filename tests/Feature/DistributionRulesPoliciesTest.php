<?php

namespace Tests\Feature;

use App\Contracts\OilLotCertificationStatusProvider;
use App\Contracts\OilLotLookup;
use App\Contracts\OilLotOwnership;
use App\Contracts\OilProductEligibility;
use App\Enums\OilProductPublicStatus;
use App\Enums\Role;
use App\Enums\ShipmentStatus;
use App\Enums\TransportType;
use App\Http\Requests\Distribution\ListOilProductFiltersRequest;
use App\Http\Requests\Distribution\ListShipmentFiltersRequest;
use App\Http\Requests\Distribution\StoreDistributorProfileRequest;
use App\Http\Requests\Distribution\StoreOilProductRequest;
use App\Http\Requests\Distribution\StoreShipmentRequest;
use App\Http\Requests\Distribution\UpdateOilProductRequest;
use App\Http\Requests\Distribution\UpdateShipmentRequest;
use App\Models\Distribution\DistributorProfile;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use App\Models\User;
use App\Services\Distribution\ShipmentStatusTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Tests\TestCase;

class DistributionRulesPoliciesTest extends TestCase
{
    use RefreshDatabase;

    private function oilLot(string $code = 'LOT-SFAX-001'): int
    {
        return DB::table('oil_lots')->insertGetId([
            'harvest_id' => null,
            'lot_code' => $code,
            'extraction_date' => '2026-10-01',
            'volume_l' => '400.00',
            'grade' => 'Extra virgin',
            'acidity' => '0.250',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function validProductData(int $lotId): array
    {
        return [
            'name' => 'Extra virgin Chemlali',
            'brand' => 'Sfax Harvest',
            'bottle_volume_ml' => 750,
            'packaging_date' => '2026-10-01',
            'oil_lot_id' => $lotId,
        ];
    }

    private function validShipmentData(int $productId): array
    {
        return [
            'oil_product_id' => $productId,
            'departure_location' => 'Sfax',
            'destination' => 'Tunis',
            'departure_date' => '2026-10-01',
            'distance_km' => 270,
            'transport_type' => TransportType::Truck->value,
        ];
    }

    private function validatorFor(
        string $requestClass,
        array $data,
        ?string $routeKey = null,
        mixed $routeModel = null,
    ): \Illuminate\Contracts\Validation\Validator {
        $request = new $requestClass;
        $request->initialize([], $data, [], [], [], ['REQUEST_METHOD' => Request::METHOD_POST]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app['redirect']);
        $request->setUserResolver(fn () => auth()->user());
        if ($routeKey !== null) {
            $route = new RoutingRoute('PATCH', '/test/{'.$routeKey.'}', ['uses' => static fn () => null]);
            $route->bind($request);
            $route->setParameter($routeKey, $routeModel);
            $request->setRouteResolver(fn () => $route);
        } else {
            $request->setRouteResolver(fn () => null);
        }

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        if (method_exists($request, 'withValidator')) {
            $request->withValidator($validator);
        }
        if (method_exists($request, 'after')) {
            foreach ($request->after() as $callback) {
                $validator->after($callback);
            }
        }

        return $validator;
    }

    public function test_product_request_validates_lot_and_ignores_slug_input(): void
    {
        $producer = User::factory()->create(['role' => Role::Producer]);
        $this->actingAs($producer);
        $lotId = $this->oilLot();
        $data = $this->validProductData($lotId) + ['slug' => 'attacker-slug'];

        $validator = $this->validatorFor(StoreOilProductRequest::class, $data);
        $this->assertFalse($validator->fails());
        $this->assertArrayNotHasKey('slug', $validator->validated());

        $invalidLot = $this->validatorFor(StoreOilProductRequest::class, $this->validProductData(999999));
        $this->assertTrue($invalidLot->fails());
        $this->assertArrayHasKey('oil_lot_id', $invalidLot->errors()->toArray());
    }

    public function test_product_request_rejects_invalid_volume_date_and_status(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Producer]));
        $base = $this->validProductData($this->oilLot());

        foreach ([
            ['name' => ''],
            ['brand' => ''],
            ['bottle_volume_ml' => 99],
            ['bottle_volume_ml' => 5001],
            ['packaging_date' => now()->addDay()->toDateString()],
            ['public_status' => 'public'],
        ] as $invalid) {
            $this->assertTrue($this->validatorFor(
                StoreOilProductRequest::class,
                array_replace($base, $invalid)
            )->fails());
        }

        $invalidImage = $this->validatorFor(StoreOilProductRequest::class, array_replace($base, [
            'image' => UploadedFile::fake()->create('oil.gif', 10, 'image/gif'),
        ]));
        $this->assertTrue($invalidImage->fails());
        $this->assertArrayHasKey('image', $invalidImage->errors()->toArray());
    }

    public function test_product_update_cannot_change_lot_after_shipments_exist(): void
    {
        $producer = User::factory()->create(['role' => Role::Producer]);
        $this->actingAs($producer);
        $product = OilProduct::factory()->create();
        $replacementLotId = $this->oilLot('LOT-SFAX-002');
        Shipment::factory()->for($product)->create();
        $data = $this->validProductData($replacementLotId);

        $validator = $this->validatorFor(
            UpdateOilProductRequest::class,
            $data,
            'product',
            $product
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('oil_lot_id', $validator->errors()->toArray());
    }

    public function test_shipment_request_rejects_invalid_route_values_and_excludes_co2_input(): void
    {
        $distributor = User::factory()->create(['role' => Role::Distributor]);
        $this->actingAs($distributor);
        $product = OilProduct::factory()->create();
        $base = $this->validShipmentData($product->id);

        foreach ([0, -1] as $distance) {
            $validator = $this->validatorFor(StoreShipmentRequest::class, array_replace($base, ['distance_km' => $distance]));
            $this->assertTrue($validator->fails());
            $this->assertArrayHasKey('distance_km', $validator->errors()->toArray());
        }
        $tooFar = $this->validatorFor(StoreShipmentRequest::class, array_replace($base, ['distance_km' => 20001]));
        $this->assertTrue($tooFar->fails());

        $beforeDeparture = $this->validatorFor(StoreShipmentRequest::class, array_replace($base, [
            'arrival_date' => '2026-09-30',
        ]));
        $this->assertTrue($beforeDeparture->fails());
        $this->assertArrayHasKey('arrival_date', $beforeDeparture->errors()->toArray());

        $sameLocation = $this->validatorFor(StoreShipmentRequest::class, array_replace($base, [
            'destination' => 'Sfax',
        ]));
        $this->assertTrue($sameLocation->fails());
        $this->assertArrayHasKey('destination', $sameLocation->errors()->toArray());

        $invalidTransport = $this->validatorFor(StoreShipmentRequest::class, array_replace($base, [
            'transport_type' => 'teleport',
        ]));
        $this->assertTrue($invalidTransport->fails());
        $this->assertArrayHasKey('transport_type', $invalidTransport->errors()->toArray());

        $invalidStatus = $this->validatorFor(StoreShipmentRequest::class, array_replace($base, [
            'status' => 'in-the-air',
        ]));
        $this->assertTrue($invalidStatus->fails());
        $this->assertArrayHasKey('status', $invalidStatus->errors()->toArray());

        $archivedProduct = OilProduct::factory()->archived()->create();
        $archivedShipment = $this->validatorFor(StoreShipmentRequest::class, array_replace($base, [
            'oil_product_id' => $archivedProduct->id,
        ]));
        $this->assertTrue($archivedShipment->fails());
        $this->assertArrayHasKey('oil_product_id', $archivedShipment->errors()->toArray());

        $deliveredWithoutArrival = $this->validatorFor(StoreShipmentRequest::class, array_replace($base, [
            'status' => ShipmentStatus::Delivered->value,
        ]));
        $this->assertTrue($deliveredWithoutArrival->fails());
        $this->assertArrayHasKey('arrival_date', $deliveredWithoutArrival->errors()->toArray());

        $withCo2 = $this->validatorFor(StoreShipmentRequest::class, $base + ['co2_estimate' => 987.65]);
        $this->assertFalse($withCo2->fails());
        $this->assertArrayNotHasKey('co2_estimate', $withCo2->validated());
    }

    public function test_shipment_update_uses_transition_rule_and_ignores_co2_input(): void
    {
        $distributor = User::factory()->create(['role' => Role::Distributor]);
        $this->actingAs($distributor);
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::Planned]);
        $payload = array_replace($this->validShipmentData($shipment->oil_product_id), [
            'status' => ShipmentStatus::InTransit->value,
            'co2_estimate' => 999,
        ]);

        $validator = $this->validatorFor(
            UpdateShipmentRequest::class,
            $payload,
            'shipment',
            $shipment
        );
        $this->assertFalse($validator->fails());
        $this->assertArrayNotHasKey('co2_estimate', $validator->validated());
    }

    public function test_profile_and_list_filter_requests_validate_fields_dates_and_enums(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Distributor]));
        $profileData = [
            'company_name' => 'Sfax Olive Logistics',
            'address' => 'Route de Tunis, Sfax',
            'phone' => '+216 20 123 456',
            'region' => 'Sfax',
        ];
        $this->assertFalse($this->validatorFor(StoreDistributorProfileRequest::class, $profileData)->fails());
        $this->assertTrue($this->validatorFor(
            StoreDistributorProfileRequest::class,
            array_replace($profileData, ['phone' => 'not-a-phone'])
        )->fails());

        $this->assertTrue($this->validatorFor(ListShipmentFiltersRequest::class, [
            'status' => 'unknown',
            'date_from' => 'not-a-date',
            'date_to' => '2026-10-01',
        ])->fails());
        $this->assertFalse($this->validatorFor(ListShipmentFiltersRequest::class, [
            'status' => ShipmentStatus::Planned->value,
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-02',
        ])->fails());

        $this->actingAs(User::factory()->create(['role' => Role::Producer]));
        $this->assertTrue($this->validatorFor(ListOilProductFiltersRequest::class, [
            'status' => 'published',
            'date_from' => '2026-10-05',
            'date_to' => '2026-10-01',
            'owner_id' => 'not-an-id',
        ])->fails());
        $filterOwner = User::factory()->create(['role' => Role::Producer]);
        $this->assertFalse($this->validatorFor(ListOilProductFiltersRequest::class, [
            'status' => OilProductPublicStatus::Visible->value,
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-02',
            'owner_id' => (string) $filterOwner->id,
        ])->fails());
    }

    public function test_shipment_status_transition_allows_only_expected_progression(): void
    {
        $transition = app(ShipmentStatusTransition::class);
        $allowed = [
            [ShipmentStatus::Planned, ShipmentStatus::InTransit],
            [ShipmentStatus::Planned, ShipmentStatus::Cancelled],
            [ShipmentStatus::InTransit, ShipmentStatus::Delivered],
            [ShipmentStatus::InTransit, ShipmentStatus::Cancelled],
            [ShipmentStatus::Delivered, ShipmentStatus::Delivered],
            [ShipmentStatus::Cancelled, ShipmentStatus::Cancelled],
        ];
        foreach ($allowed as [$current, $next]) {
            $this->assertTrue($transition->allows($current, $next));
        }

        $forbidden = [
            [ShipmentStatus::Planned, ShipmentStatus::Delivered],
            [ShipmentStatus::Delivered, ShipmentStatus::Planned],
            [ShipmentStatus::Delivered, ShipmentStatus::Cancelled],
            [ShipmentStatus::Cancelled, ShipmentStatus::Planned],
            ['unknown', ShipmentStatus::Planned],
        ];
        foreach ($forbidden as [$current, $next]) {
            $this->assertFalse($transition->allows($current, $next));
        }
    }

    public function test_policies_enforce_product_shipment_and_profile_ownership(): void
    {
        $producer = User::factory()->create(['role' => Role::Producer]);
        $otherProducer = User::factory()->create(['role' => Role::Producer]);
        $distributor = User::factory()->create(['role' => Role::Distributor]);
        $otherDistributor = User::factory()->create(['role' => Role::Distributor]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $consumer = User::factory()->create(['role' => Role::Consumer]);
        $inactiveProducer = User::factory()->create(['role' => Role::Producer, 'is_active' => false]);

        $product = OilProduct::factory()->create(['created_by_user_id' => $producer->id]);
        $profile = DistributorProfile::factory()->for($distributor)->create();
        $shipment = Shipment::factory()->for($product)->for($profile)->create();

        $this->assertTrue(Gate::forUser($producer)->allows('create', OilProduct::class));
        $this->assertTrue(Gate::forUser($producer)->allows('update', $product));
        $this->assertTrue(Gate::forUser($producer)->allows('archive', $product));
        $this->assertTrue(Gate::forUser($producer)->allows('toggleVisibility', $product));
        $this->assertFalse(Gate::forUser($otherProducer)->allows('update', $product));
        $this->assertFalse(Gate::forUser($otherProducer)->allows('toggleVisibility', $product));
        $this->assertFalse(Gate::forUser($otherProducer)->allows('forceHide', $product));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $product));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $product));
        $this->assertTrue(Gate::forUser($admin)->allows('archive', $product));
        $this->assertTrue(Gate::forUser($admin)->allows('forceHide', $product));
        $this->assertFalse(Gate::forUser($consumer)->allows('create', OilProduct::class));
        $this->assertFalse(Gate::forUser($inactiveProducer)->allows('update', $product));

        $this->assertTrue(Gate::forUser($distributor)->allows('create', Shipment::class));
        $this->assertTrue(Gate::forUser($distributor)->allows('update', $shipment));
        $this->assertTrue(Gate::forUser($distributor)->allows('cancel', $shipment));
        $this->assertFalse(Gate::forUser($otherDistributor)->allows('update', $shipment));
        $this->assertFalse(Gate::forUser($otherDistributor)->allows('cancel', $shipment));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $shipment));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $shipment));
        $this->assertFalse(Gate::forUser($consumer)->allows('create', Shipment::class));
        $this->assertFalse(Gate::forUser($inactiveProducer)->allows('create', Shipment::class));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $shipment));

        $this->assertTrue(Gate::forUser($distributor)->allows('view', $profile));
        $this->assertTrue(Gate::forUser($distributor)->allows('update', $profile));
        $this->assertFalse(Gate::forUser($otherDistributor)->allows('update', $profile));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $profile));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $profile));
    }

    public function test_default_oil_lot_services_are_isolated_and_temporary(): void
    {
        $lotId = $this->oilLot();

        $this->assertTrue(app(OilProductEligibility::class)->isEligible($lotId));
        $this->assertFalse(app(OilProductEligibility::class)->isEligible($lotId + 1000));
        $this->assertSame('not available', app(OilLotCertificationStatusProvider::class)->statusFor($lotId));
        $this->assertTrue(app(OilLotOwnership::class)->belongsToUser($lotId, 123));
        $this->assertFalse(app(OilLotLookup::class)->exists($lotId + 1000));

        $producer = User::factory()->create(['role' => Role::Producer]);
        $this->actingAs($producer);
        $ownership = Mockery::mock(OilLotOwnership::class);
        $ownership->shouldReceive('belongsToUser')->once()->with($lotId, $producer->id)->andReturn(false);
        $this->app->instance(OilLotOwnership::class, $ownership);

        $validator = $this->validatorFor(StoreOilProductRequest::class, $this->validProductData($lotId));
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('oil_lot_id', $validator->errors()->toArray());
    }
}
