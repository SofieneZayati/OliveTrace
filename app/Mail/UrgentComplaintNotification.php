<?php

namespace App\Mail;

use App\Models\Consumer\Complaint;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UrgentComplaintNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Complaint $complaint,
        public User $admin
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ OliveTrace - Réclamation urgente : '.$this->complaint->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.urgent-complaint',
            with: [
                'complaint' => $this->complaint,
                'admin' => $this->admin,
            ],
        );
    }
}
