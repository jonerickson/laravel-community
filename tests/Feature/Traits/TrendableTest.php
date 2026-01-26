<?php

declare(strict_types=1);

use App\Models\Forum;
use App\Models\Like;
use App\Models\Post;
use App\Models\Read;
use App\Models\Topic;
use App\Models\User;
use App\Models\View;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->forum = Forum::factory()->create();
    $this->topic = Topic::factory()->create([
        'forum_id' => $this->forum->id,
        'created_by' => $this->user->id,
    ]);
});

describe('trending score calculations', function (): void {
    it('calculates trending score with default weights', function (): void {
        // Create engagement metrics
        View::factory()->count(10)->create(['viewable_type' => Topic::class, 'viewable_id' => $this->topic->id]);
        Read::factory()->create(['readable_type' => Topic::class, 'readable_id' => $this->topic->id, 'created_by' => $this->user->id]);

        $posts = Post::factory()->count(3)->create([
            'topic_id' => $this->topic->id,
            'created_by' => $this->user->id,
        ]);

        $posts->each(function (Post $post): void {
            Like::factory()->count(2)->create(['likeable_type' => Post::class, 'likeable_id' => $post->id]);
        });

        $score = $this->topic->getTrendingScore();

        expect($score)->toBeGreaterThan(0);
        expect($score)->toBeFloat();
    });

    it('applies recency boost for new content', function (): void {
        $newTopic = Topic::factory()->create([
            'forum_id' => $this->forum->id,
            'created_by' => $this->user->id,
            'created_at' => now()->subHours(12), // Within 24 hours
        ]);

        $oldTopic = Topic::factory()->create([
            'forum_id' => $this->forum->id,
            'created_by' => $this->user->id,
            'created_at' => now()->subDays(3),
        ]);

        // Add same engagement to both
        View::factory()->count(5)->create(['viewable_type' => Topic::class, 'viewable_id' => $newTopic->id]);
        View::factory()->count(5)->create(['viewable_type' => Topic::class, 'viewable_id' => $oldTopic->id]);

        $newScore = $newTopic->getTrendingScore();
        $oldScore = $oldTopic->getTrendingScore();

        expect($newScore)->toBeGreaterThan($oldScore);
    });

    it('applies sharp drop-off for very old content', function (): void {
        $recentTopic = Topic::factory()->create([
            'forum_id' => $this->forum->id,
            'created_by' => $this->user->id,
            'created_at' => now()->subDays(5),
        ]);

        $veryOldTopic = Topic::factory()->create([
            'forum_id' => $this->forum->id,
            'created_by' => $this->user->id,
            'created_at' => now()->subDays(35), // Over 30 days
        ]);

        // Add same engagement to both
        View::factory()->count(100)->create(['viewable_type' => Topic::class, 'viewable_id' => $recentTopic->id]);
        View::factory()->count(100)->create(['viewable_type' => Topic::class, 'viewable_id' => $veryOldTopic->id]);

        $recentScore = $recentTopic->getTrendingScore();
        $oldScore = $veryOldTopic->getTrendingScore();

        expect($recentScore)->toBeGreaterThan($oldScore * 5); // Should be significantly higher
    });

    it('respects configurable weights', function (): void {
        Config::set('trending.weights.views', 10.0);
        Config::set('trending.weights.posts', 1.0);

        $viewHeavyTopic = Topic::factory()->create([
            'forum_id' => $this->forum->id,
            'created_by' => $this->user->id,
        ]);

        $postHeavyTopic = Topic::factory()->create([
            'forum_id' => $this->forum->id,
            'created_by' => $this->user->id,
        ]);

        View::factory()->count(10)->create(['viewable_type' => Topic::class, 'viewable_id' => $viewHeavyTopic->id]);
        Post::factory()->count(10)->create([
            'topic_id' => $postHeavyTopic->id,
            'created_by' => $this->user->id,
        ]);

        $viewScore = $viewHeavyTopic->getTrendingScore();
        $postScore = $postHeavyTopic->getTrendingScore();

        expect($viewScore)->toBeGreaterThan($postScore);
    });

    it('handles zero engagement gracefully', function (): void {
        $score = $this->topic->getTrendingScore();

        expect($score)->toEqual(0.0);
    });
});

describe('trending query scopes', function (): void {
    beforeEach(function (): void {
        // Create multiple topics with different engagement levels and ages
        $this->hotTopic = Topic::factory()->create([
            'forum_id' => $this->forum->id,
            'created_by' => $this->user->id,
            'created_at' => now()->subHours(12),
        ]);

        $this->moderateTopic = Topic::factory()->create([
            'forum_id' => $this->forum->id,
            'created_by' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);

        $this->coldTopic = Topic::factory()->create([
            'forum_id' => $this->forum->id,
            'created_by' => $this->user->id,
            'created_at' => now()->subWeeks(2),
        ]);

        // Add high engagement to hot topic
        View::factory()->count(50)->create(['viewable_type' => Topic::class, 'viewable_id' => $this->hotTopic->id]);
        Post::factory()->count(10)->create([
            'topic_id' => $this->hotTopic->id,
            'created_by' => $this->user->id,
        ]);

        // Medium engagement to moderate topic
        View::factory()->count(20)->create(['viewable_type' => Topic::class, 'viewable_id' => $this->moderateTopic->id]);
        Post::factory()->count(5)->create([
            'topic_id' => $this->moderateTopic->id,
            'created_by' => $this->user->id,
        ]);

        // Low engagement to cold topic
        View::factory()->count(5)->create(['viewable_type' => Topic::class, 'viewable_id' => $this->coldTopic->id]);
    });

    it('orders topics by trending score correctly', function (): void {
        $trendingTopics = Topic::trending(10)->get();

        expect($trendingTopics)->toHaveCount(3);
        expect($trendingTopics->first()->id)->toEqual($this->hotTopic->id);
        expect($trendingTopics->last()->id)->toEqual($this->coldTopic->id);
    });

    it('respects limit parameter', function (): void {
        $trendingTopics = Topic::trending(2)->get();

        expect($trendingTopics)->toHaveCount(2);
    });

    it('filters by timeframe correctly', function (): void {
        $dayTrending = Topic::trendingInTimeframe('day')->get();
        $weekTrending = Topic::trendingInTimeframe('week')->get();

        expect($dayTrending)->toHaveCount(1);
        expect($dayTrending->first()->id)->toEqual($this->hotTopic->id);

        expect($weekTrending)->toHaveCount(2);
    });

    it('returns hot topics correctly', function (): void {
        $hotTopics = Topic::hotTopics()->get();

        expect($hotTopics)->toHaveCount(1);
        expect($hotTopics->first()->id)->toEqual($this->hotTopic->id);
    });

    it('finds rising topics', function (): void {
        // Create a topic with recent posts activity
        $risingTopic = Topic::factory()->create([
            'forum_id' => $this->forum->id,
            'created_by' => $this->user->id,
            'created_at' => now()->subHours(36),
        ]);

        Post::factory()->count(3)->create([
            'topic_id' => $risingTopic->id,
            'created_by' => $this->user->id,
            'created_at' => now()->subHours(12), // Recent posts
        ]);

        $risingTopics = Topic::risingTopics()->get();

        expect($risingTopics->pluck('id'))->toContain($risingTopic->id);
    });

    it('respects minimum engagement threshold', function (): void {
        Config::set('trending.query.min_engagement_threshold', 100);

        $trendingTopics = Topic::trending()->get();

        expect($trendingTopics)->toHaveCount(0);
    });
});

describe('caching behavior', function (): void {
    it('caches trending scores when enabled', function (): void {
        Config::set('trending.cache.cache_scores', true);

        View::factory()->count(10)->create(['viewable_type' => Topic::class, 'viewable_id' => $this->topic->id]);

        $score1 = $this->topic->refresh()->getTrendingScore();
        $score2 = $this->topic->refresh()->getTrendingScore();

        expect($score1)->toEqual($score2);
    });

    it('does not cache when disabled', function (): void {
        Config::set('trending.cache.cache_scores', false);

        View::factory()->count(10)->create(['viewable_type' => Topic::class, 'viewable_id' => $this->topic->id]);

        $score1 = $this->topic->refresh()->getTrendingScore();

        // Add more engagement
        View::factory()->count(10)->create(['viewable_type' => Topic::class, 'viewable_id' => $this->topic->id]);

        $score2 = $this->topic->refresh()->getTrendingScore();

        // Without caching, the second score should be higher
        expect($score2)->toBeGreaterThan($score1);
    });

    it('clears cache correctly', function (): void {
        Config::set('trending.cache.cache_scores', true);

        View::factory()->count(10)->create(['viewable_type' => Topic::class, 'viewable_id' => $this->topic->id]);

        $initialScore = $this->topic->refresh()->getTrendingScore();

        // Add more engagement
        View::factory()->count(10)->create(['viewable_type' => Topic::class, 'viewable_id' => $this->topic->id]);

        // Should still return cached score
        $cachedScore = $this->topic->refresh()->getTrendingScore();
        expect($cachedScore)->toEqual($initialScore);

        // Clear cache and get updated score
        $this->topic->clearTrendingCache();
        $updatedScore = $this->topic->refresh()->getTrendingScore();

        expect($updatedScore)->toBeGreaterThan($initialScore);
    });
});

describe('edge cases', function (): void {
    it('handles topics without related models', function (): void {
        $emptyTopic = Topic::factory()->create([
            'forum_id' => $this->forum->id,
            'created_by' => $this->user->id,
        ]);

        $score = $emptyTopic->getTrendingScore();

        expect($score)->toEqual(0.0);
    });

    it('works with different reference times', function (): void {
        View::factory()->count(10)->create(['viewable_type' => Topic::class, 'viewable_id' => $this->topic->id]);

        $pastTime = now()->subDays(1);
        $futureTime = now()->addDays(1);

        $pastScore = $this->topic->getTrendingScore($pastTime);
        $futureScore = $this->topic->getTrendingScore($futureTime);

        expect($pastScore)->toBeGreaterThan($futureScore);
    });

    it('throws exception for invalid timeframes', function (): void {
        expect(fn () => Topic::trendingInTimeframe('invalid')->get())
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('trending score attribute', function (): void {
    it('exposes trending score as model attribute', function (): void {
        View::factory()->count(10)->create(['viewable_type' => Topic::class, 'viewable_id' => $this->topic->id]);

        $topic = $this->topic->fresh();

        expect($topic->trending_score)->toBeFloat();
        expect($topic->trending_score)->toBeGreaterThan(0);
    });
});
