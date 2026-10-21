<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Module 5 (Aymen) — adds columns for advanced features:
// - evidence_photo: optional photo proof for complaints (Feature 5)
// - ai_draft_reply: cached AI-generated draft reply (Feature 1)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->string('evidence_photo')->nullable()->after('description');
            $table->text('ai_draft_reply')->nullable()->after('ai_priority');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn(['evidence_photo', 'ai_draft_reply']);
        });
    }
};
