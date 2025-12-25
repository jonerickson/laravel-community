<?php

declare(strict_types=1);

namespace App\Mailboxes\To;

use App\Mail\SupportTickets\SupportTicketNotFound;
use App\Models\SupportTicket;
use App\Models\User;
use BeyondCode\Mailbox\InboundEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZBateson\MailMimeParser\Message\MimePart;

class SupportEmail
{
    public const string REPLY_LINE = 'Please reply above this line.
----------------------------------';

    private const array ALLOWED_MIME_TYPES = [
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/bmp',
        // Videos
        'video/mp4',
        'video/mpeg',
        'video/quicktime',
        'video/x-msvideo',
        'video/webm',
        'video/x-ms-wmv',
    ];

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

        $ticket = SupportTicket::query()->where('ticket_number', $ticketNumber)->firstOr(function () use ($inboundEmail, $ticketNumber): void {
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

        /** @var MimePart $attachment */
        foreach ($inboundEmail->attachments() as $attachment) {
            $mimeType = $attachment->getContentType();

            if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
                continue;
            }

            $filename = $attachment->getFilename();
            $uniqueFilename = Str::uuid().'-'.$filename;
            $path = 'support/'.$uniqueFilename;
            $content = $attachment->getContent();

            if (Storage::put($path, $content)) {
                $ticket->files()->create([
                    'name' => $filename,
                    'filename' => $filename,
                    'path' => $path,
                    'mime' => $mimeType,
                ]);
            }
        }
    }
}
