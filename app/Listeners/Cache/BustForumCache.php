<?php

declare(strict_types=1);

namespace App\Listeners\Cache;

use App\Enums\PostType;
use App\Events\ForumCategoryCreated;
use App\Events\ForumCategoryDeleted;
use App\Events\ForumCategoryGroupCreated;
use App\Events\ForumCategoryGroupDeleted;
use App\Events\ForumCategoryGroupUpdated;
use App\Events\ForumCategoryUpdated;
use App\Events\ForumCreated;
use App\Events\ForumDeleted;
use App\Events\ForumGroupCreated;
use App\Events\ForumGroupDeleted;
use App\Events\ForumGroupUpdated;
use App\Events\ForumUpdated;
use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostUpdated;
use App\Events\TopicCreated;
use App\Events\TopicDeleted;
use App\Events\TopicUpdated;
use App\Events\UserGroupCreated;
use App\Events\UserGroupDeleted;
use App\Services\CacheService;

class BustForumCache
{
    public function __construct(
        private readonly CacheService $cache,
    ) {
        //
    }

    public function handle(
        ForumCategoryCreated|
        ForumCategoryUpdated|
        ForumCategoryDeleted|
        ForumCreated|
        ForumUpdated|
        ForumDeleted|
        TopicCreated|
        TopicUpdated|
        TopicDeleted|
        PostCreated|
        PostUpdated|
        PostDeleted|
        ForumGroupCreated|
        ForumGroupUpdated|
        ForumGroupDeleted|
        ForumCategoryGroupCreated|
        ForumCategoryGroupUpdated|
        ForumCategoryGroupDeleted|
        UserGroupCreated|
        UserGroupDeleted $event
    ): void {
        if (in_array($event::class, [PostCreated::class, PostUpdated::class, PostDeleted::class]) && $event->post->type !== PostType::Forum) {
            return;
        }

        $this->cache->purgeByKey('forums.categories.index');
    }
}
