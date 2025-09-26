<?php

declare(strict_types=1);

namespace App\Jobs\Stripe;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateCustomerInformation implements ShouldQueue
{
    use Queueable;

    public function __construct(protected User $user)
    {
        //
    }

    public function handle(): void
    {
        if (! $this->user->hasStripeId()) {
            return;
        }

        $this->user->syncStripeCustomerDetails();
    }
}
