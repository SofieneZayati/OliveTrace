<?php

namespace Tests\Feature;

use App\Contracts\ProductRatingSummary;
use App\Enums\Role;
use App\Enums\ShipmentStatus;
use App\Models\Consumer\Complaint;
use App\Models\Consumer\Feedback;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use App\Models\User;
use App\Services\Consumer\FeedbackRatingSummary;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConsumerModuleTest extends TestCase
{
    use RefreshDatabase;

    private function consumer(): User
    {
        return User::factory()->create(['role' => Role::Consumer]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    public function test_guest_gets_friendly_page_for_unknown_trace(): void
    {
        $this->get('/trace/unknown-slug-123')->assertNotFound()->assertSee('not available yet');
    }

    public function test_trace_page_composes_product_feedback_and_graceful_missing_sections(): void
    {
        $product = OilProduct::factory()->create(['name' => 'Chemlali Gold 750ml']);
        $consumer = $this->consumer();
        Feedback::factory()->create(['oil_product_id' => $product->id, 'consumer_user_id' => $consumer->id, 'rating' => 5, 'comment' => 'Superb oil!']);

        $this->get('/trace/'.$product->slug)->assertOk()
            ->assertSee('Chemlali Gold 750ml')
            ->assertSee('Superb oil!')
            ->assertSee('No harvest information available')
            ->assertSee('No certificate available')
            ->assertSee('No shipment recorded');
    }

    public function test_consumer_can_publish_then_update_a_single_review_per_product(): void
    {
        $consumer = $this->consumer();
        $product = OilProduct::factory()->create();

        $this->actingAs($consumer)->post('/products/'.$product->id.'/feedback', ['rating' => 5, 'comment' => 'Excellent fruity oil!'])
            ->assertRedirect('/trace/'.$product->id);
        $this->assertDatabaseHas('feedback', ['oil_product_id' => $product->id, 'consumer_user_id' => $consumer->id, 'rating' => 5]);

        // Submitting again updates the same row instead of duplicating it.
        $this->actingAs($consumer)->post('/products/'.$product->id.'/feedback', ['rating' => 4, 'comment' => 'Still very good.'])
            ->assertRedirect('/trace/'.$product->id);
        $this->assertSame(1, Feedback::where('oil_product_id', $product->id)->where('consumer_user_id', $consumer->id)->count());
        $this->assertDatabaseHas('feedback', ['oil_product_id' => $product->id, 'consumer_user_id' => $consumer->id, 'rating' => 4]);

        $feedback = Feedback::firstOrFail();
        $this->assertNotNull($feedback->ai_category);
        $this->assertNotNull($feedback->ai_sentiment);
        $this->assertNotNull($feedback->ai_priority);
    }

    public function test_review_rating_is_validated_between_1_and_5(): void
    {
        $consumer = $this->consumer();

        $this->actingAs($consumer)->post('/products/1/feedback', ['rating' => 6])->assertSessionHasErrors('rating');
        $this->actingAs($consumer)->post('/products/1/feedback', ['rating' => 0])->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_guests_and_non_consumers_cannot_review(): void
    {
        $this->post('/products/1/feedback', ['rating' => 5])->assertRedirect('/login');

        $producer = User::factory()->create(['role' => Role::Producer]);
        $this->actingAs($producer)->post('/products/1/feedback', ['rating' => 5])->assertForbidden();
    }

    public function test_consumer_can_edit_and_delete_only_own_review(): void
    {
        $owner = $this->consumer();
        $other = $this->consumer();
        $feedback = Feedback::factory()->for(OilProduct::factory(), 'product')->create(['consumer_user_id' => $owner->id]);

        $this->actingAs($other)->get(route('feedback.edit', $feedback))->assertForbidden();
        $this->actingAs($other)->delete(route('feedback.destroy', $feedback))->assertForbidden();
        $this->assertModelExists($feedback);

        $this->actingAs($owner)->get(route('feedback.edit', $feedback))->assertOk();
        $this->actingAs($owner)->patch(route('feedback.update', $feedback), ['rating' => 2, 'comment' => 'Changed my mind.'])
            ->assertRedirect('/trace/'.$feedback->oil_product_id);
        $this->assertDatabaseHas('feedback', ['id' => $feedback->id, 'rating' => 2]);

        $this->actingAs($owner)->delete(route('feedback.destroy', $feedback))->assertRedirect('/trace/'.$feedback->oil_product_id);
        $this->assertDatabaseMissing('feedback', ['id' => $feedback->id]);
    }

    public function test_consumer_can_submit_and_follow_a_complaint(): void
    {
        $consumer = $this->consumer();
        $product = OilProduct::factory()->create();

        $this->actingAs($consumer)->get(route('complaints.index'))->assertOk();
        $this->actingAs($consumer)->get(route('complaints.create'))->assertOk();
        $this->actingAs($consumer)->post(route('complaints.store'), ['oil_product_id' => $product->id])
            ->assertSessionHasErrors(['subject', 'description']);

        $this->actingAs($consumer)->post(route('complaints.store'), [
            'oil_product_id' => $product->id, 'subject' => 'Bottle arrived leaking', 'description' => 'The cap was loose and oil leaked in the box.',
        ])->assertRedirect(route('complaints.show', Complaint::firstOrFail()));

        $complaint = Complaint::firstOrFail();
        $this->assertSame('open', $complaint->status->value);
        $this->assertNotNull($complaint->ai_category);

        $this->actingAs($consumer)->get(route('complaints.show', $complaint))->assertOk()->assertSee('Bottle arrived leaking');
    }

    public function test_complaints_are_private_between_owner_and_admin(): void
    {
        $owner = $this->consumer();
        $other = $this->consumer();
        $complaint = Complaint::factory()->create(['consumer_user_id' => $owner->id]);

        $this->actingAs($other)->get(route('complaints.show', $complaint))->assertForbidden();
        $this->actingAs($this->admin())->get(route('complaints.show', $complaint))->assertForbidden(); // consumer route, admin uses back office
    }

    public function test_admin_moderates_reviews_and_complaints(): void
    {
        $admin = $this->admin();
        $feedback = Feedback::factory()->create();
        $complaint = Complaint::factory()->create();

        $this->actingAs($admin)->get(route('admin.consumer.feedback.index'))->assertOk()->assertSee('Moderate reviews');
        $this->actingAs($admin)->patch(route('admin.consumer.feedback.update', $feedback), ['status' => 'hidden'])
            ->assertRedirect(route('admin.consumer.feedback.index'));
        $this->assertSame('hidden', $feedback->fresh()->status->value);

        $this->actingAs($admin)->get(route('admin.consumer.complaints.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.consumer.complaints.show', $complaint))->assertOk()->assertSee('Assistant suggestion');

        $this->actingAs($admin)->patch(route('admin.consumer.complaints.update', $complaint), ['status' => 'open'])
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)->patch(route('admin.consumer.complaints.update', $complaint), [
            'status' => 'resolved', 'admin_response' => 'Replacement bottle shipped.',
        ])->assertRedirect(route('admin.consumer.complaints.show', $complaint));
        $complaint->refresh();
        $this->assertSame('resolved', $complaint->status->value);
        $this->assertSame('Replacement bottle shipped.', $complaint->admin_response);
        $this->assertNotNull($complaint->resolved_at);
    }

    public function test_hidden_and_archived_products_have_no_public_trace(): void
    {
        $hidden = OilProduct::factory()->hidden()->create();
        $archived = OilProduct::factory()->archived()->create();

        $this->get('/trace/'.$hidden->slug)->assertNotFound();
        $this->get('/trace/'.$archived->slug)->assertNotFound();

        $consumer = $this->consumer();
        $this->actingAs($consumer)->post('/products/'.$hidden->id.'/feedback', ['rating' => 5])->assertNotFound();
        $this->actingAs($consumer)->post(route('complaints.store'), [
            'oil_product_id' => $hidden->id, 'subject' => 'Hidden product', 'description' => 'Should be rejected.',
        ])->assertNotFound();
    }

    public function test_complaint_form_lists_visible_products_and_trace_shows_star_widget(): void
    {
        $product = OilProduct::factory()->create(['name' => 'Chemlali Gold 750ml']);
        $consumer = $this->consumer();

        $this->actingAs($consumer)->get(route('complaints.create'))->assertOk()
            ->assertSee('Select the product')
            ->assertSee('Chemlali Gold 750ml');

        $this->actingAs($consumer)->get('/trace/'.$product->slug)->assertOk()
            ->assertSee('Tap a star to rate', false)
            ->assertSee('name="rating"', false);
    }

    public function test_trace_page_links_certificate_document_when_available(): void
    {
        Schema::create('certificate_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('oil_lot_id');
            $table->string('status', 30)->default('approved');
        });
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('certificate_request_id');
            $table->string('certificate_number')->nullable();
            $table->string('type')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 30)->nullable();
            $table->string('pdf_url')->nullable();
        });

        $product = OilProduct::factory()->create();
        $lotId = (int) DB::table('oil_lots')->where('id', $product->oil_lot_id)->value('id');
        $requestId = DB::table('certificate_requests')->insertGetId(['oil_lot_id' => $lotId, 'status' => 'approved']);
        DB::table('certificates')->insert([
            'certificate_request_id' => $requestId, 'certificate_number' => 'CERT-2026-001',
            'type' => 'Organic', 'issue_date' => now()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(), 'status' => 'valid',
            'pdf_url' => 'https://example.com/certificates/CERT-2026-001.pdf',
        ]);

        $this->get('/trace/'.$product->slug)->assertOk()
            ->assertSee('Verified')
            ->assertSee('View certificate document')
            ->assertSee('https://example.com/certificates/CERT-2026-001.pdf', false);
    }

    public function test_trace_page_shows_transport_footprint_and_product_name_on_complaints(): void
    {
        $product = OilProduct::factory()->create(['name' => 'Chemlali Gold 750ml']);
        Shipment::factory()->delivered()->create(['oil_product_id' => $product->id, 'co2_estimate' => '12.50']);
        Shipment::factory()->create(['oil_product_id' => $product->id, 'status' => ShipmentStatus::Cancelled, 'co2_estimate' => '99.99']);

        $this->get('/trace/'.$product->slug)->assertOk()
            ->assertSee('12.50')
            ->assertSee('Estimated transport footprint')
            ->assertDontSee('99.99');

        $consumer = $this->consumer();
        $this->actingAs($consumer)->post(route('complaints.store'), [
            'oil_product_id' => $product->id, 'subject' => 'Leaking bottle', 'description' => 'Oil leaked in the box.',
        ])->assertRedirect();

        $this->actingAs($consumer)->get(route('complaints.index'))->assertOk()->assertSee('Chemlali Gold 750ml');
    }

    public function test_my_complaints_alias_redirects_to_complaints(): void
    {
        $this->get('/my-complaints')->assertRedirect('/complaints');
        $this->followingRedirects()->get('/my-complaints')->assertSee('Log in');
        $this->actingAs($this->consumer())->get('/my-complaints')->assertRedirect(route('complaints.index'));
    }

    public function test_product_rating_summary_feeds_hana_catalog_contract(): void
    {
        $ratings = app(ProductRatingSummary::class);
        $this->assertInstanceOf(FeedbackRatingSummary::class, $ratings);

        $product = OilProduct::factory()->create();
        $this->assertNull($ratings->summary($product->id));

        $consumer = $this->consumer();
        Feedback::factory()->create(['oil_product_id' => $product->id, 'consumer_user_id' => $consumer->id, 'rating' => 5]);
        Feedback::factory()->create(['oil_product_id' => $product->id, 'consumer_user_id' => $this->consumer()->id, 'rating' => 3]);

        // Fresh resolution, like a new request: summaries are snapshotted per request.
        $summary = app(ProductRatingSummary::class)->summary($product->id);
        $this->assertSame(4.0, $summary->average);
        $this->assertSame(2, $summary->count);
    }

    public function test_consumer_cannot_close_their_own_complaint(): void
    {
        $consumer = $this->consumer();

        $this->actingAs($consumer)->patch(route('admin.consumer.complaints.update', Complaint::factory()->create()), [
            'status' => 'resolved',
        ])->assertForbidden();
    }
}
