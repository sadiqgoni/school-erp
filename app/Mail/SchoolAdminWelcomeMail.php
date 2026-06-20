<?php

namespace App\Mail;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SchoolAdminWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public School $school,
        public string $adminName,
        public string $email,
        public string $temporaryPassword,
        public string $portalUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your School Dice portal login',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.school-admin-welcome',
        );
    }
}
