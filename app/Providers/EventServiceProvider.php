<?php

namespace App\Providers;

use App\Events\ComplaintCreated;
use App\Events\ComplaintStatusChanged;
use App\Events\NewCertificateAvailable;
use App\Listeners\NotifyAdminUrgentComplaint;
use App\Listeners\NotifyConsumerComplaintStatus;
use App\Listeners\NotifyConsumersNewCertificate;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ComplaintCreated::class => [
            NotifyAdminUrgentComplaint::class,
        ],
        ComplaintStatusChanged::class => [
            NotifyConsumerComplaintStatus::class,
        ],
        NewCertificateAvailable::class => [
            NotifyConsumersNewCertificate::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
