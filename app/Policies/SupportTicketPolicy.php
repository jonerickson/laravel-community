<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function before(User $user): ?bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, SupportTicket $ticket): bool
    {
        return $ticket->isAuthoredBy($user);
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function update(?User $user, SupportTicket $ticket): bool
    {
        return $ticket->isAuthoredBy($user);
    }
}
