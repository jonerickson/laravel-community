<?php

declare(strict_types=1);

namespace App\Drivers\SupportTickets;

use App\Enums\FileVisibility;
use App\Enums\SupportTicketStatus;
use App\Models\Comment;
use App\Models\SupportTicket;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DatabaseDriver implements SupportTicketProvider
{
    public function __construct(
        protected Container $container
    ) {}

    public function createTicket(array $data): SupportTicket
    {
        return SupportTicket::create($data);
    }

    public function updateTicket(SupportTicket $ticket, array $data): bool
    {
        return $ticket->update($data);
    }

    public function deleteTicket(SupportTicket $ticket): bool
    {
        return $ticket->delete();
    }

    public function syncTicket(SupportTicket $ticket): bool
    {
        if (! $ticket->isExternal()) {
            return false;
        }

        return $ticket->markSynced();
    }

    public function syncTickets(?Collection $tickets = null): int
    {
        if (! $tickets instanceof Collection) {
            $tickets = SupportTicket::needsSyncing()->get();
        }

        $syncedCount = 0;

        foreach ($tickets as $ticket) {
            if ($this->syncTicket($ticket)) {
                $syncedCount++;
            }
        }

        return $syncedCount;
    }

    public function getExternalTicket(string $externalId): ?array
    {
        return null;
    }

    public function createExternalTicket(SupportTicket $ticket): ?array
    {
        return null;
    }

    public function updateExternalTicket(SupportTicket $ticket): ?array
    {
        return null;
    }

    public function deleteExternalTicket(SupportTicket $ticket): bool
    {
        return true;
    }

    public function addComment(SupportTicket $ticket, string $content, ?int $userId = null): bool
    {
        if (! $ticket->status->isActive()) {
            $this->openTicket($ticket);
        }

        if ($userId === $ticket->created_by && ! in_array($ticket->status, [SupportTicketStatus::Open, SupportTicketStatus::New]) && $ticket->canTransitionTo(SupportTicketStatus::Open)) {
            $ticket->updateStatus(SupportTicketStatus::Open);
        } elseif ($userId !== $ticket->created_by && $ticket->status !== SupportTicketStatus::WaitingOnCustomer && $ticket->canTransitionTo(SupportTicketStatus::WaitingOnCustomer)) {
            $ticket->updateStatus(SupportTicketStatus::WaitingOnCustomer);
        }

        if (is_null($ticket->assigned_to)) {
            $ticket->assignedTo()->associate($userId)->save();
        }

        $comment = Comment::create([
            'commentable_type' => SupportTicket::class,
            'commentable_id' => $ticket->id,
            'content' => $content,
            'created_by' => $userId,
        ]);

        return $comment->exists;
    }

    public function deleteComment(SupportTicket $ticket, Comment $comment): bool
    {
        return $comment->delete();
    }

    public function assignTicket(SupportTicket $ticket, string|int|null $externalUserId = null): bool
    {
        if ($externalUserId && $user = User::find($externalUserId)) {
            return $ticket->assign($user);
        }

        return $ticket->unassign();
    }

    public function updateStatus(SupportTicket $ticket, SupportTicketStatus $status): bool
    {
        return $ticket->updateStatus($status);
    }

    public function openTicket(SupportTicket $ticket): bool
    {
        if ($ticket->canTransitionTo(SupportTicketStatus::Open) && $ticket->updateStatus(SupportTicketStatus::Open)) {
            $ticket->update([
                'resolved_at' => null,
                'closed_at' => null,
            ]);

            return true;
        }

        return false;
    }

    public function closeTicket(SupportTicket $ticket): bool
    {
        if ($ticket->updateStatus(SupportTicketStatus::Closed)) {
            $ticket->update([
                'resolved_at' => null,
                'closed_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    public function resolveTicket(SupportTicket $ticket): bool
    {
        if ($ticket->updateStatus(SupportTicketStatus::Resolved)) {
            $ticket->update([
                'closed_at' => null,
                'resolved_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    public function uploadAttachment(SupportTicket $ticket, string $filePath, string $filename): ?array
    {
        try {
            $storedPath = Storage::putFileAs(
                'support-tickets/'.$ticket->id,
                $filePath,
                $filename
            );

            if (! $storedPath) {
                return null;
            }

            $file = $ticket->files()->create([
                'name' => $filename,
                'path' => $storedPath,
                'filename' => $filename,
                'mime' => mime_content_type($filePath) ?: 'application/octet-stream',
                'size' => filesize($filePath),
                'visibility' => FileVisibility::Private,
            ]);

            return [
                'id' => $file->id,
                'name' => $file->name,
                'path' => $file->path,
                'size' => $file->size,
                'mime' => $file->mime,
            ];
        } catch (Exception) {
            return null;
        }
    }

    public function getDriverName(): string
    {
        return 'database';
    }
}
