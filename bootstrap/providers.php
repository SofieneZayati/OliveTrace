<?php

use App\Providers\AppServiceProvider;
use App\Providers\ConsumerServiceProvider;
use App\Providers\DistributionServiceProvider;
use App\Providers\EventServiceProvider;

return [
    AppServiceProvider::class,
    DistributionServiceProvider::class,
    ConsumerServiceProvider::class,
    EventServiceProvider::class,
];
