<?php

declare(strict_types=1);

namespace App\Listeners\Cache;

use App\Enums\ProductType;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Services\CacheService;

class BustSubscriptionCache
{
    public function __construct(
        private readonly CacheService $cache,
    ) {
        //
    }

    public function handle(ProductCreated|ProductUpdated|ProductDeleted $event): void
    {
        if ($event->product->type !== ProductType::Subscription) {
            return;
        }

        $this->cache->purgeByKey('subscriptions.index');
    }
}
