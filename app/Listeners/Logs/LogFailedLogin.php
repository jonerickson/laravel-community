<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use App\Models\User;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        $credentials = $event->credentials;
        $email = $credentials['email'] ?? 'unknown';

        $user = new User;
        if (method_exists($user, 'logFailedLogin')) {
            $user->logFailedLogin($email);
        }
    }
}
