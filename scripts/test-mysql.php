<?php

// Run the same suite against a separate MySQL schema using your local .env credentials.
// Usage: php scripts/test-mysql.php
require __DIR__.'/../vendor/autoload.php';

$values = Dotenv\Dotenv::parse(file_get_contents(__DIR__.'/../.env'));
$database = ($values['DB_DATABASE'] ?? 'olivetrace').'_testing';
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $database] as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $_SERVER[$name] = $value;
}
foreach (['DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD'] as $name) {
    $value = $values[$name] ?? '';
    putenv("{$name}={$value}");
    $_ENV[$name] = $_SERVER[$name] = $value;
}

echo "Running tests against the separate MySQL database {$database}.\n";
passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/../artisan').' config:clear', $result);
if ($result !== 0) {
    exit($result);
}
passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/../vendor/phpunit/phpunit/phpunit'), $result);
exit($result);
