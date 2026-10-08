<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oil_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('oil_lot_id')->index();
            $table->string('name', 150);
            $table->string('brand', 150);
            $table->unsignedInteger('bottle_volume_ml');
            $table->date('packaging_date');
            $table->string('image')->nullable();
            $table->string('slug', 191)->nullable()->unique();
            $table->string('public_status', 20)->default('visible')->index();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oil_products');
    }
};
