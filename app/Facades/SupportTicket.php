<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getDefaultDriver()
 * @method static \App\Models\SupportTicket createTicket(array $data)
 * @method static bool updateTicket(\App\Models\SupportTicket $ticket, array $data)
 * @method static bool deleteTicket(\App\Models\SupportTicket $ticket)
 * @method static bool syncTicket(\App\Models\SupportTicket $ticket)
 * @method static int syncTickets(\Illuminate\Support\Collection|null $tickets = null)
 * @method static array|null getExternalTicket(string $externalId)
 * @method static array|null createExternalTicket(\App\Models\SupportTicket $ticket)
 * @method static array|null updateExternalTicket(\App\Models\SupportTicket $ticket)
 * @method static bool deleteExternalTicket(\App\Models\SupportTicket $ticket)
 * @method static bool addComment(\App\Models\SupportTicket $ticket, string $content, int|null $userId = null)
 * @method static bool deleteComment(\App\Models\SupportTicket $ticket, \App\Models\Comment $comment)
 * @method static bool assignTicket(\App\Models\SupportTicket $ticket, string|int|null $externalUserId = null)
 * @method static bool updateStatus(\App\Models\SupportTicket $ticket, \App\Enums\SupportTicketStatus $status)
 * @method static bool openTicket(\App\Models\SupportTicket $ticket)
 * @method static bool closeTicket(\App\Models\SupportTicket $ticket)
 * @method static bool resolveTicket(\App\Models\SupportTicket $ticket)
 * @method static array|null uploadAttachment(\App\Models\SupportTicket $ticket, string $filePath, string $filename)
 * @method static string getDriverName()
 * @method static mixed driver(string|null $driver = null)
 * @method static \App\Managers\SupportTicketManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 * @method static \App\Managers\SupportTicketManager setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \App\Managers\SupportTicketManager forgetDrivers()
 *
 * @see \App\Managers\SupportTicketManager
 */
class SupportTicket extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'support-ticket';
    }
}
