<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mill_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('harvest_id')->constrained()->restrictOnDelete();
            $table->foreignId('mill_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('external_mill_name', 150)->nullable();
            $table->date('requested_date');
            $table->date('appointment_date')->nullable();
            $table->decimal('quantity_kg', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->text('message')->nullable();
            $table->text('response_message')->nullable();
            $table->timestamps();

            $table->index(['mill_id', 'status'], 'mill_requests_mill_status_index');
            $table->index(['harvest_id', 'status'], 'mill_requests_harvest_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mill_requests');
    }
};
