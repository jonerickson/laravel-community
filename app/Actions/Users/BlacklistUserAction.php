<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Action;
use App\Models\Blacklist;
use App\Models\Fingerprint;
use App\Models\User;

class BlacklistUserAction extends Action
{
    public function __construct(
        protected User $user,
        protected string $reason,
    ) {
        //
    }

    public function __invoke(): bool
    {
        if ($this->user->is_blacklisted) {
            return false;
        }

        $this->user->blacklistResource($this->reason);
        $this->user->fingerprints()->each(fn (Fingerprint $fingerprint): Blacklist|false => $fingerprint->blacklistResource($this->reason));

        return true;
    }
}
