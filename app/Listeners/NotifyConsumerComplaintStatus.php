<?php

namespace App\Listeners;

use App\Events\ComplaintStatusChanged;
use App\Services\Consumer\NotificationService;

class NotifyConsumerComplaintStatus
{
    public function __construct(protected NotificationService $notifications) {}

    public function handle(ComplaintStatusChanged $event): void
    {
        $this->notifications->notifyComplaintStatusChange($event->complaint, $event->oldStatus);
    }
}
