<?php

namespace Tests\Feature;

use App\Models\Distribution\OilProduct;
use App\Services\Distribution\ProductTraceUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DistributionTraceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_slug_is_generated_once_and_is_unique(): void
    {
        $first = OilProduct::factory()->create(['name' => 'Huile d’olive vierge extra - El Baraka']);
        $second = OilProduct::factory()->create(['name' => 'Huile d’olive vierge extra - El Baraka']);
        $originalSlug = $first->slug;

        $first->name = 'Renamed after creation';
        $first->slug = 'attempted-change';
        $first->save();

        $this->assertMatchesRegularExpression('/^huile-dolive-vierge-extra-el-baraka-[a-z0-9]{8}$/', $originalSlug);
        $this->assertNotSame($originalSlug, $second->slug);
        $this->assertSame($originalSlug, $first->fresh()->slug);
        $this->assertSame(1, OilProduct::query()->where('slug', $originalSlug)->count());
    }

    public function test_trace_url_uses_the_configured_fallback_when_trace_route_is_absent(): void
    {
        app('url');
        Route::shouldReceive('has')->once()->with('trace.show')->andReturnFalse();
        config(['olivetrace-distribution.trace_path' => '/public/trace']);
        $product = OilProduct::factory()->create();

        $this->assertSame(url('/public/trace/'.$product->slug), app(ProductTraceUrl::class)->forProduct($product));
    }

    public function test_trace_url_uses_the_trace_route_when_available(): void
    {
        app('url');
        $product = OilProduct::factory()->create();
        Route::shouldReceive('has')->once()->with('trace.show')->andReturnTrue();
        URL::shouldReceive('route')->once()->with('trace.show', $product->slug, true)->andReturn('https://trace.example.test/'.$product->slug);

        $this->assertSame('https://trace.example.test/'.$product->slug, app(ProductTraceUrl::class)->forProduct($product));
    }

    public function test_qr_code_is_svg_and_deterministic(): void
    {
        app('url');
        $product = OilProduct::factory()->create();
        Route::shouldReceive('has')->twice()->with('trace.show')->andReturnFalse();

        $firstSvg = $product->qrSvg();
        $secondSvg = $product->qrSvg();
        $this->assertStringStartsWith('<svg', $firstSvg);
        $this->assertSame($firstSvg, $secondSvg);
    }
}
