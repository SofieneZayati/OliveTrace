<?php

namespace App\Listeners;

use App\Events\ComplaintCreated;
use App\Services\Consumer\NotificationService;

class NotifyAdminUrgentComplaint
{
    public function __construct(protected NotificationService $notifications) {}

    public function handle(ComplaintCreated $event): void
    {
        $complaint = $event->complaint;

        // Check if urgent based on AI priority
        if (in_array($complaint->ai_priority?->value, ['urgent', 'high'], true)) {
            $this->notifications->notifyAdminUrgentComplaint($event->complaint);
        }
    }
}
