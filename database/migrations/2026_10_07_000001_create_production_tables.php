<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('display_name', 120);
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('company_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_profile_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('governorate', 50);
            $table->string('delegation', 100)->nullable();
            $table->decimal('area_ha', 10, 2);
            $table->string('olive_variety', 100);
            $table->string('farming_type', 20);
            $table->string('irrigation_type', 20);
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->index(['governorate', 'status'], 'farms_origin_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farms');
        Schema::dropIfExists('producer_profiles');
    }
};
