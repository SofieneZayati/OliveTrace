<?php

namespace App\Mail;

use App\Models\Consumer\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComplaintStatusNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Complaint $complaint,
        public string $oldStatus
    ) {}

    public function envelope(): Envelope
    {
        $labels = [
            'open' => 'Ouverte',
            'in_review' => 'En cours',
            'resolved' => 'Résolue',
            'rejected' => 'Rejetée',
        ];

        return new Envelope(
            subject: 'OliveTrace - Votre réclamation : '.$labels[$this->complaint->status].' - '.$this->complaint->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.complaint-status',
            with: [
                'complaint' => $this->complaint,
                'oldStatusLabel' => match ($this->oldStatus) {
                    'open' => 'Ouverte',
                    'in_review' => 'En cours',
                    'resolved' => 'Résolue',
                    'rejected' => 'Rejetée',
                    default => $this->oldStatus,
                },
                'newStatusLabel' => match ($this->complaint->status) {
                    'open' => 'Ouverte',
                    'in_review' => 'En cours',
                    'resolved' => 'Résolue',
                    'rejected' => 'Rejetée',
                    default => $this->complaint->status,
                },
            ],
        );
    }
}
