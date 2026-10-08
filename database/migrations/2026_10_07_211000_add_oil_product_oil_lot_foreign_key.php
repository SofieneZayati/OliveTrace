<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oil_products', function (Blueprint $table) {
            $table->foreign('oil_lot_id', 'oil_products_oil_lot_id_foreign')
                ->references('id')->on('oil_lots')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('oil_products', function (Blueprint $table) {
            $table->dropForeign(['oil_lot_id']);
        });
    }
};
