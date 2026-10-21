<?php

namespace App\Events;

use App\Models\Consumer\Complaint;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Complaint $complaint) {}
}
