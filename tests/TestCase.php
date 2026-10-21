<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        // Ensure testing environment is set before bootstrapping
        if (!isset($_ENV['APP_ENV']) && !isset($_SERVER['APP_ENV'])) {
            $_ENV['APP_ENV'] = 'testing';
            $_SERVER['APP_ENV'] = 'testing';
            putenv('APP_ENV=testing');
        }

        $app = parent::createApplication();
        $connection = $app['config']['database.default'];
        $database = $app['config']["database.connections.{$connection}.database"];

        $safeDatabase = $connection === 'sqlite'
            ? ($database === ':memory:' || str_ends_with((string) $database, '_testing.sqlite'))
            : str_ends_with((string) $database, '_testing');
        if (! $app->environment('testing') || ! $safeDatabase) {
            throw new \RuntimeException('Tests require APP_ENV=testing and an isolated database ending in _testing (or SQLite).');
        }

        return $app;
    }
}
