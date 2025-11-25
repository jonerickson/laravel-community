<?php

declare(strict_types=1);

namespace App\Listeners\Cache;

use App\Enums\PostType;
use App\Events\ForumCategoryCreated;
use App\Events\ForumCategoryDeleted;
use App\Events\ForumCategoryUpdated;
use App\Events\ForumCreated;
use App\Events\ForumDeleted;
use App\Events\ForumUpdated;
use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostUpdated;
use App\Events\TopicCreated;
use App\Events\TopicDeleted;
use App\Events\TopicUpdated;
use App\Services\CacheService;

class BustForumCache
{
    public function __construct(
        private readonly CacheService $cache,
    ) {
        //
    }

    public function handle(ForumCategoryCreated|ForumCategoryUpdated|ForumCategoryDeleted|ForumCreated|ForumUpdated|ForumDeleted|TopicCreated|TopicUpdated|TopicDeleted|PostCreated|PostUpdated|PostDeleted $event): void
    {
        if (in_array(get_class($event), [PostCreated::class, PostUpdated::class, PostDeleted::class])) {
            if ($event->post->type !== PostType::Forum) {
                return;
            }
        }

        $this->cache->purgeByKey('forums.categories.index');
    }
}
