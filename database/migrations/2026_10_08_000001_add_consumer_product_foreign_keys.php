<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Module 5 (Aymen) — completes the team FK map: feedback.oil_product_id and
// complaints.oil_product_id reference Hana's oil_products with
// restrictOnDelete (history is never cascade-deleted). Guarded for branches
// where the distribution tables have not landed yet.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('oil_products')) {
            return;
        }

        Schema::table('feedback', function (Blueprint $table) {
            $table->foreign('oil_product_id', 'feedback_oil_product_id_foreign')
                ->references('id')->on('oil_products')->restrictOnDelete();
        });
        Schema::table('complaints', function (Blueprint $table) {
            $table->foreign('oil_product_id', 'complaints_oil_product_id_foreign')
                ->references('id')->on('oil_products')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('oil_products')) {
            return;
        }

        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign(['oil_product_id']);
        });
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['oil_product_id']);
        });
    }
};
