<?php

namespace App\Mail;

use App\Models\Consumer\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Module 5 (Aymen) — Email notification sent to the consumer when an admin
// updates the status of their complaint. Provides the new status, admin
// response (if any), and a link to view the complaint online.
class ComplaintStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Complaint $complaint,
        public string $newStatus,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'OliveTrace — Mise à jour de votre réclamation #' . $this->complaint->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.complaint-status-updated',
        );
    }
}
