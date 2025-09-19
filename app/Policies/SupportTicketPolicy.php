<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
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
