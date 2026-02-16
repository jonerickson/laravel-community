<?php

declare(strict_types=1);

namespace App\Mail\Policies;

use App\Models\Policy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PolicyUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Policy $policy
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Policy Updated: '.$this->policy->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.policies.policy-updated',
        );
    }

    /**
     * @return array{}
     */
    public function attachments(): array
    {
        return [];
    }
}
