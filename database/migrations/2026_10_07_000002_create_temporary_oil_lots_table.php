<?php

// TEMPORARY STUB - replaced by Mariem's real oil_lots. Do not extend. Delete at merge.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('oil_lots')) {
            return;
        }

        Schema::create('oil_lots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('harvest_id')->nullable();
            $table->string('lot_code')->unique();
            $table->date('extraction_date');
            $table->decimal('volume_l', 10, 2);
            $table->string('grade', 50);
            $table->decimal('acidity', 5, 3);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('oil_lots')) {
            return;
        }

        $columns = Schema::getColumnListing('oil_lots');
        sort($columns);
        $stubColumns = ['acidity', 'created_at', 'extraction_date', 'grade', 'harvest_id', 'id', 'lot_code', 'notes', 'updated_at', 'volume_l'];
        sort($stubColumns);

        if ($columns === $stubColumns) {
            Schema::dropIfExists('oil_lots');
        }
    }
};
