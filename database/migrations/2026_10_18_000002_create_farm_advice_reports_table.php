<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_advice_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->restrictOnDelete();
            $table->string('provider', 20);
            $table->string('model', 100);
            $table->json('facts');
            $table->json('advice');
            $table->timestamps();
            $table->index(['farm_id', 'created_at']);
        });
        Schema::table('farm_activities', function (Blueprint $table) {
            $table->foreignId('advice_report_id')->nullable()->constrained('farm_advice_reports')->restrictOnDelete();
            $table->unsignedTinyInteger('recommendation_index')->nullable();
            $table->unique(['advice_report_id', 'recommendation_index'], 'farm_activity_report_recommendation_unique');
        });
    }

    public function down(): void
    {
        Schema::table('farm_activities', function (Blueprint $table) {
            // MySQL may reuse the composite unique index for this foreign key.
            $table->dropForeign(['advice_report_id']);
            $table->dropUnique('farm_activity_report_recommendation_unique');
            $table->dropColumn(['advice_report_id', 'recommendation_index']);
        });
        Schema::dropIfExists('farm_advice_reports');
    }
};
