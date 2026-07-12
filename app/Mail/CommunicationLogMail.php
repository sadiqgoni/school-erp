<?php

namespace App\Mail;

use App\Models\CommunicationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommunicationLogMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CommunicationLog $communicationLog,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->communicationLog->subject ?: 'School notification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.communication-log',
        );
    }
}
