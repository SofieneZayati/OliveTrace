<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Module 5 (Aymen) — Consumer Traceability, Feedback & Complaints.
//
// Integration contract: `oil_product_id` references Hana's `oil_products`
// table, which does not exist on this branch yet. The column is therefore a
// plain indexed unsigned integer WITHOUT a database foreign key for now;
// the foreign key (`restrictOnDelete`, never cascade: traceability history
// must survive) will be added during Phase 6 integration once Hana's table
// lands. Consumer references use `restrictOnDelete` so linked accounts are
// deactivated instead of hard-deleted, per the shared deletion policy.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('oil_product_id')->index();
            $table->foreignId('consumer_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('visible');
            $table->string('ai_category', 30)->nullable();
            $table->string('ai_sentiment', 20)->nullable();
            $table->string('ai_priority', 20)->nullable();
            $table->timestamps();
            // One active feedback per consumer per product: updating replaces, never duplicates.
            $table->unique(['oil_product_id', 'consumer_user_id'], 'feedback_product_consumer_unique');
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('oil_product_id')->index();
            $table->foreignId('consumer_user_id')->constrained('users')->restrictOnDelete();
            $table->string('subject', 150);
            $table->text('description');
            $table->string('status', 20)->default('open');
            $table->text('admin_response')->nullable();
            $table->string('ai_category', 30)->nullable();
            $table->string('ai_sentiment', 20)->nullable();
            $table->string('ai_priority', 20)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'complaints_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('feedback');
    }
};
