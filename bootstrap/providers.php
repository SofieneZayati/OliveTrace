<?php

use App\Providers\AppServiceProvider;
use App\Providers\ConsumerServiceProvider;
use App\Providers\DistributionServiceProvider;

return [
    AppServiceProvider::class,
    DistributionServiceProvider::class,
    ConsumerServiceProvider::class,
];
