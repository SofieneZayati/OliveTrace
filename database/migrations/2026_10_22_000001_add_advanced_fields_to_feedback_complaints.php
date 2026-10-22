<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->boolean('verified_purchase')->default(false)->after('status');
            $table->timestamp('last_edited_at')->nullable()->after('updated_at');
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->timestamp('sla_deadline')->nullable()->after('resolved_at');
            $table->integer('reopen_count')->default(0)->after('sla_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn(['verified_purchase', 'last_edited_at']);
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn(['sla_deadline', 'reopen_count']);
        });
    }
};
