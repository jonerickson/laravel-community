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
            return (float) Cache::remember(
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

        $query->fromRaw("(
            WITH topic_stats AS (
                SELECT
                    `{$tableName}`.*,
                    (
                        LOG(1 + COALESCE(view_stats.total_views, 0)) * ? +
                        LOG(1 + COALESCE(view_stats.unique_views, 0)) * ? +
                        LOG(1 + COALESCE(post_stats.post_count, 0)) * ? +
                        LOG(1 + COALESCE(read_stats.read_count, 0)) * ? +
                        LOG(1 + COALESCE(like_stats.like_count, 0)) * ?
                    ) AS raw_score,
                    TIMESTAMPDIFF(HOUR, `{$tableName}`.created_at, ?) AS hours_since
                FROM `{$tableName}`
                LEFT JOIN (
                    SELECT
                        `viewable_id`,
                        SUM(`count`) as total_views,
                        COUNT(DISTINCT `fingerprint_id`) as unique_views
                    FROM `views`
                    WHERE `viewable_type` = '{$modelClass}'
                    GROUP BY `viewable_id`
                ) AS view_stats ON view_stats.viewable_id = `{$tableName}`.id
                LEFT JOIN (
                    SELECT
                        `topic_id`,
                        COUNT(*) as post_count
                    FROM `posts`
                    WHERE `type` = 'forum'
                    GROUP BY `topic_id`
                ) AS post_stats ON post_stats.topic_id = `{$tableName}`.id
                LEFT JOIN (
                    SELECT
                        `readable_id`,
                        COUNT(*) as read_count
                    FROM `reads`
                    WHERE `readable_type` = '{$modelClass}'
                    GROUP BY `readable_id`
                ) AS read_stats ON read_stats.readable_id = `{$tableName}`.id
                LEFT JOIN (
                    SELECT
                        `posts`.`topic_id`,
                        COUNT(`likes`.`id`) as like_count
                    FROM `posts`
                    LEFT JOIN `likes` ON `likes`.`likeable_type` = 'App\\\\Models\\\\Post' AND `likes`.`likeable_id` = `posts`.`id`
                    WHERE `posts`.`type` = 'forum'
                    GROUP BY `posts`.`topic_id`
                ) AS like_stats ON like_stats.topic_id = `{$tableName}`.id
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
            throw new InvalidArgumentException('Invalid timeframe: '.$timeframe);
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

        $this->scopeTrending($query, $limit);

        $query->where('created_at', '>=', now()->subHours(48))
            ->whereIn('id', function ($subQuery): void {
                $subQuery->select('topic_id')
                    ->from('posts')
                    ->where('created_at', '>=', now()->subHours(24));
            });
    }

    public function trendingScore(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if (isset($this->attributes['trending_score'])) {
                    return (float) $this->attributes['trending_score'];
                }

                return $this->getTrendingScore();
            }
        )->shouldCache();
    }

    public function clearTrendingCache(): bool
    {
        $this->getKey();
        Config::get('trending.cache.prefix', 'trending');

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

        if (isset($this->views)) {
            $score += $this->views->count() * ($weights['views'] ?? 1.0);
            $score += $this->views->unique('fingerprint_id')->count() * ($weights['unique_views'] ?? 1.5);
        }

        if (isset($this->posts)) {
            $score += $this->posts->count() * ($weights['posts'] ?? 3.0);
        }

        if (isset($this->reads)) {
            $score += $this->reads->count() * ($weights['reads'] ?? 2.0);
        }

        $likesScore = $this->calculateLikesScore();

        return $score + $likesScore * ($weights['likes'] ?? 2.5);
    }

    protected function calculateLikesScore(): float
    {
        if (! property_exists($this, 'posts')) {
            return 0.0;
        }

        return (float) $this->posts
            ->loadCount('likes')
            ->sum('likes_count');
    }

    protected function calculateTimeMultiplier(Carbon $referenceTime): float
    {
        $ageInHours = $this->created_at->diffInHours($referenceTime);
        $decayConfig = Config::get('trending.decay', []);

        $recencyConfig = $decayConfig['recency_boost'] ?? [];
        if ($ageInHours <= ($recencyConfig['threshold_hours'] ?? 24)) {
            return $recencyConfig['multiplier'] ?? 2.0;
        }

        $oldContentConfig = $decayConfig['old_content'] ?? [];
        if ($ageInHours >= ($oldContentConfig['threshold_hours'] ?? 720)) {
            return $oldContentConfig['multiplier'] ?? 0.1;
        }

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
