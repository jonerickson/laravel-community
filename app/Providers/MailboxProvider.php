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
        if ($supportEmail = config('mailbox.mailboxes.support')) {
            Mailbox::to($supportEmail, SupportEmail::class);
        }
    }
}
