<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oil_product_id')->constrained('oil_products')->restrictOnDelete();
            $table->foreignId('distributor_profile_id')->constrained('distributor_profiles')->restrictOnDelete();
            $table->string('departure_location');
            $table->string('destination');
            $table->date('departure_date')->index();
            $table->date('arrival_date')->nullable()->index();
            $table->decimal('distance_km', 8, 2);
            $table->string('transport_type', 20);
            $table->string('status', 20)->default('planned')->index();
            $table->decimal('co2_estimate', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
