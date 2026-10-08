<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Orphan mills without an owner cannot exist anymore.
        DB::table('mills')->whereNull('user_id')->delete();

        // Fresh installs already get a required user_id from the create migration.
        if ($this->userIdIsNullable()) {
            if ($foreignKeys = $this->userForeignKey()) {
                Schema::table('mills', function (Blueprint $table) use ($foreignKeys) {
                    $table->dropForeign($foreignKeys);
                });
            }

            Schema::table('mills', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });

            Schema::table('mills', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        if (! Schema::hasIndex('mills', 'mills_user_id_unique')) {
            Schema::table('mills', function (Blueprint $table) {
                $table->unique('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($foreignKeys = $this->userForeignKey()) {
            Schema::table('mills', function (Blueprint $table) use ($foreignKeys) {
                $table->dropForeign($foreignKeys);
            });
        }

        Schema::table('mills', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        if (Schema::hasIndex('mills', 'mills_user_id_unique')) {
            Schema::table('mills', function (Blueprint $table) {
                $table->dropUnique('mills_user_id_unique');
            });
        }
    }

    private function userIdIsNullable(): bool
    {
        $column = collect(Schema::getColumns('mills'))->firstWhere('name', 'user_id');

        return (bool) ($column['nullable'] ?? true);
    }

    /**
     * @return string|null the foreign key constraint name
     */
    private function userForeignKey(): ?string
    {
        foreach (Schema::getForeignKeys('mills') as $foreignKey) {
            if (in_array('user_id', $foreignKey['columns'] ?? [], true)) {
                return $foreignKey['name'];
            }
        }

        return null;
    }
};
