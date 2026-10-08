<?php

namespace App\Providers;

use App\Contracts\ProductRatingSummary;
use App\Services\Consumer\FeedbackRatingSummary;
use Illuminate\Support\ServiceProvider;

// Module 5 (Aymen) bindings. Registered after DistributionServiceProvider so
// the feedback-backed rating summary replaces Hana's unavailable placeholder.
class ConsumerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRatingSummary::class, FeedbackRatingSummary::class);
    }

    public function boot(): void
    {
        //
    }
}
