<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Distribution\OilProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_generate_a_description_from_the_language_api_without_saving_it(): void
    {
        $producer = User::factory()->state(['role' => Role::Producer])->create();
        $product = OilProduct::factory()->create(['created_by_user_id' => $producer->id]);
        $draft = 'Nom marque volume date '.str_repeat('description chaleureuse vérifiable ', 20);
        config([
            'services.product_description.key' => 'test-key',
            'services.product_description.model' => 'test-model',
            'services.product_description.endpoint' => 'https://language.test/models',
        ]);
        Http::fake([
            'https://language.test/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => $draft]]]]],
            ]),
        ]);

        $this->actingAs($producer)
            ->postJson(route('producer.products.description.generate', $product))
            ->assertOk()
            ->assertJson(['description' => trim($draft)]);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'test-model:generateContent')
            && str_contains($request['contents'][0]['parts'][0]['text'], $product->name)
            && str_contains($request['contents'][0]['parts'][0]['text'], $product->brand));
        $this->assertNull($product->fresh()->description);
    }

    public function test_unconfigured_or_failed_api_returns_a_grounded_fallback(): void
    {
        $producer = User::factory()->state(['role' => Role::Producer])->create();
        $product = OilProduct::factory()->create(['created_by_user_id' => $producer->id]);

        config([
            'services.product_description.key' => '',
            'services.product_description.endpoint' => 'https://language.test/models',
        ]);
        $this->actingAs($producer)
            ->postJson(route('producer.products.description.generate', $product))
            ->assertOk()
            ->assertJsonPath('description', fn (string $description): bool => str_contains($description, $product->name)
                && str_contains($description, (string) $product->bottle_volume_ml));

        config(['services.product_description.key' => 'test-key']);
        Http::fake(['https://language.test/*' => Http::response([], 503)]);
        $this->actingAs($producer)
            ->postJson(route('producer.products.description.generate', $product))
            ->assertOk()
            ->assertJsonPath('description', fn (string $description): bool => str_contains($description, $product->brand));
    }

    public function test_non_owner_cannot_generate_a_product_description(): void
    {
        $owner = User::factory()->state(['role' => Role::Producer])->create();
        $otherProducer = User::factory()->state(['role' => Role::Producer])->create();
        $product = OilProduct::factory()->create(['created_by_user_id' => $owner->id]);

        $this->actingAs($otherProducer)
            ->postJson(route('producer.products.description.generate', $product))
            ->assertForbidden();
    }

    public function test_new_product_form_can_generate_from_unsaved_fields(): void
    {
        $producer = User::factory()->state(['role' => Role::Producer])->create();
        config(['services.product_description.key' => '']);

        $this->actingAs($producer)
            ->postJson(route('producer.products.description.generate-new'), [
                'name' => 'Huile test',
                'brand' => 'OliveTrace',
                'bottle_volume_ml' => 750,
                'packaging_date' => '2026-10-01',
            ])
            ->assertOk()
            ->assertJsonPath('description', fn (string $description): bool => str_contains($description, 'Huile test'));
    }
}
