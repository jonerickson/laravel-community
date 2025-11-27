<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Action;
use App\Models\Fingerprint;
use App\Models\User;

class UnblacklistUserAction extends Action
{
    public function __construct(
        protected User $user,
    ) {
        //
    }

    public function __invoke(): bool
    {
        $this->user->unblacklistResource();
        $this->user->fingerprints()->each(fn (Fingerprint $fingerprint): mixed => $fingerprint->unblacklistResource());

        return true;
    }
}
