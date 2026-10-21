<?php

use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
echo 'Bootstrapped successfully';
