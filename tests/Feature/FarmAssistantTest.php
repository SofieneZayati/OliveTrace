<?php

namespace Tests\Feature;

use App\Entities\Production\Farm;
use App\Enums\Role;
use App\Models\User;
use Database\Factories\Production\FarmFactory;
use Database\Factories\Production\ProducerProfileFactory;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Facades\Http;
use Tests\ProductionTestCase;

class FarmAssistantTest extends ProductionTestCase
{
    private Farm $farm;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $user = User::factory()->create(['role' => Role::Producer]);
        $profile = (new ProducerProfileFactory)->make($user->id, ['phone' => 'private-phone-marker', 'address' => 'private-address-marker']);
        $this->farm = (new FarmFactory)->make($profile, ['name' => 'Private farm name', 'description' => 'private-notes-marker', 'gpsLat' => '34.1234567']);
        $manager = app(EntityManagerInterface::class);
        $manager->persist($profile);
        $manager->persist($this->farm);
        $manager->flush();
        $this->actingAs($user);
        config(['farm-assistant.provider' => 'openai', 'farm-assistant.openai_key' => 'fake-test-key', 'farm-assistant.model' => 'test-model']);
    }

    private function advice(): array
    {
        return ['summary' => 'Review water and soil practices.', 'suggestions' => ['Discuss mulching with a local agronomist.'], 'flags' => ['Some farm information is missing.'], 'limitations' => 'Advisory only; the producer must review every suggestion.'];
    }

    private function openaiResponse(array $advice): array
    {
        return ['status' => 'completed', 'output' => [
            ['type' => 'reasoning', 'summary' => []],
            ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($advice)]]],
        ]];
    }

    private function assertFarmUnchanged(): void
    {
        $this->assertDatabaseHas('farms', ['id' => $this->farm->id, 'name' => 'Private farm name', 'status' => 'active', 'description' => 'private-notes-marker']);
    }

    public function test_missing_configuration_keeps_crud_available_without_a_network_call(): void
    {
        config(['farm-assistant.openai_key' => null]);
        $this->post(route('producer.farms.advice', $this->farm->id))->assertOk()->assertSee('not configured yet');
        Http::assertNothingSent();
        $this->get(route('producer.farms.index'))->assertOk();
        $this->assertFarmUnchanged();
    }

    public function test_openai_uses_structured_output_with_minimum_facts_and_escapes_generated_text(): void
    {
        $advice = $this->advice();
        $advice['summary'] = '<script>alert("unsafe")</script>';
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->openaiResponse($advice))]);
        $this->post(route('producer.farms.advice', $this->farm->id))->assertOk()
            ->assertSee($advice['summary'])->assertDontSee($advice['summary'], false)->assertSee($advice['suggestions'][0]);
        Http::assertSent(function ($request) {
            $data = $request->data();
            $facts = json_decode($data['input'], true);
            $this->assertSame('Sfax', $facts['governorate']);
            $this->assertSame('Chemlali', $facts['olive_variety']);
            $this->assertFalse($data['store']);
            $this->assertTrue($data['text']['format']['strict']);
            foreach (['private-phone-marker', 'private-address-marker', 'private-notes-marker', '34.1234567', 'Private farm name'] as $privateValue) {
                $this->assertStringNotContainsString($privateValue, $data['input']);
            }

            return $request->url() === 'https://api.openai.com/v1/responses';
        });
        $this->assertFarmUnchanged();
    }

    public function test_provider_failure_malformed_output_and_refusal_leave_farm_records_intact(): void
    {
        $responses = [
            Http::response(['error' => ['message' => 'provider-private-details']], 503),
            Http::response(['status' => 'incomplete', 'output' => []]),
            Http::response(['status' => 'completed', 'output' => [['content' => [['type' => 'refusal', 'refusal' => 'No advice']]]]]),
            Http::response($this->openaiResponse(['summary' => 'Invalid: required fields missing'])),
        ];
        foreach ($responses as $response) {
            Http::fake(['https://api.openai.com/v1/responses' => $response]);
            $this->post(route('producer.farms.advice', $this->farm->id))->assertOk()->assertSee('temporarily unavailable')->assertDontSee('provider-private-details');
            $this->assertFarmUnchanged();
        }
        $this->get(route('producer.farms.show', $this->farm->id))->assertOk();
    }

    public function test_ollama_supports_local_non_streaming_structured_advice(): void
    {
        config(['farm-assistant.provider' => 'ollama', 'farm-assistant.ollama_url' => 'http://127.0.0.1:11434', 'farm-assistant.model' => 'local-test-model']);
        Http::fake(['http://127.0.0.1:11434/api/chat' => Http::response(['done' => true, 'message' => ['content' => json_encode($this->advice())]])]);
        $this->post(route('producer.farms.advice', $this->farm->id))->assertOk()->assertSee('Discuss mulching');
        Http::assertSent(fn ($request) => $request->data()['stream'] === false && $request->data()['format']['type'] === 'object');
        $this->assertFarmUnchanged();
    }

    public function test_advice_requests_are_rate_limited(): void
    {
        config(['farm-assistant.openai_key' => null]);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('producer.farms.advice', $this->farm->id))->assertOk();
        }
        $this->post(route('producer.farms.advice', $this->farm->id))->assertStatus(429);
        Http::assertNothingSent();
        $this->assertFarmUnchanged();
    }
}
