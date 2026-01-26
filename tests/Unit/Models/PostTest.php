<?php

declare(strict_types=1);

use App\Enums\PostType;
use App\Models\Comment;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

describe('Post topic relationship', function (): void {
    test('returns the topic the post belongs to', function (): void {
        $topic = Topic::factory()->create();
        $post = Post::factory()->forum()->create(['topic_id' => $topic->id]);

        expect($post->topic->id)->toBe($topic->id);
    });

    test('returns null when post has no topic', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);

        expect($post->topic)->toBeNull();
    });

    test('topic relationship is BelongsTo', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);

        expect($post->topic())->toBeInstanceOf(BelongsTo::class);
    });
});

describe('Post author relationship (HasAuthor)', function (): void {
    test('returns the author who created the post', function (): void {
        $user = User::factory()->create();
        $post = Post::factory()->blog()->create(['topic_id' => null, 'created_by' => $user->id]);

        expect($post->author->id)->toBe($user->id);
    });

    test('returns default guest when created_by is null', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null, 'created_by' => null]);

        expect($post->author->name)->toBe('Guest');
        expect($post->author->id)->toBe(0);
    });

    test('isAuthoredBy returns true for the post author', function (): void {
        $user = User::factory()->create();
        $post = Post::factory()->blog()->create(['topic_id' => null, 'created_by' => $user->id]);

        expect($post->isAuthoredBy($user))->toBeTrue();
    });

    test('isAuthoredBy returns false for a different user', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->blog()->create(['topic_id' => null, 'created_by' => $user->id]);

        expect($post->isAuthoredBy($otherUser))->toBeFalse();
    });

    test('author_name returns author name', function (): void {
        $user = User::factory()->create(['name' => 'Jane Smith']);
        $post = Post::factory()->blog()->create(['topic_id' => null, 'created_by' => $user->id]);

        expect($post->author_name)->toBe('Jane Smith');
    });
});

describe('Post type scopes', function (): void {
    test('blog scope returns only blog posts', function (): void {
        $topic = Topic::factory()->create();

        Post::factory()->blog()->create(['topic_id' => null]);
        Post::factory()->forum()->create(['topic_id' => $topic->id]);

        expect(Post::query()->blog()->count())->toBe(1);
        expect(Post::query()->blog()->first()->type)->toBe(PostType::Blog);
    });

    test('forum scope returns only forum posts', function (): void {
        $topic = Topic::factory()->create();

        Post::factory()->blog()->create(['topic_id' => null]);
        Post::factory()->forum()->create(['topic_id' => $topic->id]);

        expect(Post::query()->forum()->count())->toBe(1);
        expect(Post::query()->forum()->first()->type)->toBe(PostType::Forum);
    });
});

describe('Post Publishable trait', function (): void {
    test('post is published by default', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);

        expect($post->is_published)->toBeTrue();
        expect($post->published_at)->not->toBeNull();
    });

    test('draft state sets is_published to false', function (): void {
        $post = Post::factory()->blog()->draft()->create(['topic_id' => null]);

        expect($post->is_published)->toBeFalse();
        expect($post->published_at)->toBeNull();
    });

    test('publish method sets post as published', function (): void {
        $post = Post::factory()->blog()->draft()->create(['topic_id' => null]);
        $post->publish();

        $post->refresh();

        expect($post->is_published)->toBeTrue();
        expect($post->published_at)->not->toBeNull();
    });

    test('unpublish method sets post as unpublished', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);
        $post->unpublish();

        $post->refresh();

        expect($post->is_published)->toBeFalse();
    });

    test('published scope returns only published posts with past published_at', function (): void {
        Post::factory()->blog()->create(['topic_id' => null, 'is_published' => true, 'published_at' => now()->subMinute()]);
        Post::factory()->blog()->draft()->create(['topic_id' => null]);
        Post::factory()->blog()->create(['topic_id' => null, 'is_published' => true, 'published_at' => now()->addDay()]);

        expect(Post::query()->published()->count())->toBe(1);
    });

    test('unpublished scope returns unpublished or future posts', function (): void {
        Post::factory()->blog()->create(['topic_id' => null, 'is_published' => true, 'published_at' => now()->subMinute()]);
        Post::factory()->blog()->draft()->create(['topic_id' => null]);

        expect(Post::query()->unpublished()->count())->toBe(1);
    });

    test('recent scope orders by published_at descending', function (): void {
        $older = Post::factory()->blog()->create(['topic_id' => null, 'published_at' => now()->subDay()]);
        $newer = Post::factory()->blog()->create(['topic_id' => null, 'published_at' => now()]);

        $posts = Post::query()->recent()->get();

        expect($posts->first()->id)->toBe($newer->id);
        expect($posts->last()->id)->toBe($older->id);
    });
});

describe('Post Approvable trait', function (): void {
    test('post is approved by default', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);

        expect($post->is_approved)->toBeTrue();
    });

    test('approve method sets is_approved to true', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);
        $post->update(['is_approved' => false]);
        $post->approve();

        expect($post->refresh()->is_approved)->toBeTrue();
    });

    test('unapprove method sets is_approved to false', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);
        $post->unapprove();

        expect($post->refresh()->is_approved)->toBeFalse();
    });

    test('approved scope returns only approved posts', function (): void {
        Post::factory()->blog()->create(['topic_id' => null]);
        $unapproved = Post::factory()->blog()->create(['topic_id' => null]);
        $unapproved->update(['is_approved' => false]);

        expect(Post::query()->approved()->count())->toBe(1);
    });

    test('unapproved scope returns only unapproved posts', function (): void {
        Post::factory()->blog()->create(['topic_id' => null]);
        $unapproved = Post::factory()->blog()->create(['topic_id' => null]);
        $unapproved->update(['is_approved' => false]);

        expect(Post::query()->unapproved()->count())->toBe(1);
    });
});

describe('Post Featureable trait', function (): void {
    test('featured scope returns only featured posts', function (): void {
        Post::factory()->blog()->featured()->create(['topic_id' => null]);
        Post::factory()->blog()->create(['topic_id' => null, 'is_featured' => false]);

        expect(Post::query()->featured()->count())->toBe(1);
    });

    test('notFeatured scope returns only non-featured posts', function (): void {
        Post::factory()->blog()->featured()->create(['topic_id' => null]);
        Post::factory()->blog()->create(['topic_id' => null, 'is_featured' => false]);

        expect(Post::query()->notFeatured()->count())->toBe(1);
    });
});

describe('Post Pinnable trait', function (): void {
    test('pin method sets is_pinned to true', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null, 'is_pinned' => false]);
        $post->pin();

        expect($post->refresh()->is_pinned)->toBeTrue();
    });

    test('unpin method sets is_pinned to false', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null, 'is_pinned' => true]);
        $post->unpin();

        expect($post->refresh()->is_pinned)->toBeFalse();
    });

    test('pinned scope returns only pinned posts', function (): void {
        Post::factory()->blog()->create(['topic_id' => null, 'is_pinned' => true]);
        Post::factory()->blog()->create(['topic_id' => null, 'is_pinned' => false]);

        expect(Post::query()->pinned()->count())->toBe(1);
    });
});

describe('Post Commentable trait', function (): void {
    test('returns empty collection when post has no comments', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);

        expect($post->comments)->toBeEmpty();
    });

    test('returns comments belonging to post', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);
        $user = User::factory()->create();

        Comment::create([
            'content' => 'Test comment',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
            'created_by' => $user->id,
        ]);

        expect($post->refresh()->comments)->toHaveCount(1);
    });

    test('approvedComments returns only approved comments', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);
        $user = User::factory()->create();

        $approved = Comment::create([
            'content' => 'Approved comment',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
            'created_by' => $user->id,
            'is_approved' => true,
        ]);
        $unapproved = Comment::create([
            'content' => 'Unapproved comment',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
            'created_by' => $user->id,
        ]);
        $unapproved->update(['is_approved' => false]);

        expect($post->refresh()->approvedComments)->toHaveCount(1);
        expect($post->approvedComments->first()->id)->toBe($approved->id);
    });

    test('topLevelComments returns only comments without parent', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);
        $user = User::factory()->create();

        $topLevel = Comment::create([
            'content' => 'Top level comment',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
            'created_by' => $user->id,
        ]);
        Comment::create([
            'content' => 'Reply comment',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
            'parent_id' => $topLevel->id,
            'created_by' => $user->id,
        ]);

        expect($post->refresh()->topLevelComments)->toHaveCount(1);
        expect($post->topLevelComments->first()->id)->toBe($topLevel->id);
    });
});

describe('Post Likeable trait', function (): void {
    test('returns empty collection when post has no likes', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);

        expect($post->likes)->toBeEmpty();
    });

    test('toggleLike creates a like', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);
        $user = User::factory()->create();

        Auth::login($user);
        $post->toggleLike('👍', $user->id);

        expect($post->refresh()->likes)->toHaveCount(1);
    });

    test('toggleLike removes existing like with same emoji', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);
        $user = User::factory()->create();

        Auth::login($user);
        $post->toggleLike('👍', $user->id);
        $post->toggleLike('👍', $user->id);

        expect($post->refresh()->likes)->toBeEmpty();
    });

    test('isLikedBy returns true when user has liked', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);
        $user = User::factory()->create();

        Auth::login($user);
        $post->toggleLike('👍', $user->id);

        expect($post->isLikedBy($user->id))->toBeTrue();
    });
});

describe('Post Reportable trait', function (): void {
    test('returns empty collection when post has no reports', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);

        expect($post->reports)->toBeEmpty();
    });

    test('hasReports returns false when no reports exist', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);

        expect($post->hasReports())->toBeFalse();
    });
});

describe('Post slug generation', function (): void {
    test('blog post has a slug', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null, 'title' => 'My Blog Post Title']);

        expect($post->slug)->not->toBeEmpty();
        expect($post->slug)->toBeString();
    });

    test('forum post has a slug', function (): void {
        $topic = Topic::factory()->create();
        $post = Post::factory()->forum()->create(['topic_id' => $topic->id, 'content' => 'This is a forum reply content']);

        expect($post->slug)->not->toBeEmpty();
    });

    test('generateSlug returns slug from title for blog type', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null, 'title' => 'My Blog Post']);
        $post->type = PostType::Blog;
        $post->title = 'Custom Title Here';

        expect($post->generateSlug())->toBe('custom-title-here');
    });

    test('generateSlug returns slug from content for forum type', function (): void {
        $topic = Topic::factory()->create();
        $post = Post::factory()->forum()->create(['topic_id' => $topic->id, 'content' => '<p>Hello World Forum Post</p>']);
        $post->type = PostType::Forum;
        $post->content = '<p>Hello World Forum Post</p>';

        $slug = $post->generateSlug();
        expect($slug)->not->toBeEmpty();
        expect($slug)->toBeString();
    });
});

describe('Post type casting', function (): void {
    test('type is cast to PostType enum', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);

        expect($post->type)->toBeInstanceOf(PostType::class);
    });

    test('blog type is PostType::Blog', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);

        expect($post->type)->toBe(PostType::Blog);
    });

    test('forum type is PostType::Forum', function (): void {
        $topic = Topic::factory()->create();
        $post = Post::factory()->forum()->create(['topic_id' => $topic->id]);

        expect($post->type)->toBe(PostType::Forum);
    });
});

describe('Post reading time attribute', function (): void {
    test('returns at least 1 minute for short content', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null, 'content' => 'Short']);

        expect($post->reading_time)->toBe(1);
    });

    test('returns more than 1 minute for long content', function (): void {
        $content = str_repeat('word ', 500);
        $post = Post::factory()->blog()->create(['topic_id' => null, 'content' => $content]);

        expect($post->reading_time)->toBeGreaterThan(1);
    });
});

describe('Post url attribute', function (): void {
    test('returns blog route for blog posts', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);

        expect($post->url)->toContain('/blog/');
    });

    test('returns forum route for forum posts with topic', function (): void {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id]);
        $post = Post::factory()->forum()->create(['topic_id' => $topic->id]);

        expect($post->url)->toContain('/forums/');
        expect($post->url)->toContain('#'.$post->id);
    });

    test('returns null for forum post without topic', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null]);
        $post->type = PostType::Forum;

        expect($post->getUrl())->toBeNull();
    });
});

describe('Post getLabel', function (): void {
    test('returns the post title', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null, 'title' => 'Test Title']);

        expect($post->getLabel())->toBe('Test Title');
    });
});

describe('Post latestActivity scope', function (): void {
    test('orders by pinned first then created_at ascending', function (): void {
        $pinned = Post::factory()->blog()->create(['topic_id' => null, 'is_pinned' => true, 'created_at' => now()->subDay()]);
        $older = Post::factory()->blog()->create(['topic_id' => null, 'is_pinned' => false, 'created_at' => now()->subWeek()]);
        $newer = Post::factory()->blog()->create(['topic_id' => null, 'is_pinned' => false, 'created_at' => now()]);

        $posts = Post::query()->latestActivity()->get();

        expect($posts->first()->id)->toBe($pinned->id);
        expect($posts[1]->id)->toBe($older->id);
        expect($posts->last()->id)->toBe($newer->id);
    });
});

describe('Post metadata', function (): void {
    test('metadata is cast to array', function (): void {
        $post = Post::factory()->blog()->create([
            'topic_id' => null,
            'metadata' => ['seo_title' => 'Test SEO Title'],
        ]);

        expect($post->metadata)->toBeArray();
        expect($post->metadata['seo_title'])->toBe('Test SEO Title');
    });

    test('metadata can be null', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null, 'metadata' => null]);

        expect($post->metadata)->toBeNull();
    });
});

describe('Post needingModeration scope', function (): void {
    test('returns unpublished posts', function (): void {
        $unpublished = Post::factory()->blog()->draft()->create(['topic_id' => null]);
        Post::factory()->blog()->create(['topic_id' => null, 'published_at' => now()->subMinute()]);

        $posts = Post::query()->needingModeration()->get();

        expect($posts)->toHaveCount(1);
        expect($posts->first()->id)->toBe($unpublished->id);
    });

    test('returns unapproved posts', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null, 'published_at' => now()->subMinute()]);
        $post->update(['is_approved' => false]);

        Post::factory()->blog()->create(['topic_id' => null, 'published_at' => now()->subMinute()]);

        $posts = Post::query()->needingModeration()->get();

        expect($posts)->toHaveCount(1);
        expect($posts->first()->id)->toBe($post->id);
    });
});

describe('Post touches topic', function (): void {
    test('updating post touches topic updated_at', function (): void {
        $topic = Topic::factory()->create(['updated_at' => now()->subDay()]);
        $originalUpdatedAt = $topic->updated_at;

        $post = Post::factory()->forum()->create(['topic_id' => $topic->id]);
        $post->update(['content' => 'Updated content']);

        $topic->refresh();
        expect($topic->updated_at)->toBeGreaterThan($originalUpdatedAt);
    });
});

describe('Post featured image', function (): void {
    test('hasFeaturedImage returns true when featured image exists', function (): void {
        $post = Post::factory()->blog()->withFeaturedImage()->create(['topic_id' => null]);

        expect($post->hasFeaturedImage())->toBeTrue();
    });

    test('hasFeaturedImage returns false when no featured image', function (): void {
        $post = Post::factory()->blog()->withoutFeaturedImage()->create(['topic_id' => null]);

        expect($post->hasFeaturedImage())->toBeFalse();
    });
});

describe('Post comments_enabled', function (): void {
    test('commentsEnabled returns true when enabled', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null, 'comments_enabled' => true]);

        expect($post->commentsEnabled())->toBeTrue();
    });

    test('commentsEnabled returns false when disabled', function (): void {
        $post = Post::factory()->blog()->create(['topic_id' => null, 'comments_enabled' => false]);

        expect($post->commentsEnabled())->toBeFalse();
    });
});
