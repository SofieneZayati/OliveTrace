<?php

namespace Tests;

use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;

/** Exercise the actual migration schema with two ORM connections, never the development database. */
abstract class ProductionTestCase extends TestCase
{
    use DatabaseMigrations;

    private ?string $temporaryDatabase = null;

    public function createApplication()
    {
        $app = parent::createApplication();
        if ($app['config']['database.default'] === 'sqlite') {
            // Separate SQLite :memory: connections cannot see each other's tables or shared users.
            $this->temporaryDatabase = sys_get_temp_dir().'/olivetrace_'.bin2hex(random_bytes(12)).'_testing.sqlite';
            touch($this->temporaryDatabase);
            $app['config']['database.connections.sqlite.database'] = $this->temporaryDatabase;
            $app['db']->purge('sqlite');
        }

        return $app;
    }

    protected function tearDown(): void
    {
        $databaseManager = $this->app?->make('db');
        $entityManager = $this->app && $this->app->resolved(EntityManagerInterface::class)
            ? $this->app->make(EntityManagerInterface::class) : null;
        parent::tearDown();
        // Migration rollback opens connections too; disconnect only after Laravel's teardown callbacks.
        $entityManager?->getConnection()->close();
        foreach ($databaseManager?->getConnections() ?? [] as $connection) {
            $connection->disconnect();
        }
        RefreshDatabaseState::$migrated = false;
        if ($this->temporaryDatabase) {
            // Only this test's randomly named temporary file is removed.
            unlink($this->temporaryDatabase);
        }
    }
}
