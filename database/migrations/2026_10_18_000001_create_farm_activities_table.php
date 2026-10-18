<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->restrictOnDelete();
            $table->string('title', 150);
            $table->string('type', 30);
            $table->date('planned_on');
            $table->string('status', 20)->default('planned');
            $table->date('completed_on')->nullable();
            $table->decimal('water_m3', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['farm_id', 'status', 'planned_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_activities');
    }
};
