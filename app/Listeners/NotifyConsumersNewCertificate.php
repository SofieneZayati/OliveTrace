<?php

namespace App\Listeners;

use App\Events\NewCertificateAvailable;
use App\Services\Consumer\NotificationService;

class NotifyConsumersNewCertificate
{
    public function __construct(protected NotificationService $notifications) {}

    public function handle(NewCertificateAvailable $event): void
    {
        $this->notifications->notifyNewCertificate($event->productId);
    }
}
