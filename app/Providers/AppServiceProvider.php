<?php

namespace App\Providers;

use App\Models\Consumer\Complaint;
use App\Models\Consumer\Feedback;
use App\Models\Production\Farm;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use App\Policies\ConsumerPolicy;
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
        Gate::policy(Feedback::class, ConsumerPolicy::class);
        Gate::policy(Complaint::class, ConsumerPolicy::class);
        Gate::define('access-admin', fn (User $user) => $user->is_active && $user->isAdmin());
    }
}
