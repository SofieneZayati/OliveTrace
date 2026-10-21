<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_check_succeeds_with_the_complete_production_schema(): void
    {
        $this->artisan('olivetrace:check-database')
            ->expectsOutput('Database connection and shared/production tables OK. Persistence uses Eloquent.')
            ->assertSuccessful();
    }

    public static function missingFarmCareTables(): array
    {
        return [['farm_activities'], ['farm_advice_reports']];
    }

    #[DataProvider('missingFarmCareTables')]
    public function test_database_check_reports_missing_farm_care_migrations(string $missingTable): void
    {
        Schema::shouldReceive('hasTable')->andReturnUsing(fn (string $table) => $table !== $missingTable);
        $this->artisan('olivetrace:check-database')
            ->expectsOutput("Required table {$missingTable} is missing. Run php artisan migrate.")
            ->assertFailed();
    }
}
