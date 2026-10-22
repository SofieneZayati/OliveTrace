<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oil_lots', function (Blueprint $table) {
            $table->foreignId('milling_operation_id')->nullable()->unique()->after('mill_request_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('oil_lots', function (Blueprint $table) {
            $table->dropForeign(['milling_operation_id']);
            $table->dropUnique(['milling_operation_id']);
            $table->dropColumn('milling_operation_id');
        });
    }
};
