<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Action;
use App\Jobs\Users\SyncGroups;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\LazyCollection;
use Throwable;

class CreateSyncGroupsBatchAction extends Action
{
    public function __construct(
        protected Collection $userIds,
        protected int $chunkSize = 1000,
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): bool
    {
        $this->userIds->lazy()->chunk($this->chunkSize)->each(function (LazyCollection $chunk, int $index): void {
            $jobs = $chunk->map(fn (int $userId): SyncGroups => new SyncGroups(
                userId: $userId,
            ))->all();

            Bus::batch($jobs)
                ->name('Sync User Groups (Chunk '.($index + 1).')')
                ->dispatch();
        });

        return true;
    }
}
