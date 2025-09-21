<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

trait Trendable
{
    public function getTrendingScore(?Carbon $referenceTime = null): float
    {
        $referenceTime ??= now();
        $cacheKey = $this->getTrendingCacheKey($referenceTime);

        if (Config::get('trending.cache.cache_scores', true)) {
            return Cache::remember(
                $cacheKey,
                now()->addMinutes(Config::get('trending.cache.duration', 60)),
                fn (): float => $this->calculateTrendingScore($referenceTime)
            );
        }

        return $this->calculateTrendingScore($referenceTime);
    }

    public function scopeTrending(Builder $query, ?int $limit = null, ?Carbon $referenceTime = null): void
    {
        $limit ??= Config::get('trending.query.default_limit', 50);
        $referenceTime ??= now();
        $minThreshold = Config::get('trending.query.min_engagement_threshold', 1);

        $tableName = $this->getTable();
        $modelClass = addslashes(static::class);

        // Use CTE for optimized trending calculation without redundant score computation
        $query->fromRaw("(
            WITH topic_stats AS (
                SELECT
                    `$tableName`.*,
                    (
                        LOG(1 + COALESCE(view_stats.total_views, 0)) * ? +
                        LOG(1 + COALESCE(view_stats.unique_views, 0)) * ? +
                        LOG(1 + COALESCE(post_stats.post_count, 0)) * ? +
                        LOG(1 + COALESCE(read_stats.read_count, 0)) * ? +
                        LOG(1 + COALESCE(like_stats.like_count, 0)) * ?
                    ) AS raw_score,
                    TIMESTAMPDIFF(HOUR, `$tableName`.created_at, ?) AS hours_since
                FROM `$tableName`
                LEFT JOIN (
                    SELECT
                        `viewable_id`,
                        SUM(`count`) as total_views,
                        COUNT(DISTINCT `fingerprint_id`) as unique_views
                    FROM `views`
                    WHERE `viewable_type` = '$modelClass'
                    GROUP BY `viewable_id`
                ) AS view_stats ON view_stats.viewable_id = `$tableName`.id
                LEFT JOIN (
                    SELECT
                        `topic_id`,
                        COUNT(*) as post_count
                    FROM `posts`
                    WHERE `type` = 'forum'
                    GROUP BY `topic_id`
                ) AS post_stats ON post_stats.topic_id = `$tableName`.id
                LEFT JOIN (
                    SELECT
                        `readable_id`,
                        COUNT(*) as read_count
                    FROM `reads`
                    WHERE `readable_type` = '$modelClass'
                    GROUP BY `readable_id`
                ) AS read_stats ON read_stats.readable_id = `$tableName`.id
                LEFT JOIN (
                    SELECT
                        `posts`.`topic_id`,
                        COUNT(`likes`.`id`) as like_count
                    FROM `posts`
                    LEFT JOIN `likes` ON `likes`.`likeable_type` = 'App\\\\Models\\\\Post' AND `likes`.`likeable_id` = `posts`.`id`
                    WHERE `posts`.`type` = 'forum'
                    GROUP BY `posts`.`topic_id`
                ) AS like_stats ON like_stats.topic_id = `$tableName`.id
            ),
            final_scores AS (
                SELECT *,
                    CASE
                        WHEN raw_score = 0 THEN 0
                        WHEN hours_since <= ? THEN raw_score * ?
                        WHEN hours_since >= ? THEN raw_score * ?
                        ELSE raw_score * POW(0.5, hours_since / ?)
                    END AS trending_score
                FROM topic_stats
            )
            SELECT *
            FROM final_scores
            WHERE trending_score >= ?
            ORDER BY trending_score DESC
            LIMIT ?
        ) as trending_topics", [
            // Parameters for raw score calculation
            Config::get('trending.weights.views', 1.0),
            Config::get('trending.weights.unique_views', 1.5),
            Config::get('trending.weights.posts', 3.0),
            Config::get('trending.weights.reads', 2.0),
            Config::get('trending.weights.likes', 2.5),
            $referenceTime,
            // Parameters for time decay
            Config::get('trending.decay.recency_boost.threshold_hours', 24),
            Config::get('trending.decay.recency_boost.multiplier', 2.0),
            Config::get('trending.decay.old_content.threshold_hours', 720),
            Config::get('trending.decay.old_content.multiplier', 0.1),
            Config::get('trending.decay.half_life', 168),
            // Parameters for filtering and limiting
            $minThreshold,
            $limit,
        ]);
    }

    public function scopeTrendingInTimeframe(Builder $query, string $timeframe = 'week', ?int $limit = null): void
    {
        $limit ??= Config::get('trending.query.default_limit', 50);
        $timeframes = Config::get('trending.query.timeframes', []);

        if (! isset($timeframes[$timeframe])) {
            throw new InvalidArgumentException("Invalid timeframe: {$timeframe}");
        }

        $hours = $timeframes[$timeframe];

        if ($hours !== null) {
            $query->where('created_at', '>=', now()->subHours($hours));
        }

        $this->scopeTrending($query, $limit);
    }

    public function scopeHotTopics(Builder $query, ?int $limit = null): void
    {
        $this->scopeTrendingInTimeframe($query, 'day', $limit);
    }

    public function scopeRisingTopics(Builder $query, ?int $limit = null): void
    {
        $limit ??= Config::get('trending.query.default_limit', 50);

        $query->where('created_at', '>=', now()->subHours(48))
            ->whereHas('posts', function (Builder $postQuery): void {
                $postQuery->where('created_at', '>=', now()->subHours(24));
            });

        $this->scopeTrending($query, $limit);
    }

    public function trendingScore(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                // If the trending_score was calculated by a query scope, use that value
                if (isset($this->attributes['trending_score'])) {
                    return (float) $this->attributes['trending_score'];
                }

                // Otherwise, calculate it using the model attributes
                return $this->calculateTrendingScore(now());
            }
        )->shouldCache();
    }

    public function clearTrendingCache(): bool
    {
        $this->getKey();
        Config::get('trending.cache.prefix', 'trending');
        // In production, you might want to use a more sophisticated cache clearing mechanism
        // For now, we'll just clear the current cache keys
        $cacheKeys = [
            $this->getTrendingCacheKey(now()),
            $this->getTrendingCacheKey(now()->subHour()),
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        return true;
    }

    protected function calculateTrendingScore(Carbon $referenceTime): float
    {
        $engagementScore = $this->calculateEngagementScore();
        $timeMultiplier = $this->calculateTimeMultiplier($referenceTime);

        return $engagementScore * $timeMultiplier;
    }

    protected function calculateEngagementScore(): float
    {
        $weights = Config::get('trending.weights', []);
        $score = 0.0;

        // Views contribution
        if (method_exists($this, 'views_count')) {
            $score += ($this->views_count ?? 0) * ($weights['views'] ?? 1.0);
        }

        // Unique views contribution
        if (method_exists($this, 'unique_views_count')) {
            $score += ($this->unique_views_count ?? 0) * ($weights['unique_views'] ?? 1.5);
        }

        // Posts/replies contribution
        if (method_exists($this, 'posts_count')) {
            $score += ($this->posts_count ?? 0) * ($weights['posts'] ?? 3.0);
        }

        // Reads contribution
        if (method_exists($this, 'reads_count')) {
            $score += ($this->reads_count ?? 0) * ($weights['reads'] ?? 2.0);
        }

        // Likes from related posts
        $likesScore = $this->calculateLikesScore();

        return $score + $likesScore * ($weights['likes'] ?? 2.5);
    }

    protected function calculateLikesScore(): float
    {
        if (! method_exists($this, 'posts')) {
            return 0.0;
        }

        return (float) $this->posts()
            ->withCount('likes')
            ->get()
            ->sum('likes_count');
    }

    protected function calculateTimeMultiplier(Carbon $referenceTime): float
    {
        $ageInHours = $this->created_at->diffInHours($referenceTime);
        $decayConfig = Config::get('trending.decay', []);

        // Recency boost for new content
        $recencyConfig = $decayConfig['recency_boost'] ?? [];
        if ($ageInHours <= ($recencyConfig['threshold_hours'] ?? 24)) {
            return $recencyConfig['multiplier'] ?? 2.0;
        }

        // Sharp drop-off for old content
        $oldContentConfig = $decayConfig['old_content'] ?? [];
        if ($ageInHours >= ($oldContentConfig['threshold_hours'] ?? 720)) {
            return $oldContentConfig['multiplier'] ?? 0.1;
        }

        // Exponential decay for content in between
        $halfLifeHours = $decayConfig['half_life'] ?? 168; // 7 days default

        return 0.5 ** ($ageInHours / $halfLifeHours);
    }

    protected function getTrendingCacheKey(?Carbon $referenceTime = null): string
    {
        $referenceTime ??= now();
        $prefix = Config::get('trending.cache.prefix', 'trending');

        return sprintf(
            '%s:%s:%s:%s',
            $prefix,
            static::class,
            $this->getKey(),
            $referenceTime->format('Y-m-d-H')
        );
    }

    protected function initializeTrendable(): void
    {
        $this->mergeAppends([
            'trending_score',
        ]);
    }
}
