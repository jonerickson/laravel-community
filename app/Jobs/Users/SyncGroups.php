<?php

declare(strict_types=1);

namespace App\Jobs\Users;

use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGroups implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public function __construct(
        protected int $userId
    ) {
        //
    }

    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $user = User::findOrFail($this->userId);

        $user->syncGroups();
    }
}
