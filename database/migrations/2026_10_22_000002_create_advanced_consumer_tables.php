<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verified_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('oil_product_id')->constrained('oil_products')->cascadeOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
            $table->string('proof_type')->nullable(); // receipt, qr_code, invoice
            $table->string('proof_path')->nullable(); // uploaded proof file
            $table->timestamp('verified_at')->useCurrent();
            $table->timestamps();

            $table->unique(['consumer_user_id', 'oil_product_id'], 'verified_purchase_unique');
        });

        Schema::create('feedback_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('old_rating')->nullable();
            $table->text('old_comment')->nullable();
            $table->unsignedTinyInteger('new_rating')->nullable();
            $table->text('new_comment')->nullable();
            $table->foreignId('edited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('consumer_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // new_certificate, complaint_status, review_reply, product_alert
            $table->string('title');
            $table->text('message');
            $table->foreignId('related_feedback_id')->nullable()->constrained('feedback')->nullOnDelete();
            $table->foreignId('related_complaint_id')->nullable()->constrained('complaints')->nullOnDelete();
            $table->foreignId('related_oil_product_id')->nullable()->constrained('oil_products')->nullOnDelete();
            $table->boolean('read')->default(false);
            $table->timestamps();

            $table->index(['consumer_user_id', 'read', 'created_at']);
        });

        Schema::create('admin_suggested_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_id')->constrained()->cascadeOnDelete();
            $table->text('suggested_response');
            $table->string('tone'); // professional, empathetic, formal, friendly
            $table->decimal('confidence', 3, 2)->nullable();
            $table->boolean('used')->default(false);
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_suggested_responses');
        Schema::dropIfExists('consumer_notifications');
        Schema::dropIfExists('feedback_edits');
        Schema::dropIfExists('verified_purchases');
    }
};
