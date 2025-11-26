<?php

declare(strict_types=1);

namespace App\Listeners\Cache;

use App\Enums\ProductType;
use App\Events\PriceCreated;
use App\Events\PriceDeleted;
use App\Events\PriceUpdated;
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

    public function handle(ProductCreated|ProductUpdated|ProductDeleted|PriceCreated|PriceUpdated|PriceDeleted $event): void
    {
        if (in_array($event::class, [ProductCreated::class, ProductUpdated::class, ProductDeleted::class]) && $event->product->type !== ProductType::Subscription) {
            return;
        }

        if (in_array($event::class, [PriceCreated::class, PriceUpdated::class, PriceDeleted::class]) && $event->price->product->type !== ProductType::Subscription) {
            return;
        }

        $this->cache->purgeByKey('subscriptions.index');
    }
}
