<?php

namespace Tests\Feature;

use App\Contracts\OilLotCertificationStatusProvider;
use App\Enums\Role;
use App\Models\Certification\Certificate;
use App\Models\Certification\CertificateRequest;
use App\Models\Distribution\OilProduct;
use App\Models\Production\OilLot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_trace_follows_the_real_milling_relation_and_respects_origin_publication(): void
    {
        $product = OilProduct::factory()->create();
        $lot = OilLot::findOrFail($product->oil_lot_id);
        $farm = $lot->harvest->farm;
        $farm->update(['is_public' => true, 'status' => 'active']);
        $farm->producerProfile->update(['is_public' => true, 'is_active' => true]);
        $this->get(route('trace.show', $product->slug))->assertOk()
            ->assertSee($lot->lot_number)->assertSee($farm->name)->assertSee('Harvest date')
            ->assertDontSee($farm->producerProfile->user->email);
        $farm->forceFill(['status' => 'disabled'])->save();
        $this->get(route('trace.show', $product->slug))->assertOk()
            ->assertSee('Origin not published yet')->assertDontSee($farm->name);
        $farm->forceFill(['status' => 'active'])->save();
        $farm->producerProfile->user->forceFill(['is_active' => false])->save();
        $this->get(route('trace.show', $product->slug))->assertOk()->assertDontSee($farm->name);
    }

    public function test_catalog_and_trace_agree_on_active_and_expired_certificates(): void
    {
        $product = OilProduct::factory()->create();
        $request = CertificateRequest::create(['oil_lot_id' => $product->oil_lot_id, 'producer_user_id' => $product->created_by_user_id, 'status' => 'approved']);
        $certificate = Certificate::create([
            'certificate_request_id' => $request->id, 'certificate_number' => 'CERT-INTEGRATION-001',
            'type' => 'Extra Virgin Olive Oil', 'issue_date' => now()->subDay(),
            'expiry_date' => now()->addYear(), 'status' => 'active',
        ]);
        $this->assertSame('verified', app(OilLotCertificationStatusProvider::class)->statusFor($product->oil_lot_id));
        $this->get(route('catalog.index'))->assertOk()->assertSee('Verified');
        $this->get(route('trace.show', $product->slug))->assertOk()->assertSee('Verified')->assertSee('CERT-INTEGRATION-001');
        $certificate->update(['expiry_date' => now()->subDay()]);
        $this->assertSame('expired', app(OilLotCertificationStatusProvider::class)->statusFor($product->oil_lot_id));
        $this->get(route('trace.show', $product->slug))->assertOk()->assertSee('Expired');
        $certificate->update(['expiry_date' => now()->addYear()]);
        $request->update(['status' => 'rejected']);
        $this->assertSame('rejected', app(OilLotCertificationStatusProvider::class)->statusFor($product->oil_lot_id));
        $this->get(route('trace.show', $product->slug))->assertOk()->assertDontSee('Verified');
        $request->update(['status' => 'approved']);
        $certificate->delete();
        $this->assertSame('pending', app(OilLotCertificationStatusProvider::class)->statusFor($product->oil_lot_id));
    }

    public function test_opening_certification_form_never_creates_an_oil_lot(): void
    {
        $producer = User::factory()->create(['role' => Role::Producer]);
        $this->actingAs($producer)->get(route('certification.producer.requests.create'))->assertOk()->assertSee('No eligible oil lots');
        $this->assertDatabaseCount('oil_lots', 0);
        $this->assertDatabaseCount('certificate_requests', 0);
    }

    public function test_lab_can_approve_real_lot_with_optional_fields_omitted(): void
    {
        $lot = OilLot::factory()->create();
        $request = CertificateRequest::create(['oil_lot_id' => $lot->id, 'producer_user_id' => $lot->producer_user_id, 'status' => 'pending']);
        $lab = User::factory()->create(['role' => Role::Laboratory]);
        $this->actingAs($lab)->post(route('lab.requests.analyze', $request), ['acidity' => '0.3', 'result' => true])
            ->assertSessionHasNoErrors()->assertRedirect(route('lab.requests.index'));
        $this->assertDatabaseHas('certificate_requests', ['id' => $request->id, 'status' => 'approved']);
        $this->assertDatabaseHas('lab_analyses', ['certificate_request_id' => $request->id, 'notes' => null, 'peroxide_value' => null]);
        $this->assertSame('verified', app(OilLotCertificationStatusProvider::class)->statusFor($lot->id));
    }
}
