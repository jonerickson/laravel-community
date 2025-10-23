<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PasswordChanged;
use App\Mail\Auth\PasswordChangedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendPasswordChangedNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(PasswordChanged $event): void
    {
        Mail::to($event->user)->send(new PasswordChangedMail($event->user));
    }
}
