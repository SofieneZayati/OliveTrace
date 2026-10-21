<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
try {
    $dotenv->load();
    echo "SUCCESS\n";
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
}
