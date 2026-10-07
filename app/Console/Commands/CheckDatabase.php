<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDatabase extends Command
{
    protected $signature = 'olivetrace:check-database';

    protected $description = 'Check the database connection and required tables without changing data';

    public function handle(): int
    {
        try {
            DB::select('SELECT 1');
            foreach (['users', 'producer_profiles', 'farms'] as $table) {
                if (! Schema::hasTable($table)) {
                    $this->error('Required tables are missing. Run php artisan migrate.');

                    return self::FAILURE;
                }
            }
        } catch (QueryException) {
            $this->error('Cannot connect to the database. Check the service and your private .env settings.');

            return self::FAILURE;
        }
        $this->info('Database connection and shared/production tables OK. Persistence uses Eloquent.');

        return self::SUCCESS;
    }
}
