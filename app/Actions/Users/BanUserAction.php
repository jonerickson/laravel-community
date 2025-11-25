<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Action;
use App\Models\Fingerprint;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class BanUserAction extends Action
{
    public function __construct(
        protected User $user,
        protected string $reason,
    ) {
        //
    }

    public function __invoke(): bool
    {
        if ($this->user->is_banned) {
            return false;
        }

        $this->user->fingerprints()->each(fn (Fingerprint $fingerprint) => $fingerprint->banFingerprint($this->reason, Auth::user()));

        return true;
    }
}
