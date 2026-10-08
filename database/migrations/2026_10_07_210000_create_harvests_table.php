<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->restrictOnDelete();
            $table->date('harvest_date');
            $table->date('expected_end_date')->nullable();
            $table->string('method', 30);
            $table->decimal('quantity_kg', 12, 2);
            $table->string('status', 20)->default('declared');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['farm_id', 'status'], 'harvests_farm_status_index');
            $table->index('harvest_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
