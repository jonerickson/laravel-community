<?php

declare(strict_types=1);

namespace App\Managers;

use App\Drivers\SupportTickets\DatabaseDriver;
use App\Drivers\SupportTickets\SupportTicketProvider;
use App\Enums\SupportTicketStatus;
use App\Models\Comment;
use App\Models\SupportTicket;
use Illuminate\Support\Collection;
use Illuminate\Support\Manager;

class SupportTicketManager extends Manager implements SupportTicketProvider
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('support-tickets.default', 'database');
    }

    public function createTicket(array $data): SupportTicket
    {
        return $this->driver()->createTicket($data);
    }

    public function updateTicket(SupportTicket $ticket, array $data): bool
    {
        return $this->driver()->updateTicket($ticket, $data);
    }

    public function deleteTicket(SupportTicket $ticket): bool
    {
        return $this->driver()->deleteTicket($ticket);
    }

    public function syncTicket(SupportTicket $ticket): bool
    {
        return $this->driver()->syncTicket($ticket);
    }

    public function syncTickets(?Collection $tickets = null): int
    {
        return $this->driver()->syncTickets($tickets);
    }

    public function getExternalTicket(string $externalId): ?array
    {
        return $this->driver()->getExternalTicket($externalId);
    }

    public function createExternalTicket(SupportTicket $ticket): ?array
    {
        return $this->driver()->createExternalTicket($ticket);
    }

    public function updateExternalTicket(SupportTicket $ticket): ?array
    {
        return $this->driver()->updateExternalTicket($ticket);
    }

    public function deleteExternalTicket(SupportTicket $ticket): bool
    {
        return $this->driver()->deleteExternalTicket($ticket);
    }

    public function addComment(SupportTicket $ticket, string $content, ?int $userId = null): bool
    {
        return $this->driver()->addComment($ticket, $content, $userId);
    }

    public function deleteComment(SupportTicket $ticket, Comment $comment): bool
    {
        return $this->driver()->deleteComment($ticket, $comment);
    }

    public function assignTicket(SupportTicket $ticket, string|int|null $externalUserId = null): bool
    {
        return $this->driver()->assignTicket($ticket, $externalUserId);
    }

    public function updateStatus(SupportTicket $ticket, SupportTicketStatus $status): bool
    {
        return $this->driver()->updateStatus($ticket, $status);
    }

    public function openTicket(SupportTicket $ticket): bool
    {
        return $this->driver()->openTicket($ticket);
    }

    public function closeTicket(SupportTicket $ticket): bool
    {
        return $this->driver()->closeTicket($ticket);
    }

    public function resolveTicket(SupportTicket $ticket): bool
    {
        return $this->driver()->resolveTicket($ticket);
    }

    public function uploadAttachment(SupportTicket $ticket, string $filePath, string $filename): ?array
    {
        return $this->driver()->uploadAttachment($ticket, $filePath, $filename);
    }

    public function getDriverName(): string
    {
        return $this->driver()->getDriverName();
    }

    protected function createDatabaseDriver(): SupportTicketProvider
    {
        return new DatabaseDriver($this->container);
    }
}
