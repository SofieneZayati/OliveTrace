<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Consumer\Complaint;
use App\Models\Consumer\Feedback;
use App\Models\User;
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
        // Minimal stand-in for Hana's future oil_products table (contract columns only).
        Schema::create('oil_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('qr_token')->nullable();
        });
        $productId = DB::table('oil_products')->insertGetId(['name' => 'Chemlali Gold 750ml', 'qr_token' => 'LOT-2026-001']);
        $consumer = $this->consumer();
        Feedback::factory()->create(['oil_product_id' => $productId, 'consumer_user_id' => $consumer->id, 'rating' => 5, 'comment' => 'Superb oil!']);

        $this->get('/trace/LOT-2026-001')->assertOk()
            ->assertSee('Chemlali Gold 750ml')
            ->assertSee('Superb oil!')
            ->assertSee('No harvest information available')
            ->assertSee('No certificate available')
            ->assertSee('No shipment recorded');

        Schema::dropIfExists('oil_products');
    }

    public function test_consumer_can_publish_then_update_a_single_review_per_product(): void
    {
        $consumer = $this->consumer();

        $this->actingAs($consumer)->post('/products/1/feedback', ['rating' => 5, 'comment' => 'Excellent fruity oil!'])
            ->assertRedirect('/trace/1');
        $this->assertDatabaseHas('feedback', ['oil_product_id' => 1, 'consumer_user_id' => $consumer->id, 'rating' => 5]);

        // Submitting again updates the same row instead of duplicating it.
        $this->actingAs($consumer)->post('/products/1/feedback', ['rating' => 4, 'comment' => 'Still very good.'])
            ->assertRedirect('/trace/1');
        $this->assertSame(1, Feedback::where('oil_product_id', 1)->where('consumer_user_id', $consumer->id)->count());
        $this->assertDatabaseHas('feedback', ['oil_product_id' => 1, 'consumer_user_id' => $consumer->id, 'rating' => 4]);

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
        $feedback = Feedback::factory()->create(['oil_product_id' => 1, 'consumer_user_id' => $owner->id]);

        $this->actingAs($other)->get(route('feedback.edit', $feedback))->assertForbidden();
        $this->actingAs($other)->delete(route('feedback.destroy', $feedback))->assertForbidden();
        $this->assertModelExists($feedback);

        $this->actingAs($owner)->get(route('feedback.edit', $feedback))->assertOk();
        $this->actingAs($owner)->patch(route('feedback.update', $feedback), ['rating' => 2, 'comment' => 'Changed my mind.'])
            ->assertRedirect('/trace/1');
        $this->assertDatabaseHas('feedback', ['id' => $feedback->id, 'rating' => 2]);

        $this->actingAs($owner)->delete(route('feedback.destroy', $feedback))->assertRedirect('/trace/1');
        $this->assertDatabaseMissing('feedback', ['id' => $feedback->id]);
    }

    public function test_consumer_can_submit_and_follow_a_complaint(): void
    {
        $consumer = $this->consumer();

        $this->actingAs($consumer)->get(route('complaints.index'))->assertOk();
        $this->actingAs($consumer)->get(route('complaints.create'))->assertOk();
        $this->actingAs($consumer)->post(route('complaints.store'), ['oil_product_id' => 1])
            ->assertSessionHasErrors(['subject', 'description']);

        $this->actingAs($consumer)->post(route('complaints.store'), [
            'oil_product_id' => 1, 'subject' => 'Bottle arrived leaking', 'description' => 'The cap was loose and oil leaked in the box.',
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

    public function test_consumer_cannot_close_their_own_complaint(): void
    {
        $consumer = $this->consumer();

        $this->actingAs($consumer)->patch(route('admin.consumer.complaints.update', Complaint::factory()->create()), [
            'status' => 'resolved',
        ])->assertForbidden();
    }
}
