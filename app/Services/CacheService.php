<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ForumCategoryData;
use App\Data\PostData;
use App\Data\ProductData;
use App\Models\Comment;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Post;
use App\Models\Price;
use App\Models\Product;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CacheService
{
    public function purgeByKey(string $key): void
    {
        Cache::forget($key);
    }

    public function getByKey(string $key): ?array
    {
        $method = Str::of($key)
            ->explode('.')
            ->map(fn (string $part, int $index) => $index === 0 ? Str::of($part)->trim() : Str::of($part)->trim()->studly())
            ->join('');

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return null;
    }

    protected function blogIndex()
    {
        return Cache::flexible('blog.index', [60 * 60, 60 * 60 * 24], fn () => PostData::collect(Post::query()
            ->blog()
            ->with(['comments', 'author.groups', 'reads'])
            ->withCount(['views', 'comments'])
            ->published()
            ->latest()
            ->get()
        )->toArray());
    }

    protected function subscriptionsIndex()
    {
        return Cache::flexible('subscriptions.index', [60 * 60 * 24, 60 * 60 * 48], fn () => ProductData::collect(Product::query()
            ->subscriptions()
            ->visible()
            ->with(['approvedReviews' => fn (MorphMany|Comment $query) => $query->latest()])
            ->with(['prices' => fn (HasMany|Price $query) => $query->recurring()->active()])
            ->with(['categories', 'policies.category', 'defaultPrice'])
            ->ordered()
            ->get()
        )->toArray());
    }

    protected function forumsCategoriesIndex(): array
    {
        return Cache::flexible('forums.categories.index', [60 * 10, 60 * 60], fn () => ForumCategoryData::collect(ForumCategory::query()
            ->active()
            ->ordered()
            ->with(['groups'])
            ->with(['forums' => function (HasMany|Forum $query): void {
                $query
                    ->whereNull('parent_id')
                    ->withCount(['topics', 'posts'])
                    ->active()
                    ->ordered()
                    ->with(['groups'])
                    ->with(['latestTopics' => function (HasMany|Topic $subQuery): void {
                        $subQuery
                            ->withCount('posts')
                            ->with(['author', 'lastPost'])
                            ->limit(3);
                    }]);
            }])
            ->get()
            ->loadCount([
                'forums as posts_count' => function ($query): void {
                    $query->join('topics', 'topics.forum_id', '=', 'forums.id')
                        ->join('posts', 'posts.topic_id', '=', 'topics.id');
                },
            ])
        )->toArray());
    }
}
