<?php

require __DIR__.'/vendor/autoload.php';

use Dotenv\Dotenv;

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "SUCCESS: .env loaded correctly\n";
    echo 'APP_ENV: '.$_ENV['APP_ENV']."\n";
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    echo 'Trace: '.$e->getTraceAsString()."\n";
}
