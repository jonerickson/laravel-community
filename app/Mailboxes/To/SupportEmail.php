<?php

declare(strict_types=1);

namespace App\Mailboxes\To;

use App\Mail\SupportTickets\SupportTicketNotFound;
use App\Models\SupportTicket;
use App\Models\User;
use BeyondCode\Mailbox\InboundEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class SupportEmail
{
    public const string REPLY_LINE = 'Please reply above this line.
----------------------------------';

    public function __invoke(InboundEmail $inboundEmail): void
    {
        $rateLimitKey = 'support-email:'.Str::lower($inboundEmail->from());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            abort(429);
        }

        RateLimiter::hit($rateLimitKey);

        $ticketNumber = Str::of($inboundEmail->subject())
            ->after('ST-')
            ->prepend('ST-')
            ->toString();

        $ticket = SupportTicket::query()->where('ticket_number', $ticketNumber)->firstOr(function () use ($inboundEmail, $ticketNumber) {
            Mail::to($inboundEmail->from())->send(new SupportTicketNotFound($ticketNumber));
        });

        if (! $ticket instanceof SupportTicket) {
            return;
        }

        $author = User::query()->where('email', $inboundEmail->from())->first();

        if (! $author instanceof User) {
            return;
        }

        $reply = Str::of($inboundEmail->text())
            ->before(static::REPLY_LINE)
            ->trim()
            ->toString();

        $ticket->comments()->create([
            'content' => $reply,
            'created_by' => $author->id,
        ]);
    }
}
