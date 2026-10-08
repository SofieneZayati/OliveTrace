<?php

use App\Models\Distribution\OilProduct;
use App\Services\Distribution\ProductSlugGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $generator = app(ProductSlugGenerator::class);

        OilProduct::query()->whereNull('slug')->orderBy('id')->each(function (OilProduct $product) use ($generator): void {
            $product->forceFill(['slug' => $generator->generate($product->name)])->saveQuietly();
        });

        Schema::table('oil_products', function (Blueprint $table): void {
            $table->string('slug', 191)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('oil_products', function (Blueprint $table): void {
            $table->string('slug', 191)->nullable()->change();
        });
    }
};
