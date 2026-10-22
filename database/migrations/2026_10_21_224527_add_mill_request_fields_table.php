<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mill_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('mill_requests', 'refusal_reason')) {
                $table->text('refusal_reason')->nullable()->after('response_message');
            }
            if (! Schema::hasColumn('mill_requests', 'responded_at')) {
                $table->dateTime('responded_at')->nullable()->after('refusal_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mill_requests', function (Blueprint $table) {
            if (Schema::hasColumn('mill_requests', 'responded_at')) {
                $table->dropColumn('responded_at');
            }
            if (Schema::hasColumn('mill_requests', 'refusal_reason')) {
                $table->dropColumn('refusal_reason');
            }
        });
    }
};
