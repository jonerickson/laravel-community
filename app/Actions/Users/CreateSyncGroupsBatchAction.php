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
        protected int $chunkSize = 1000,
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): bool
    {
        $this->users->chunk($this->chunkSize)->each(function (Collection $chunk, int $index): void {
            $jobs = $chunk->map(fn (User $user): SyncGroups => new SyncGroups(
                userId: $user->id,
            ))->all();

            Bus::batch($jobs)
                ->name('Sync User Groups (Chunk '.($index + 1).')')
                ->dispatch();
        });

        return true;
    }
}
