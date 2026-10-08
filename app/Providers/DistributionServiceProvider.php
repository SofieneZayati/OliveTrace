<?php

namespace App\Providers;

use App\Contracts\OilLotCertificationStatusProvider;
use App\Contracts\OilLotLookup;
use App\Contracts\OilLotOwnership;
use App\Contracts\OilProductEligibility;
use App\Contracts\ProductRatingSummary;
use App\Models\Distribution\DistributorProfile;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use App\Policies\Distribution\DistributorProfilePolicy;
use App\Policies\Distribution\OilProductPolicy;
use App\Policies\Distribution\ShipmentPolicy;
use App\Services\Distribution\ExistingOilLotEligibility;
use App\Services\Distribution\TemporaryOilLotLookup;
use App\Services\Distribution\TemporaryPermissiveOilLotOwnership;
use App\Services\Distribution\UnavailableOilLotCertificationStatus;
use App\Services\Distribution\UnavailableProductRatingSummary;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class DistributionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OilLotLookup::class, TemporaryOilLotLookup::class);
        $this->app->bind(OilProductEligibility::class, ExistingOilLotEligibility::class);
        $this->app->bind(OilLotCertificationStatusProvider::class, UnavailableOilLotCertificationStatus::class);
        $this->app->bind(OilLotOwnership::class, TemporaryPermissiveOilLotOwnership::class);
        $this->app->bind(ProductRatingSummary::class, UnavailableProductRatingSummary::class);
    }

    public function boot(): void
    {
        Gate::policy(OilProduct::class, OilProductPolicy::class);
        Gate::policy(Shipment::class, ShipmentPolicy::class);
        Gate::policy(DistributorProfile::class, DistributorProfilePolicy::class);
    }
}
