<?php

declare(strict_types=1);

namespace App\Managers;

use App\Contracts\SupportTicketDriver;
use App\Drivers\SupportTickets\DatabaseDriver;
use Illuminate\Support\Manager;

class SupportTicketManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('support-tickets.default', 'database');
    }

    protected function createDatabaseDriver(): SupportTicketDriver
    {
        return new DatabaseDriver($this->container);
    }
}
