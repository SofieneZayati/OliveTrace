<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Services\LabResultAIAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LabAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_ai_uses_a_private_header_and_safe_error_messages(): void
    {
        Http::preventStrayRequests();
        config([
            'services.lab_ai.key' => 'fake-lab-key', 'services.lab_ai.model' => 'gemini-3.1-flash-lite',
            'farm-assistant.provider' => 'gemini', 'farm-assistant.gemini_key' => 'fake-farm-key',
        ]);
        $this->actingAs(User::factory()->create(['role' => Role::Laboratory]));
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => 'Review these values with the laboratory.']]]]]])
            ->push(['error' => ['message' => 'private-provider-details']], 503)]);
        $this->postJson(route('lab.requests.ai-explanation'), ['acidity' => 0.3])->assertOk()
            ->assertJsonPath('explanation', 'Review these values with the laboratory.');
        $this->postJson(route('lab.requests.ai-explanation'), ['acidity' => 0.3])->assertOk()
            ->assertDontSee('private-provider-details')->assertDontSee('fake-lab-key');
        Http::assertSent(fn ($request) => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent'
            && $request->hasHeader('x-goog-api-key', 'fake-lab-key')
            && ! $request->hasHeader('Authorization')
            && ! str_contains($request->url(), 'fake-lab-key'));
        Http::assertSentCount(2);
    }

    public function test_missing_lab_key_does_not_fall_back_to_the_farm_key(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        config(['services.lab_ai.key' => null, 'farm-assistant.gemini_key' => 'fake-farm-key']);

        $this->assertStringContainsString('Configure the Gemini API key', LabResultAIAssistant::generateExplanation(0.3, 10, null));
        Http::assertNothingSent();
    }

    public function test_lab_explanation_renders_markdown_without_executable_html(): void
    {
        Http::preventStrayRequests();
        config(['services.lab_ai.key' => 'fake-lab-key']);
        $this->actingAs(User::factory()->create(['role' => Role::Laboratory]));
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => "### Results\n\n**Acidity:** high\n\n- Review sample\n\n<script>alert(1)</script>\n\n[Unsafe](javascript:alert(1))"]]]]],
        ])]);

        $response = $this->postJson(route('lab.requests.ai-explanation'), ['acidity' => 12])->assertOk();
        $html = $response->json('explanation_html');
        $this->assertStringContainsString('<h3>Results</h3>', $html);
        $this->assertStringContainsString('<strong>Acidity:</strong>', $html);
        $this->assertStringContainsString('<li>Review sample</li>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
    }

    public function test_incomplete_or_empty_lab_responses_are_not_displayed_as_explanations(): void
    {
        Http::preventStrayRequests();
        config(['services.lab_ai.key' => 'fake-lab-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['finishReason' => 'MAX_TOKENS', 'content' => ['parts' => [['text' => 'Partial answer']]]]]])
            ->push(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => []]]]])]);

        $this->assertSame('Unable to parse AI response.', LabResultAIAssistant::generateExplanation(0.3, 10, null));
        $this->assertSame('Unable to parse AI response.', LabResultAIAssistant::generateExplanation(0.3, 10, null));
    }
}
