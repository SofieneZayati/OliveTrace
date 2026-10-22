<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Mail\ComplaintStatusUpdated;
use App\Models\Consumer\Complaint;
use App\Models\Consumer\Feedback;
use App\Models\Distribution\OilProduct;
use App\Models\Production\OilLot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConsumerAdvancedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.groq.api_key' => '', 'services.groq.model' => 'test-model']);
        Http::preventStrayRequests();
        Mail::fake();
    }

    private function user(Role $role = Role::Consumer): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_public_search_finds_slug_id_name_brand_and_lot_number(): void
    {
        $product = OilProduct::factory()->create(['name' => 'Consumer Search Oil', 'brand' => 'Aymen Test']);
        $lot = OilLot::findOrFail($product->oil_lot_id);
        $lot->update(['lot_number' => 'LOT-SEARCH-UNIQUE']);

        $this->get(route('trace.search'))->assertOk()->assertSee('Scanner un QR code');
        foreach ([$product->slug, (string) $product->id, 'Consumer Search', 'Aymen Test', 'LOT-SEARCH-UNIQUE'] as $query) {
            $this->get(route('trace.search.query', ['q' => $query]))
                ->assertRedirect(route('trace.show', $product->slug));
        }
        $this->get(route('trace.show', $product->slug))->assertOk()->assertDontSee('@if(', false);
    }

    public function test_hidden_match_does_not_block_a_public_product_in_the_same_lot(): void
    {
        $hidden = OilProduct::factory()->hidden()->create(['name' => 'Shared Search Oil']);
        $public = OilProduct::factory()->create(['name' => 'Shared Search Oil', 'oil_lot_id' => $hidden->oil_lot_id]);
        $lot = OilLot::findOrFail($public->oil_lot_id);
        $lot->update(['lot_number' => 'LOT-PUBLIC-SEARCH']);

        foreach (['Shared Search Oil', 'LOT-PUBLIC-SEARCH'] as $query) {
            $this->get(route('trace.search.query', ['q' => $query]))
                ->assertRedirect(route('trace.show', $public->slug));
        }
        $this->get(route('trace.search.query', ['q' => $hidden->slug]))
            ->assertRedirect(route('trace.search'))->assertSessionHas('error');
    }

    public function test_search_validates_query_on_the_server_and_escapes_error_text(): void
    {
        foreach ([[], ['not-a-string'], str_repeat('x', 161)] as $query) {
            $this->get(route('trace.search.query', ['q' => $query]))->assertSessionHasErrors('q');
        }
        $this->followingRedirects()->get(route('trace.search.query', ['q' => '<script>alert(1)</script>']))
            ->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_consumer_dashboard_is_personal_and_recommends_only_public_products(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $public = OilProduct::factory()->create(['name' => 'Public Recommendation']);
        $hidden = OilProduct::factory()->hidden()->create(['name' => 'Secret Recommendation']);
        Feedback::factory()->create(['consumer_user_id' => $owner->id, 'oil_product_id' => $public->id, 'rating' => 4]);
        Feedback::factory()->create(['consumer_user_id' => $other->id, 'oil_product_id' => $hidden->id, 'rating' => 5]);
        Complaint::factory()->create(['consumer_user_id' => $owner->id, 'subject' => 'My private complaint']);
        Complaint::factory()->create(['consumer_user_id' => $other->id, 'subject' => 'Someone else private complaint']);

        $this->actingAs($owner)->get(route('dashboard'))->assertOk()
            ->assertSee('Mes avis')->assertSee('Public Recommendation')->assertSee('My private complaint')
            ->assertDontSee('Secret Recommendation')->assertDontSee('Someone else private complaint');
    }

    public function test_evidence_upload_is_private_and_visible_only_to_owner_or_admin(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $owner = $this->user();
        $product = OilProduct::factory()->create();

        $this->actingAs($owner)->post(route('complaints.store'), [
            'oil_product_id' => $product->id, 'subject' => 'Leaking bottle',
            'description' => 'The packaging arrived damaged.',
            'evidence_photo' => UploadedFile::fake()->createWithContent('evidence.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aU1cAAAAASUVORK5CYII=')),
        ])->assertRedirect();
        $complaint = Complaint::firstOrFail();
        Storage::disk('local')->assertExists($complaint->evidence_photo);
        Storage::disk('public')->assertMissing($complaint->evidence_photo);
        $url = route('complaints.evidence', $complaint);
        $this->get(route('complaints.show', $complaint))->assertOk()->assertSee($url);
        $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($this->user())->get($url)->assertForbidden();
        $this->actingAs($this->user(Role::Admin))->get($url)->assertOk();
        $this->actingAs($this->user())->get(route('complaints.evidence', Complaint::factory()->create()))->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertRedirect(route('login'));
    }

    public function test_evidence_validation_rejects_non_images_and_oversized_files(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user());
        $product = OilProduct::factory()->create();
        foreach ([UploadedFile::fake()->createWithContent('payload.svg', '<svg></svg>'), UploadedFile::fake()->create('large.jpg', 2049, 'image/jpeg')] as $photo) {
            $this->post(route('complaints.store'), [
                'oil_product_id' => $product->id, 'subject' => 'Evidence test',
                'description' => 'Invalid evidence must not be saved.', 'evidence_photo' => $photo,
            ])->assertSessionHasErrors('evidence_photo');
        }
        $this->assertDatabaseCount('complaints', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('complaints'));
    }

    public function test_ai_draft_requires_an_active_admin(): void
    {
        $complaint = Complaint::factory()->create();
        $url = route('admin.consumer.complaints.ai-reply', $complaint);
        $this->postJson($url)->assertUnauthorized();
        $this->actingAs($this->user())->postJson($url)->assertForbidden();
        $admin = $this->user(Role::Admin);
        $admin->forceFill(['is_active' => false])->save();
        $this->actingAs($admin)->postJson($url)->assertRedirect(route('login'));
        Http::assertNothingSent();
    }

    public function test_ai_draft_is_saved_for_review_without_sending_or_changing_the_decision(): void
    {
        config(['services.groq.api_key' => 'consumer-test-key', 'services.groq.model' => 'openai/gpt-oss-120b']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Please review this draft.']]]])]);
        $complaint = Complaint::factory()->create(['description' => 'Packaging damaged', 'evidence_photo' => 'complaints/private.jpg']);

        $this->actingAs($this->user(Role::Admin))->postJson(route('admin.consumer.complaints.ai-reply', $complaint))
            ->assertOk()->assertJsonPath('draft', 'Please review this draft.');
        $complaint->refresh();
        $this->assertSame('Please review this draft.', $complaint->ai_draft_reply);
        $this->assertNull($complaint->admin_response);
        $this->assertSame('open', $complaint->status->value);
        Mail::assertNothingSent();
        Http::assertSent(function ($request) use ($complaint) {
            $payload = json_encode($request->data());

            return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
                && $request['model'] === 'openai/gpt-oss-120b'
                && $request['max_completion_tokens'] >= 1536
                && $request['reasoning_effort'] === 'low'
                && $request['include_reasoning'] === false
                && ! isset($request['max_tokens'])
                && str_contains($payload, 'Packaging damaged')
                && ! str_contains($payload, $complaint->consumer->email)
                && ! str_contains($payload, 'complaints/private.jpg');
        });
    }

    public function test_ai_without_a_key_fails_gracefully_without_network_calls(): void
    {
        $this->actingAs($this->user(Role::Admin))
            ->postJson(route('admin.consumer.complaints.ai-reply', Complaint::factory()->create()))->assertStatus(503);
        $this->postJson(route('admin.consumer.feedback.summary'))->assertStatus(503);
        Http::assertNothingSent();
    }

    public static function invalidProviderResponses(): array
    {
        return [
            'provider unavailable' => [503, ['error' => ['message' => 'Unavailable']]],
            'missing content' => [200, []],
            'empty content' => [200, ['choices' => [['message' => ['content' => ' ']]]]],
            'non text content' => [200, ['choices' => [['message' => ['content' => ['unexpected']]]]]],
            'oversized content' => [200, ['choices' => [['message' => ['content' => str_repeat('x', 6001)]]]]],
        ];
    }

    #[DataProvider('invalidProviderResponses')]
    public function test_ai_rejects_invalid_provider_output(int $status, array $body): void
    {
        config(['services.groq.api_key' => 'consumer-test-key']);
        Http::fake(['api.groq.com/*' => Http::response($body, $status)]);
        $complaint = Complaint::factory()->create(['ai_draft_reply' => 'Previous draft']);
        Feedback::factory()->create();
        $this->actingAs($this->user(Role::Admin))->postJson(route('admin.consumer.complaints.ai-reply', $complaint))->assertStatus(503);
        $this->postJson(route('admin.consumer.feedback.summary'))->assertStatus(503);
        $this->assertSame('Previous draft', $complaint->fresh()->ai_draft_reply);
    }

    public function test_review_summary_uses_visible_reviews_only_and_is_admin_only(): void
    {
        config(['services.groq.api_key' => 'consumer-test-key']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Visible reviews summary.']]]])]);
        Feedback::factory()->create(['comment' => 'Visible review marker']);
        Feedback::factory()->create(['comment' => 'Hidden review marker', 'status' => 'hidden']);
        $url = route('admin.consumer.feedback.summary');
        $this->actingAs($this->user())->postJson($url)->assertForbidden();
        $this->actingAs($this->user(Role::Admin))->postJson($url)->assertOk()->assertJsonPath('summary', 'Visible reviews summary.');
        Http::assertSent(fn ($request) => str_contains(json_encode($request->data()), 'Visible review marker')
            && ! str_contains(json_encode($request->data()), 'Hidden review marker'));
    }

    public function test_ai_requests_are_rate_limited(): void
    {
        $this->actingAs($this->user(Role::Admin));
        $url = route('admin.consumer.complaints.ai-reply', Complaint::factory()->create());
        for ($i = 0; $i < 6; $i++) {
            $this->postJson($url)->assertStatus(503);
        }
        $this->postJson($url)->assertStatus(429);
        Http::assertNothingSent();
    }

    public function test_status_email_contains_reviewed_response_and_repeated_saves_do_not_resend(): void
    {
        $complaint = Complaint::factory()->create();
        $url = route('admin.consumer.complaints.update', $complaint);
        $data = ['status' => 'resolved', 'admin_response' => 'We have reviewed your complaint.'];
        $this->actingAs($this->user(Role::Admin))->patch($url, $data)->assertRedirect();
        Mail::assertSent(ComplaintStatusUpdated::class, function ($mail) use ($complaint) {
            return $mail->hasTo($complaint->consumer->email)
                && $mail->newStatus === 'resolved'
                && str_contains($mail->render(), 'We have reviewed your complaint.')
                && str_contains($mail->render(), route('complaints.show', $complaint));
        });
        $this->patch($url, $data)->assertRedirect();
        Mail::assertSentCount(1);
    }
}
