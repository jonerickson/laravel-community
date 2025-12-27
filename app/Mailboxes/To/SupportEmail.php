<?php

declare(strict_types=1);

namespace App\Mailboxes\To;

use App\Mail\SupportTickets\SupportTicketNotFound;
use App\Managers\SupportTicketManager;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use App\Services\EmailParserService;
use BeyondCode\Mailbox\InboundEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZBateson\MailMimeParser\Message\MimePart;

class SupportEmail
{
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

    public function __construct(private readonly SupportTicketManager $ticketManager) {}

    public function __invoke(InboundEmail $inboundEmail): void
    {
        $rateLimitKey = 'support-email:'.Str::lower($inboundEmail->from());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            // abort(429);
        }

        RateLimiter::hit($rateLimitKey);

        match (true) {
            Str::isMatch('/ST-[A-Z0-9]+/', $inboundEmail->subject()) => $this->createTicketReply($inboundEmail),
            default => $this->createNewTicket($inboundEmail),
        };
    }

    private function createNewTicket(InboundEmail $inboundEmail): void
    {
        if (! ($author = $this->findOrCreateAuthor($inboundEmail)) instanceof User) {
            return;
        }

        $ticket = $this->ticketManager->createTicket([
            'subject' => $inboundEmail->subject(),
            'description' => EmailParserService::parse($inboundEmail->text()),
            'support_ticket_category_id' => SupportTicketCategory::firstOrCreate(['name' => 'Uncategorized'])->id,
            'created_by' => $author->id,
        ]);

        $this->attachFiles($ticket, $inboundEmail);
    }

    private function createTicketReply(InboundEmail $inboundEmail): void
    {
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

        if (! ($author = $this->findOrCreateAuthor($inboundEmail)) instanceof User) {
            return;
        }

        $this->ticketManager->addComment(
            ticket: $ticket,
            content: EmailParserService::parse($inboundEmail->text()),
            userId: $author->id,
        );

        $this->attachFiles($ticket, $inboundEmail);
    }

    private function findOrCreateAuthor(InboundEmail $inboundEmail): ?User
    {
        return User::query()->where('email', $inboundEmail->from())->firstOrCreate([
            'email' => $inboundEmail->from(),
        ], [
            'name' => $inboundEmail->fromName(),
        ]);
    }

    private function attachFiles(SupportTicket $ticket, InboundEmail $inboundEmail): void
    {
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
