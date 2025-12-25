<?php

declare(strict_types=1);

namespace App\Providers;

use App\Mailboxes\To\SupportEmail;
use BeyondCode\Mailbox\Facades\Mailbox;
use Illuminate\Support\ServiceProvider;

class MailboxProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mailbox::to(config('mailbox.mailboxes.support'), SupportEmail::class);
    }
}
