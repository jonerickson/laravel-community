<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user instanceof User;
    }

    public function view(?User $user, SupportTicket $ticket): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $ticket->isAuthoredBy($user);
    }

    public function create(?User $user): bool
    {
        return $user instanceof User;
    }

    public function update(?User $user, SupportTicket $ticket): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $ticket)
            && $ticket->isAuthoredBy($user);
    }
}
