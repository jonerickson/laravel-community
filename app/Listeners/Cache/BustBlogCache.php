<?php

declare(strict_types=1);

namespace App\Listeners\Cache;

use App\Enums\PostType;
use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostUpdated;
use App\Services\CacheService;

class BustBlogCache
{
    public function __construct(
        private readonly CacheService $cache,
    ) {
        //
    }

    public function handle(PostCreated|PostUpdated|PostDeleted $event): void
    {
        if ($event->post->type !== PostType::Blog) {
            return;
        }

        $this->cache->purgeByKey('blog.index');
    }
}
