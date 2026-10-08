<?php

namespace App\Providers;

use App\Models\Consumer\Complaint;
use App\Models\Consumer\Feedback;
use App\Models\Production\Farm;
use App\Models\Production\Harvest;
use App\Models\Production\MillRequest;
use App\Models\Production\OilLot;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use App\Policies\ConsumerPolicy;
use App\Policies\HarvestPolicy;
use App\Policies\MillRequestPolicy;
use App\Policies\OilLotPolicy;
use App\Policies\ProductionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ProducerProfile::class, ProductionPolicy::class);
        Gate::policy(Farm::class, ProductionPolicy::class);
        Gate::policy(Harvest::class, HarvestPolicy::class);
        Gate::policy(MillRequest::class, MillRequestPolicy::class);
        Gate::policy(OilLot::class, OilLotPolicy::class);
        Gate::policy(Feedback::class, ConsumerPolicy::class);
        Gate::policy(Complaint::class, ConsumerPolicy::class);
        Gate::define('access-admin', fn (User $user) => $user->is_active && $user->isAdmin());
    }
}
