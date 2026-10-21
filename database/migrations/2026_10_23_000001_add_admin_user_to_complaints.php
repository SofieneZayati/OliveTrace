<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (! Schema::hasColumn('complaints', 'admin_user_id')) {
                $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete()->after('admin_response');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'admin_user_id')) {
                $table->dropForeign(['admin_user_id']);
                $table->dropColumn('admin_user_id');
            }
        });
    }
};
