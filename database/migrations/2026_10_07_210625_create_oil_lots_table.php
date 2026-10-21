<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oil_lots', function (Blueprint $table) {
            $table->id();

            // Milling side: filled when a mill registers the oil it produced.
            $table->foreignId('mill_request_id')->nullable()->constrained()->cascadeOnDelete();

            // Certification side: the producer who owns the lot.
            $table->foreignId('producer_user_id')->nullable()->constrained('users')->cascadeOnDelete();

            $table->string('lot_number')->unique();
            $table->decimal('liters', 10, 2)->nullable();
            $table->string('quality_grade')->nullable();
            $table->date('production_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('producer_user_id');
            $table->index('production_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oil_lots');
    }
};
