<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Mail\ComplaintStatusUpdated;
use App\Mail\UrgentComplaintNotification;
use App\Models\Certification\Certificate;
use App\Models\Certification\CertificateRequest;
use App\Models\Consumer\AdminSuggestedResponse;
use App\Models\Consumer\Complaint;
use App\Models\Consumer\ConsumerNotification;
use App\Models\Consumer\Feedback;
use App\Models\Consumer\VerifiedPurchase;
use App\Models\Distribution\OilProduct;
use App\Models\User;
use App\Services\Consumer\MyTraceService;
use App\Services\Consumer\SlaDashboardService;
use Carbon\CarbonInterval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConsumerRecoveredFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.groq.api_key' => '']);
        Http::preventStrayRequests();
        Mail::fake();
    }

    private function user(Role $role = Role::Consumer): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function proof(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('receipt.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aU1cAAAAASUVORK5CYII='));
    }

    public function test_my_trace_shows_only_the_consumers_data_and_counts_all_unread_notifications(): void
    {
        $owner = $this->user();
        $other = $this->user();
        Feedback::factory()->create(['consumer_user_id' => $owner->id, 'comment' => 'Own tasting review']);
        Feedback::factory()->create(['consumer_user_id' => $other->id, 'comment' => 'Other private history']);
        Complaint::factory()->create(['consumer_user_id' => $owner->id, 'subject' => 'Own complaint history']);
        Complaint::factory()->create(['consumer_user_id' => $other->id, 'subject' => 'Other complaint history']);
        ConsumerNotification::factory()->count(25)->create(['consumer_user_id' => $owner->id, 'read' => false]);

        $this->get(route('my-trace.index'))->assertRedirect(route('login'));
        $this->actingAs($this->user(Role::Producer))->get(route('my-trace.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('my-trace.index'))->assertOk()
            ->assertSee('Own tasting review')->assertSee('Own complaint history')->assertSee('25 non lues')
            ->assertDontSee('Other private history')->assertDontSee('Other complaint history');
        $data = app(MyTraceService::class)->getConsumerDashboard($owner->id);
        $this->assertSame(1, $data['stats']['open_complaints']);
        $this->assertSame(25, $data['unread_notifications']);
    }

    public function test_notification_forms_work_and_cannot_change_another_users_notifications(): void
    {
        $owner = $this->user();
        $own = ConsumerNotification::factory()->create(['consumer_user_id' => $owner->id, 'read' => false]);
        $other = ConsumerNotification::factory()->create(['read' => false]);
        $this->actingAs($owner)->get(route('my-trace.notifications'))->assertOk();
        $this->patch(route('my-trace.notifications.read', $other))->assertForbidden();
        $this->from(route('my-trace.notifications'))->patch(route('my-trace.notifications.read', $own))->assertRedirect(route('my-trace.notifications'));
        $this->assertTrue($own->fresh()->read);
        $own->update(['read' => false]);
        $this->patchJson(route('my-trace.notifications.read-all'))->assertOk();
        $this->assertTrue($own->fresh()->read);
        $this->assertFalse($other->fresh()->read);
    }

    public function test_complaint_status_listener_sends_one_private_alert_and_reopening_resets_deadline(): void
    {
        $owner = $this->user();
        $admin = $this->user(Role::Admin);
        $complaint = Complaint::factory()->create(['consumer_user_id' => $owner->id]);
        $this->assertNotNull($complaint->sla_deadline);
        $url = route('admin.consumer.complaints.update', $complaint);
        $this->actingAs($admin)->patch($url, ['status' => 'resolved', 'admin_response' => 'Reviewed by admin.'])->assertRedirect();
        $this->assertDatabaseCount('consumer_notifications', 1);
        $this->assertDatabaseHas('consumer_notifications', ['consumer_user_id' => $owner->id, 'related_complaint_id' => $complaint->id, 'type' => 'complaint_status']);
        $this->patch($url, ['status' => 'resolved', 'admin_response' => 'Reviewed by admin.'])->assertRedirect();
        $this->assertDatabaseCount('consumer_notifications', 1);
        Mail::assertSentCount(1);
        $this->patch($url, ['status' => 'in_review'])->assertRedirect();
        $complaint->refresh();
        $this->assertSame(1, $complaint->reopen_count);
        $this->assertNull($complaint->resolved_at);
        $this->assertSame($admin->id, $complaint->admin_user_id);
        $this->assertTrue($complaint->sla_deadline->isFuture());
        Mail::assertSent(ComplaintStatusUpdated::class);
    }

    public function test_urgent_complaint_notifies_active_admins_and_renders_the_email(): void
    {
        $admin = $this->user(Role::Admin);
        $inactive = User::factory()->create(['role' => Role::Admin, 'is_active' => false]);
        $product = OilProduct::factory()->create();
        $this->actingAs($this->user())->post(route('complaints.store'), [
            'oil_product_id' => $product->id, 'subject' => 'Glass inside the bottle', 'description' => 'There is broken glass inside the cap.',
        ])->assertRedirect();
        $this->assertDatabaseHas('consumer_notifications', ['consumer_user_id' => $admin->id, 'type' => 'complaint_urgent']);
        $this->assertDatabaseMissing('consumer_notifications', ['consumer_user_id' => $inactive->id]);
        Mail::assertQueued(UrgentComplaintNotification::class, fn ($mail) => str_contains($mail->render(), 'Glass inside the bottle'));
        $this->actingAs($admin)->get(route('admin.consumer.sla.index'))->assertOk()->assertSee('Glass inside the bottle');
    }

    public function test_sla_metrics_handle_enums_deadlines_zero_hour_resolutions_and_period_filters(): void
    {
        $this->freezeTime();
        $admin = $this->user(Role::Admin);
        foreach ([0, 12, 60] as $hours) {
            Complaint::factory()->create(['status' => 'resolved', 'created_at' => now()->subHours(70), 'resolved_at' => now()->subHours(70 - $hours), 'admin_user_id' => $admin->id]);
        }
        Complaint::factory()->create(['status' => 'open', 'sla_deadline' => now()->subHour(), 'admin_user_id' => $admin->id]);
        Complaint::factory()->create(['status' => 'in_review', 'sla_deadline' => now()->addHour(), 'admin_user_id' => $admin->id]);
        Complaint::factory()->create(['status' => 'rejected', 'sla_deadline' => now()->subHour()]);
        Complaint::factory()->create(['status' => 'open', 'created_at' => now()->subDays(10)]);
        $metrics = app(SlaDashboardService::class)->getMetrics(CarbonInterval::days(7));
        $this->assertSame(6, $metrics['total_complaints']);
        $this->assertSame(3, $metrics['resolved_complaints']);
        $this->assertSame(1, $metrics['open_complaints']);
        $this->assertSame(1, $metrics['overdue_complaints']);
        $this->assertSame(12.0, $metrics['median_resolution_time_hours']);
        $this->assertSame(24.0, $metrics['avg_resolution_time_hours']);
        $this->assertCount(7, $metrics['trend']['labels']);
        $this->actingAs($this->user())->get(route('admin.consumer.sla.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.consumer.sla.index', ['period' => '7d']))->assertOk()->assertSee('7 jours');
        $this->get(route('admin.consumer.sla.index', ['period' => 'bad']))->assertSessionHasErrors('period');
    }

    public function test_share_cards_use_real_public_product_data_and_do_not_invent_certification(): void
    {
        $product = OilProduct::factory()->create(['name' => "Aymen's Oil <script>unsafe</script>"]);
        $this->get(route('trace.share', $product->slug))->assertOk()->assertSee(e($product->name), false)
            ->assertSee('Aucun certificat valide confirmé')->assertSee('<svg', false)->assertDontSee('<script>unsafe</script>', false);
        $this->getJson(route('trace.share.api', $product->slug))->assertOk()->assertJsonPath('verified', false)
            ->assertJsonPath('trace_url', route('trace.show', $product->slug));
        foreach ([OilProduct::factory()->hidden()->create(), OilProduct::factory()->archived()->create()] as $private) {
            $this->get(route('trace.share', $private->slug))->assertNotFound();
            $this->getJson(route('trace.share.api', $private->slug))->assertNotFound();
            $this->actingAs($this->user())->get(route('products.share', $private))->assertNotFound();
        }
    }

    public function test_purchase_proof_is_private_and_requires_admin_review_before_a_verified_badge(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $owner = $this->user();
        $feedback = Feedback::factory()->create(['consumer_user_id' => $owner->id]);
        $url = route('products.proof.upload', $feedback->oil_product_id);
        $this->actingAs($owner)->post($url, ['proof_type' => 'receipt', 'proof_file' => $this->proof()])->assertRedirect();
        $purchase = VerifiedPurchase::firstOrFail();
        Storage::disk('local')->assertExists($purchase->proof_path);
        Storage::disk('public')->assertMissing($purchase->proof_path);
        $this->assertNull($purchase->approved_at);
        $this->assertFalse($feedback->fresh()->hasVerifiedPurchase());
        $this->get(route('products.proof.show', $purchase))->assertOk();
        $this->actingAs($this->user())->get(route('products.proof.show', $purchase))->assertForbidden();
        $approval = route('admin.consumer.proofs.approve', $purchase);
        $version = hash('sha256', $purchase->proof_path);
        $this->patch($approval, ['proof_version' => $version])->assertForbidden();
        $admin = $this->user(Role::Admin);
        $this->actingAs($admin)->get(route('admin.consumer.proofs.index'))->assertOk()->assertSee('Awaiting review');
        $this->patch($approval, ['proof_version' => $version])->assertRedirect();
        $this->assertTrue($feedback->fresh()->hasVerifiedPurchase());
        $this->get(route('trace.show', $feedback->product->slug))->assertOk()->assertSee('proof reviewed by admin');
        $oldPath = $purchase->proof_path;
        $this->actingAs($owner)->post($url, ['proof_type' => 'invoice', 'proof_file' => $this->proof()])->assertRedirect();
        $purchase->refresh();
        $this->assertNull($purchase->approved_at);
        $this->assertFalse($feedback->fresh()->hasVerifiedPurchase());
        Storage::disk('local')->assertMissing($oldPath);
        $this->actingAs($admin)->patch($approval, ['proof_version' => $version])->assertSessionHasErrors('proof_version');
    }

    public function test_proof_validation_and_private_product_checks_run_on_the_server(): void
    {
        Storage::fake('local');
        $owner = $this->user();
        $product = OilProduct::factory()->create();
        $this->actingAs($owner)->post(route('products.proof.upload', $product), ['proof_type' => 'receipt'])->assertSessionHasErrors('proof_file');
        $this->post(route('products.proof.upload', $product), ['proof_type' => 'receipt', 'proof_file' => UploadedFile::fake()->createWithContent('attack.svg', '<svg></svg>')])->assertSessionHasErrors('proof_file');
        $this->post(route('products.proof.upload', OilProduct::factory()->hidden()->create()), ['proof_type' => 'receipt', 'proof_file' => $this->proof()])->assertNotFound();
        $this->assertDatabaseCount('verified_purchases', 0);
    }

    public function test_review_edit_history_is_recorded_for_both_update_entry_points(): void
    {
        $owner = $this->user();
        $feedback = Feedback::factory()->create(['consumer_user_id' => $owner->id, 'rating' => 5, 'comment' => 'Original review']);
        $this->actingAs($owner)->patch(route('feedback.update', $feedback), ['rating' => 3, 'comment' => 'Edited review'])->assertRedirect();
        $this->post(route('feedback.store', $feedback->oil_product_id), ['rating' => 4, 'comment' => 'Second edit'])->assertRedirect();
        $this->assertDatabaseCount('feedback_edits', 2);
        $this->assertDatabaseHas('feedback_edits', ['feedback_id' => $feedback->id, 'old_rating' => 5, 'new_rating' => 3, 'edited_by' => $owner->id]);
        $this->assertNotNull($feedback->fresh()->last_edited_at);
        $this->actingAs($this->user())->patch(route('feedback.update', $feedback), ['rating' => 1])->assertForbidden();
        $this->assertDatabaseCount('feedback_edits', 2);
        $this->actingAs($this->user(Role::Admin))->get(route('admin.consumer.feedback.index'))->assertOk()->assertSee('Original review')->assertSee('Second edit');
    }

    public function test_review_ai_drafts_support_tones_without_fake_fallback_or_auto_publication(): void
    {
        $feedback = Feedback::factory()->create(['comment' => 'Keep my original words']);
        $url = route('admin.consumer.feedback.ai-response', $feedback);
        $this->actingAs($this->user())->postJson($url)->assertForbidden();
        $this->actingAs($this->user(Role::Admin))->postJson($url)->assertStatus(503)->assertJsonPath('success', false);
        Http::assertNothingSent();
        config(['services.groq.api_key' => 'consumer-test-key']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'A friendly reply draft.']]]])]);
        $this->postJson($url, ['tone' => 'friendly'])->assertOk()->assertJsonPath('suggestion.tone', 'friendly');
        $suggestion = AdminSuggestedResponse::firstOrFail();
        $this->assertNull($suggestion->confidence);
        $this->assertSame('Keep my original words', $feedback->fresh()->comment);
        Mail::assertNothingSent();
        $this->postJson($url, ['tone' => 'untrusted-tone'])->assertUnprocessable();
    }

    public function test_new_valid_certificate_notifies_reviewers_once_without_overwriting_lab_work(): void
    {
        $product = OilProduct::factory()->create();
        $review = Feedback::factory()->create(['oil_product_id' => $product->id]);
        $hidden = Feedback::factory()->create(['oil_product_id' => $product->id, 'status' => 'hidden']);
        $request = CertificateRequest::create(['oil_lot_id' => $product->oil_lot_id, 'producer_user_id' => $product->created_by_user_id, 'status' => 'approved']);
        $certificate = Certificate::create(['certificate_request_id' => $request->id, 'certificate_number' => 'CERT-CONSUMER-RECOVERY', 'type' => 'Quality', 'issue_date' => today(), 'expiry_date' => today()->addYear(), 'status' => 'valid']);
        $this->assertDatabaseHas('consumer_notifications', ['consumer_user_id' => $review->consumer_user_id, 'type' => 'new_certificate']);
        $this->assertDatabaseMissing('consumer_notifications', ['consumer_user_id' => $hidden->consumer_user_id, 'type' => 'new_certificate']);
        $certificate->update(['pdf_url' => 'https://example.com/certificate.pdf']);
        $this->assertDatabaseCount('consumer_notifications', 1);
        $this->getJson(route('trace.share.api', $product->slug))->assertOk()->assertJsonPath('verified', true);
    }
}
