<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LabAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_ai_uses_a_private_header_and_safe_error_messages(): void
    {
        Http::preventStrayRequests();
        config(['services.gemini.key' => 'fake-lab-key', 'services.gemini.lab_model' => 'gemini-3.1-flash-lite']);
        $this->actingAs(User::factory()->create(['role' => Role::Laboratory]));
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Review these values with the laboratory.']]]]]])
            ->push(['error' => ['message' => 'private-provider-details']], 503)]);
        $this->postJson(route('lab.requests.ai-explanation'), ['acidity' => 0.3])->assertOk()
            ->assertJsonPath('explanation', 'Review these values with the laboratory.');
        $this->postJson(route('lab.requests.ai-explanation'), ['acidity' => 0.3])->assertOk()
            ->assertDontSee('private-provider-details')->assertDontSee('fake-lab-key');
        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'fake-lab-key') && ! str_contains($request->url(), 'fake-lab-key'));
    }
}
