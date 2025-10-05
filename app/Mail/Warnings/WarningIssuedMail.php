<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use App\Models\UserWarning;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WarningIssuedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserWarning $userWarning,
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Warning Issued: '.$this->userWarning->warning->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.warnings.warning-issued',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
