<?php

namespace Tests\Feature;

use App\Contracts\OilLotCertificationStatusProvider;
use App\Enums\OilQuality;
use App\Enums\Role;
use App\Http\Controllers\Certification\LabAnalysisController;
use App\Models\Certification\Certificate;
use App\Models\Certification\CertificateRequest;
use App\Models\Certification\LabAnalysis;
use App\Models\Production\OilLot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CertificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_submissions_do_not_create_duplicate_pending_requests(): void
    {
        $lot = OilLot::factory()->create();
        $this->actingAs($lot->producer);
        $payload = ['oil_lot_id' => $lot->id, 'note' => 'Please analyze this lot.'];
        $form = route('certification.producer.requests.create');

        $this->from($form)->post(route('certification.producer.requests.store'), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('certification.producer.requests.index'));
        $this->from($form)->post(route('certification.producer.requests.store'), $payload)
            ->assertRedirect($form)->assertSessionHasErrors('oil_lot_id')->assertSessionHasInput('note', $payload['note']);
        $this->assertDatabaseCount('certificate_requests', 1);
        $this->assertNotNull(CertificateRequest::first()->requested_at);
        $this->get($form)->assertOk()->assertDontSee($lot->lot_number);

        CertificateRequest::first()->update(['status' => 'approved']);
        $this->post(route('certification.producer.requests.store'), $payload)->assertSessionHasErrors('oil_lot_id');
        $this->assertDatabaseCount('certificate_requests', 1);
    }

    public function test_rejected_lot_can_be_resubmitted_without_losing_the_previous_request(): void
    {
        $lot = OilLot::factory()->create();
        $previous = $this->requestFor($lot, 'rejected');
        $this->actingAs($lot->producer)
            ->get(route('certification.producer.requests.create'))->assertOk()->assertSee($lot->lot_number);
        $this->post(route('certification.producer.requests.store'), ['oil_lot_id' => $lot->id])
            ->assertSessionHasNoErrors()->assertRedirect(route('certification.producer.requests.index'));
        $this->assertDatabaseCount('certificate_requests', 2);
        $this->assertSame('rejected', $previous->fresh()->status);
        $this->assertSame('pending', $lot->certificateRequests()->latest('id')->first()->status);
    }

    public function test_producer_cannot_request_certification_for_another_producers_lot(): void
    {
        $lot = OilLot::factory()->create();
        $other = User::factory()->create(['role' => Role::Producer]);
        $this->actingAs($other)->get(route('certification.producer.requests.create'))
            ->assertOk()->assertDontSee($lot->lot_number);
        $this->post(route('certification.producer.requests.store'), ['oil_lot_id' => $lot->id])->assertNotFound();
        $this->assertDatabaseCount('certificate_requests', 0);
    }

    public function test_lab_submission_is_processed_once_and_preserves_the_recorded_oil_grade(): void
    {
        $lot = OilLot::factory()->create(['quality_grade' => OilQuality::Virgin]);
        $certificateRequest = $this->requestFor($lot);
        $lab = User::factory()->create(['role' => Role::Laboratory]);
        $this->actingAs($lab);
        $endpoint = route('lab.requests.analyze', $certificateRequest);

        $this->post($endpoint, ['acidity' => 1.2, 'result' => 1])
            ->assertSessionHasNoErrors()->assertRedirect(route('lab.requests.index'));
        $this->post($endpoint, ['acidity' => 0.1, 'result' => 0])->assertSessionHas('error');
        $this->assertDatabaseCount('lab_analyses', 1);
        $this->assertDatabaseCount('certificates', 1);
        $this->assertDatabaseHas('certificates', ['type' => 'Virgin Olive Oil']);
        $this->assertEquals(1.2, LabAnalysis::first()->acidity);
        $this->assertSame('approved', $certificateRequest->fresh()->status);
    }

    public function test_stale_route_binding_cannot_reprocess_a_request_finished_by_another_lab(): void
    {
        $lot = OilLot::factory()->create();
        $stale = $this->requestFor($lot);
        $lab = User::factory()->create(['role' => Role::Laboratory]);
        $this->actingAs($lab)->post(route('lab.requests.analyze', $stale), ['acidity' => 0.3, 'result' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame('pending', $stale->status);

        // Simulate a second request whose route binding happened before the first transaction committed.
        $request = Request::create('/lab/requests/'.$stale->id.'/analyze', 'POST', ['acidity' => 0.8, 'result' => 0]);
        $request->setUserResolver(fn () => $lab);
        $response = app(LabAnalysisController::class)->analyze($request, $stale);
        $this->assertTrue($response->getSession()->has('error'));
        $this->assertDatabaseCount('lab_analyses', 1);
        $this->assertDatabaseCount('certificates', 1);
        $this->assertSame('approved', $stale->fresh()->status);
    }

    public function test_public_certificate_and_catalog_agree_on_dates_status_and_request_decision(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $lot = OilLot::factory()->create();
        $certificateRequest = $this->requestFor($lot, 'approved');
        $certificate = Certificate::create([
            'certificate_request_id' => $certificateRequest->id,
            'certificate_number' => 'CERT-DATE-BOUNDARIES',
            'type' => 'Olive Oil', 'issue_date' => today()->subDay(),
            'expiry_date' => today(), 'status' => 'active',
        ]);

        foreach (['active', 'valid', 'verified', 'certified'] as $status) {
            $certificate->update(['status' => $status]);
            $this->get(route('certificates.show', $certificate->certificate_number))
                ->assertOk()->assertViewHas('valid', true)->assertSee('Laboratory results are not available.')
                ->assertSee('Certificate verification | OliveTrace')->assertDontSee('OliveTrace inspiration');
            $this->assertSame('verified', app(OilLotCertificationStatusProvider::class)->statusFor($lot->id));
        }

        $cases = [
            ['issue_date' => today()->addDay(), 'expiry_date' => today()->addYear(), 'status' => 'active'],
            ['issue_date' => today()->subYear(), 'expiry_date' => today()->subDay(), 'status' => 'active'],
            ['issue_date' => today()->subDay(), 'expiry_date' => today()->addYear(), 'status' => 'revoked'],
        ];
        foreach ($cases as $changes) {
            $certificate->update($changes);
            $this->get(route('certificates.show', $certificate->certificate_number))
                ->assertOk()->assertViewHas('valid', false)->assertSee('NOT VALID');
            $this->assertNotSame('verified', app(OilLotCertificationStatusProvider::class)->statusFor($lot->id));
        }
        $certificate->update(['status' => 'active']);
        $certificateRequest->update(['status' => 'rejected']);
        $this->get(route('certificates.show', $certificate->certificate_number))->assertOk()->assertViewHas('valid', false);
        $this->assertSame('rejected', app(OilLotCertificationStatusProvider::class)->statusFor($lot->id));
    }

    public function test_public_certificate_shows_zero_peroxide_without_exposing_private_notes(): void
    {
        $lot = OilLot::factory()->create();
        $certificateRequest = $this->requestFor($lot, 'approved');
        $lab = User::factory()->create(['role' => Role::Laboratory]);
        LabAnalysis::create([
            'certificate_request_id' => $certificateRequest->id, 'lab_user_id' => $lab->id,
            'analysis_date' => now(), 'acidity' => 0.3, 'peroxide_value' => 0, 'result' => true,
            'notes' => 'Private laboratory discussion',
        ]);
        $certificate = Certificate::create([
            'certificate_request_id' => $certificateRequest->id, 'certificate_number' => 'CERT-ZERO-PEROXIDE',
            'type' => 'Olive Oil', 'issue_date' => today(), 'expiry_date' => today()->addYear(), 'status' => 'active',
        ]);
        $this->get(route('certificates.show', $certificate->certificate_number))->assertOk()
            ->assertSee('Peroxide Value:')->assertSee('Approved')->assertDontSee('Private laboratory discussion');
    }

    public function test_invalid_lab_values_are_rejected_before_saving_or_calling_ai(): void
    {
        $lot = OilLot::factory()->create();
        $certificateRequest = $this->requestFor($lot);
        $lab = User::factory()->create(['role' => Role::Laboratory]);
        $this->actingAs($lab)->post(route('lab.requests.analyze', $certificateRequest), ['acidity' => -1, 'result' => 1])
            ->assertSessionHasErrors('acidity');
        $this->postJson(route('lab.requests.ai-explanation'), ['acidity' => -1, 'peroxide_value' => -2])
            ->assertUnprocessable()->assertJsonValidationErrors(['acidity', 'peroxide_value']);
        $this->assertDatabaseCount('lab_analyses', 0);
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_producer_and_lab_sidebar_navigation(): void
    {
        $producer = User::factory()->create(['role' => Role::Producer]);
        $this->actingAs($producer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Certificate Requests')
            ->assertSee(route('certification.producer.requests.index'));

        $lab = User::factory()->create(['role' => Role::Laboratory]);
        $this->actingAs($lab)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('View Certification Requests')
            ->assertSee(route('lab.requests.index'));
    }

    public function test_laboratory_can_modify_existing_request(): void
    {
        $lot = OilLot::factory()->create(['quality_grade' => OilQuality::ExtraVirgin]);
        $certificateRequest = $this->requestFor($lot);
        $lab = User::factory()->create(['role' => Role::Laboratory]);
        $this->actingAs($lab);

        // First analysis approved
        $this->post(route('lab.requests.analyze', $certificateRequest), [
            'acidity' => 0.45,
            'peroxide_value' => 8.5,
            'result' => 1,
            'notes' => 'Initial approved analysis',
        ])->assertSessionHasNoErrors();

        $this->assertSame('approved', $certificateRequest->fresh()->status);
        $certificate = Certificate::where('certificate_request_id', $certificateRequest->id)->first();
        $this->assertNotNull($certificate);
        $this->assertSame('active', $certificate->status);

        // Lab can open edit view
        $this->get(route('lab.requests.edit', $certificateRequest))
            ->assertOk()
            ->assertSee('Modify Certificate Request')
            ->assertSee('0.45');

        // Lab modifies request to rejected
        $this->put(route('lab.requests.update', $certificateRequest), [
            'acidity' => 1.5,
            'peroxide_value' => 25.0,
            'result' => 0,
            'notes' => 'Correction: exceeded acidity limit upon re-testing.',
        ])->assertRedirect(route('lab.requests.index'))->assertSessionHas('success');

        $certificateRequest->refresh();
        $this->assertSame('rejected', $certificateRequest->status);
        $this->assertEquals(1.5, $certificateRequest->labAnalysis->acidity);
        $this->assertSame('Correction: exceeded acidity limit upon re-testing.', $certificateRequest->labAnalysis->notes);
        $this->assertSame('revoked', $certificate->fresh()->status);

        // Lab re-modifies back to approved
        $this->put(route('lab.requests.update', $certificateRequest), [
            'acidity' => 0.35,
            'peroxide_value' => 6.2,
            'result' => 1,
            'notes' => 'Final confirmed analysis',
        ])->assertRedirect(route('lab.requests.index'))->assertSessionHas('success');

        $this->assertSame('approved', $certificateRequest->fresh()->status);
        $this->assertSame('active', $certificate->fresh()->status);
    }

    public function test_certificate_can_be_downloaded_as_pdf(): void
    {
        $lot = OilLot::factory()->create();
        $certificateRequest = $this->requestFor($lot, 'approved');
        $lab = User::factory()->create(['role' => Role::Laboratory]);
        LabAnalysis::create([
            'certificate_request_id' => $certificateRequest->id,
            'lab_user_id' => $lab->id,
            'analysis_date' => now(),
            'acidity' => 0.28,
            'peroxide_value' => 7.5,
            'result' => true,
            'notes' => 'Excellent quality',
        ]);
        $certificate = Certificate::create([
            'certificate_request_id' => $certificateRequest->id,
            'certificate_number' => 'CERT-PDF-TEST-123',
            'type' => 'Extra Virgin Olive Oil',
            'issue_date' => today(),
            'expiry_date' => today()->addYear(),
            'status' => 'active',
        ]);

        // Public page has download link
        $this->get(route('certificates.show', $certificate->certificate_number))
            ->assertOk()
            ->assertSee(route('certificates.pdf', $certificate->certificate_number));

        // PDF download route delivers PDF
        $response = $this->get(route('certificates.pdf', $certificate->certificate_number));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('Certificate-CERT-PDF-TEST-123.pdf', $response->headers->get('content-disposition'));
        $this->assertNotEmpty($response->getContent());
    }

    private function requestFor(OilLot $lot, string $status = 'pending'): CertificateRequest
    {
        return CertificateRequest::create([
            'oil_lot_id' => $lot->id, 'producer_user_id' => $lot->producer_user_id,
            'requested_at' => now(), 'status' => $status,
        ]);
    }
}
