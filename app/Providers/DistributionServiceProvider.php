<?php

namespace App\Providers;

use App\Contracts\OilLotLookup;
use App\Services\Distribution\TemporaryOilLotLookup;
use Illuminate\Support\ServiceProvider;

class DistributionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OilLotLookup::class, TemporaryOilLotLookup::class);
    }
}
