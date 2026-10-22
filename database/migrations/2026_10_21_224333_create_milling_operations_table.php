<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milling_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mill_request_id')->unique()->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('scheduled');
            $table->dateTime('producer_confirmed_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->decimal('received_quantity_kg', 10, 2)->nullable();
            $table->string('olive_condition', 20)->nullable();
            $table->text('delivery_notes')->nullable();
            $table->dateTime('miller_confirmed_at')->nullable();
            $table->decimal('discrepancy_percent', 5, 2)->nullable();
            $table->text('discrepancy_note')->nullable();
            $table->dateTime('discrepancy_acknowledged_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->decimal('processed_quantity_kg', 10, 2)->nullable();
            $table->decimal('output_volume_l', 10, 2)->nullable();
            $table->decimal('pomace_quantity_kg', 10, 2)->nullable();
            $table->decimal('extraction_rate', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milling_operations');
    }
};
