<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lab_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_request_id')->constrained('certificate_requests')->cascadeOnDelete();
            $table->foreignId('lab_user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('analysis_date');
            $table->float('acidity');
            $table->float('peroxide_value')->nullable();
            $table->boolean('result');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_analyses');
    }
};
