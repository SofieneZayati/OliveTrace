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
            $table->foreignId('mill_request_id')->constrained()->cascadeOnDelete();
            $table->string('lot_number')->unique();
            $table->decimal('liters', 10, 2);
            $table->string('quality_grade');
            $table->date('production_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('production_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oil_lots');
    }
};