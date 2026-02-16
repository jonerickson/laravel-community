<?php

declare(strict_types=1);

namespace App\Jobs\Policies;

use App\Mail\Policies\PolicyUpdatedMail;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class NotifyUsersOfPolicyUpdate implements ShouldQueue
{
    use Queueable;

    public function __construct(public Policy $policy) {}

    public function handle(): void
    {
        User::query()
            ->whereNotNull('email')
            ->whereNotNull('email_verified_at')
            ->cursor()
            ->each(function (User $user): void {
                Mail::to($user)->queue(new PolicyUpdatedMail($this->policy));
            });
    }
}
