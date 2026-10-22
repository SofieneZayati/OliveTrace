<?php

namespace App\Providers;

use App\Contracts\ProductRatingSummary;
use App\Events\NewCertificateAvailable;
use App\Models\Certification\Certificate;
use App\Models\Distribution\OilProduct;
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
        $notify = function (Certificate $certificate): void {
            if ($certificate->isCurrentlyValid()) {
                OilProduct::publiclyVisible()->where('oil_lot_id', $certificate->certificateRequest->oil_lot_id)
                    ->each(fn (OilProduct $product) => event(new NewCertificateAvailable($product->id)));
            }
        };
        Certificate::created($notify);
        Certificate::updated(function (Certificate $certificate) use ($notify): void {
            if ($certificate->wasChanged(['status', 'issue_date', 'expiry_date'])) {
                $notify($certificate);
            }
        });
    }
}
