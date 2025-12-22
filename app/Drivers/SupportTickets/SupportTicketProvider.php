<?php

declare(strict_types=1);

namespace App\Drivers\SupportTickets;

use App\Enums\SupportTicketStatus;
use App\Models\Comment;
use App\Models\SupportTicket;
use Illuminate\Support\Collection;

interface SupportTicketProvider
{
    public function createTicket(array $data): SupportTicket;

    public function updateTicket(SupportTicket $ticket, array $data): bool;

    public function deleteTicket(SupportTicket $ticket): bool;

    public function syncTicket(SupportTicket $ticket): bool;

    public function syncTickets(?Collection $tickets = null): int;

    public function getExternalTicket(string $externalId): ?array;

    public function createExternalTicket(SupportTicket $ticket): ?array;

    public function updateExternalTicket(SupportTicket $ticket): ?array;

    public function deleteExternalTicket(SupportTicket $ticket): bool;

    public function addComment(SupportTicket $ticket, string $content, ?int $userId = null): bool;

    public function deleteComment(SupportTicket $ticket, Comment $comment): bool;

    public function assignTicket(SupportTicket $ticket, string|int|null $externalUserId = null): bool;

    public function updateStatus(SupportTicket $ticket, SupportTicketStatus $status): bool;

    public function openTicket(SupportTicket $ticket): bool;

    public function closeTicket(SupportTicket $ticket): bool;

    public function resolveTicket(SupportTicket $ticket): bool;

    public function uploadAttachment(SupportTicket $ticket, string $filePath, string $filename): ?array;

    public function getDriverName(): string;
}
