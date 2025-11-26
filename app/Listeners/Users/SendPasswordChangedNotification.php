<?php

declare(strict_types=1);

namespace App\Listeners\Users;

use App\Events\PasswordChanged;
use App\Mail\Auth\PasswordChangedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class SendPasswordChangedNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(PasswordChanged $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        Mail::to($event->user)->send(new PasswordChangedMail($event->user));
    }
}
