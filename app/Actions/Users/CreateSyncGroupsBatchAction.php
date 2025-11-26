<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Action;
use App\Jobs\Users\SyncGroups;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Throwable;

class CreateSyncGroupsBatchAction extends Action
{
    public function __construct(
        protected Collection $users,
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): bool
    {
        $batch = Bus::batch([]);

        $this->users->each(fn (User $user) => $batch->add(new SyncGroups(
            userId: $user->id,
        )));

        $batch->name('Sync User Groups')->dispatch();

        return true;
    }
}
