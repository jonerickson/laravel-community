<?php

declare(strict_types=1);

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

describe('Topic forum relationship', function (): void {
    test('returns the forum the topic belongs to', function (): void {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id]);

        expect($topic->forum->id)->toBe($forum->id);
    });

    test('forum relationship is BelongsTo', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->forum())->toBeInstanceOf(BelongsTo::class);
    });
});

describe('Topic posts relationship', function (): void {
    test('returns empty collection when topic has no posts', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->posts)->toBeEmpty();
    });

    test('returns forum-type posts belonging to topic', function (): void {
        $topic = Topic::factory()->create();
        $post = Post::factory()->forum()->create(['topic_id' => $topic->id]);

        $posts = $topic->posts;

        expect($posts)->toHaveCount(1);
        expect($posts->first()->id)->toBe($post->id);
    });

    test('does not return blog-type posts', function (): void {
        $topic = Topic::factory()->create();
        Post::factory()->blog()->create(['topic_id' => $topic->id]);

        expect($topic->posts)->toBeEmpty();
    });

    test('does not return posts from other topics', function (): void {
        $topic1 = Topic::factory()->create();
        $topic2 = Topic::factory()->create();

        Post::factory()->forum()->create(['topic_id' => $topic1->id]);
        Post::factory()->forum()->create(['topic_id' => $topic2->id]);

        expect($topic1->posts)->toHaveCount(1);
    });

    test('posts relationship is HasMany', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->posts())->toBeInstanceOf(HasMany::class);
    });
});

describe('Topic lastPost relationship', function (): void {
    test('returns the most recent forum post', function (): void {
        $topic = Topic::factory()->create();
        Post::factory()->forum()->create(['topic_id' => $topic->id, 'created_at' => now()->subDay()]);
        $latest = Post::factory()->forum()->create(['topic_id' => $topic->id, 'created_at' => now()]);

        expect($topic->lastPost->id)->toBe($latest->id);
    });

    test('returns null when topic has no posts', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->lastPost)->toBeNull();
    });

    test('lastPost relationship is HasOne', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->lastPost())->toBeInstanceOf(HasOne::class);
    });
});

describe('Topic author relationship (HasAuthor)', function (): void {
    test('returns the author who created the topic', function (): void {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['created_by' => $user->id]);

        expect($topic->author->id)->toBe($user->id);
    });

    test('returns default guest when created_by is null', function (): void {
        $topic = Topic::factory()->create(['created_by' => null]);

        expect($topic->author->name)->toBe('Guest');
        expect($topic->author->id)->toBe(0);
    });

    test('author relationship is BelongsTo', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->author())->toBeInstanceOf(BelongsTo::class);
    });
});

describe('Topic Followable trait', function (): void {
    test('topic creator auto-follows the topic on creation', function (): void {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['created_by' => $user->id]);

        expect($topic->refresh()->followers)->toHaveCount(1);
        expect($topic->followers->first()->created_by)->toBe($user->id);
    });

    test('follow adds a follower', function (): void {
        $topic = Topic::factory()->create(['created_by' => null]);
        $user = User::factory()->create();

        $topic->follow($user);

        expect($topic->refresh()->followers)->toHaveCount(1);
    });

    test('unfollow removes a follower', function (): void {
        $topic = Topic::factory()->create(['created_by' => null]);
        $user = User::factory()->create();

        $topic->follow($user);
        $topic->unfollow($user);

        expect($topic->refresh()->followers)->toBeEmpty();
    });
});

describe('Topic Lockable trait', function (): void {
    test('topic is not locked by default', function (): void {
        $topic = Topic::factory()->create(['is_locked' => false]);

        expect($topic->is_locked)->toBeFalse();
    });

    test('lock method sets is_locked to true', function (): void {
        $topic = Topic::factory()->create(['is_locked' => false]);
        $topic->lock();

        expect($topic->refresh()->is_locked)->toBeTrue();
    });

    test('unlock method sets is_locked to false', function (): void {
        $topic = Topic::factory()->create(['is_locked' => true]);
        $topic->unlock();

        expect($topic->refresh()->is_locked)->toBeFalse();
    });

    test('locked scope returns only locked topics', function (): void {
        Topic::factory()->create(['is_locked' => true]);
        Topic::factory()->create(['is_locked' => false]);

        expect(Topic::query()->locked()->count())->toBe(1);
    });

    test('unlocked scope returns only unlocked topics', function (): void {
        Topic::factory()->create(['is_locked' => true]);
        Topic::factory()->create(['is_locked' => false]);

        expect(Topic::query()->unlocked()->count())->toBe(1);
    });
});

describe('Topic Pinnable trait', function (): void {
    test('topic is not pinned by default', function (): void {
        $topic = Topic::factory()->create(['is_pinned' => false]);

        expect($topic->is_pinned)->toBeFalse();
    });

    test('pin method sets is_pinned to true', function (): void {
        $topic = Topic::factory()->create(['is_pinned' => false]);
        $topic->pin();

        expect($topic->refresh()->is_pinned)->toBeTrue();
    });

    test('unpin method sets is_pinned to false', function (): void {
        $topic = Topic::factory()->create(['is_pinned' => true]);
        $topic->unpin();

        expect($topic->refresh()->is_pinned)->toBeFalse();
    });

    test('pinned scope returns only pinned topics', function (): void {
        Topic::factory()->create(['is_pinned' => true]);
        Topic::factory()->create(['is_pinned' => false]);

        expect(Topic::query()->pinned()->count())->toBe(1);
    });

    test('notPinned scope returns only unpinned topics', function (): void {
        Topic::factory()->create(['is_pinned' => true]);
        Topic::factory()->create(['is_pinned' => false]);

        expect(Topic::query()->notPinned()->count())->toBe(1);
    });
});

describe('Topic Viewable trait', function (): void {
    test('returns empty collection when topic has no views', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->views)->toBeEmpty();
    });

    test('recordView creates a view record', function (): void {
        $topic = Topic::factory()->create();
        $topic->recordView('test-fingerprint-123');

        expect($topic->views)->toHaveCount(1);
    });
});

describe('Topic Readable trait', function (): void {
    test('returns empty collection when topic has no reads', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->reads)->toBeEmpty();
    });

    test('markAsRead creates a read record', function (): void {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        $topic->markAsRead($user);

        expect($topic->refresh()->reads)->toHaveCount(1);
    });

    test('isReadBy returns true after marking as read', function (): void {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        $topic->markAsRead($user);

        expect($topic->refresh()->isReadBy($user))->toBeTrue();
    });
});

describe('Topic slug generation', function (): void {
    test('generates slug from title', function (): void {
        $topic = Topic::factory()->create(['title' => 'How To Install Laravel']);

        expect($topic->slug)->toBe('how-to-install-laravel');
    });
});

describe('Topic latestActivity scope', function (): void {
    test('orders by pinned first then updated_at descending', function (): void {
        $forum = Forum::factory()->create();
        $pinned = Topic::factory()->create(['forum_id' => $forum->id, 'is_pinned' => true, 'updated_at' => now()->subDay()]);
        $recent = Topic::factory()->create(['forum_id' => $forum->id, 'is_pinned' => false, 'updated_at' => now()]);
        $old = Topic::factory()->create(['forum_id' => $forum->id, 'is_pinned' => false, 'updated_at' => now()->subWeek()]);

        $topics = Topic::query()->where('forum_id', $forum->id)->latestActivity()->get();

        expect($topics->first()->id)->toBe($pinned->id);
        expect($topics[1]->id)->toBe($recent->id);
        expect($topics->last()->id)->toBe($old->id);
    });
});

describe('Topic computed attributes', function (): void {
    test('hasReportedContent returns false when posts not loaded', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->has_reported_content)->toBeFalse();
    });

    test('hasUnpublishedContent returns false when posts not loaded', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->has_unpublished_content)->toBeFalse();
    });

    test('hasUnapprovedContent returns false when posts not loaded', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->has_unapproved_content)->toBeFalse();
    });

    test('hasUnpublishedContent returns true when topic has unpublished posts', function (): void {
        $topic = Topic::factory()->create();
        Post::factory()->forum()->create(['topic_id' => $topic->id, 'is_published' => false]);

        $topic->load('posts');

        expect($topic->has_unpublished_content)->toBeTrue();
    });

    test('hasUnapprovedContent returns true when topic has unapproved posts', function (): void {
        $topic = Topic::factory()->create();
        $post = Post::factory()->forum()->create(['topic_id' => $topic->id]);
        $post->update(['is_approved' => false]);

        $topic->load('posts');

        expect($topic->has_unapproved_content)->toBeTrue();
    });

    test('isHot returns false when posts not loaded', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->is_hot)->toBeFalse();
    });

    test('isHot returns false for topics older than a week', function (): void {
        $topic = Topic::factory()->create(['created_at' => now()->subWeeks(2)]);
        $topic->load('posts');

        expect($topic->is_hot)->toBeFalse();
    });
});

describe('Topic deleting cascade', function (): void {
    test('deleting topic deletes its posts', function (): void {
        $topic = Topic::factory()->create();
        Post::factory()->forum()->count(3)->create(['topic_id' => $topic->id]);

        expect(Post::query()->where('topic_id', $topic->id)->count())->toBe(3);

        $topic->delete();

        expect(Post::query()->where('topic_id', $topic->id)->count())->toBe(0);
    });
});

describe('Topic touches forum', function (): void {
    test('updating topic touches forum updated_at', function (): void {
        $forum = Forum::factory()->create(['updated_at' => now()->subDay()]);
        $originalUpdatedAt = $forum->updated_at;

        $topic = Topic::factory()->create(['forum_id' => $forum->id]);
        $topic->update(['title' => 'Updated Title']);

        $forum->refresh();
        expect($forum->updated_at)->toBeGreaterThan($originalUpdatedAt);
    });
});

describe('Topic author_name attribute', function (): void {
    test('returns author name when author exists', function (): void {
        $user = User::factory()->create(['name' => 'John Doe']);
        $topic = Topic::factory()->create(['created_by' => $user->id]);

        expect($topic->author_name)->toBe('John Doe');
    });
});
