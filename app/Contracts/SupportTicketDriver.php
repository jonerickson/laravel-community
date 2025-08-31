<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\SupportTicket;
use Illuminate\Support\Collection;

interface SupportTicketDriver
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

    public function assignTicket(SupportTicket $ticket, ?string $externalUserId = null): bool;

    public function updateStatus(SupportTicket $ticket, string $status): bool;

    public function uploadAttachment(SupportTicket $ticket, string $filePath, string $filename): ?array;

    public function getDriverName(): string;
}
