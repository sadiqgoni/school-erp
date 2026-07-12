<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetNotification extends ResetPassword
{
    public function __construct(
        string $token,
        protected string $url,
    ) {
        parent::__construct($token);
    }

    protected function resetUrl($notifiable): string
    {
        return $this->url;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your School Dice password')
            ->view('emails.password-reset', [
                'name' => $notifiable->name ?? 'there',
                'url' => $this->url,
                'expiresInMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]);
    }
}
