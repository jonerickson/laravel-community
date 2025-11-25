<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Action;
use App\Models\Fingerprint;
use App\Models\User;

class UnbanUserAction extends Action
{
    public function __construct(
        protected User $user,
    ) {
        //
    }

    public function __invoke(): bool
    {
        $this->user->fingerprints()->each(fn (Fingerprint $fingerprint) => $fingerprint->unbanFingerprint());

        return true;
    }
}
