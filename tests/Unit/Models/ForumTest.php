<?php

declare(strict_types=1);

use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

describe('Forum category relationship', function (): void {
    test('returns null when forum has no category', function (): void {
        $forum = Forum::factory()->create(['category_id' => null]);

        expect($forum->category)->toBeNull();
    });

    test('returns category when forum belongs to one', function (): void {
        $category = ForumCategory::factory()->create();
        $forum = Forum::factory()->create(['category_id' => $category->id]);

        expect($forum->category->id)->toBe($category->id);
    });

    test('category relationship is BelongsTo', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->category())->toBeInstanceOf(BelongsTo::class);
    });
});

describe('Forum parent relationship', function (): void {
    test('returns null when forum has no parent', function (): void {
        $forum = Forum::factory()->create(['parent_id' => null]);

        expect($forum->parent)->toBeNull();
    });

    test('returns parent forum when set', function (): void {
        $parent = Forum::factory()->create();
        $child = Forum::factory()->create(['parent_id' => $parent->id]);

        expect($child->parent->id)->toBe($parent->id);
    });

    test('parent relationship is BelongsTo', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->parent())->toBeInstanceOf(BelongsTo::class);
    });
});

describe('Forum children relationship', function (): void {
    test('returns empty collection when forum has no children', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->children)->toBeEmpty();
    });

    test('returns child forums', function (): void {
        $parent = Forum::factory()->create();
        $child1 = Forum::factory()->create(['parent_id' => $parent->id]);
        $child2 = Forum::factory()->create(['parent_id' => $parent->id]);

        $children = $parent->children;

        expect($children)->toHaveCount(2);
        expect($children->pluck('id')->all())->toContain($child1->id, $child2->id);
    });

    test('does not return forums belonging to other parents', function (): void {
        $parent1 = Forum::factory()->create();
        $parent2 = Forum::factory()->create();

        Forum::factory()->create(['parent_id' => $parent1->id]);
        Forum::factory()->create(['parent_id' => $parent2->id]);

        expect($parent1->children)->toHaveCount(1);
    });

    test('children relationship is HasMany', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->children())->toBeInstanceOf(HasMany::class);
    });
});

describe('Forum topics relationship', function (): void {
    test('returns empty collection when forum has no topics', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->topics)->toBeEmpty();
    });

    test('returns topics belonging to forum', function (): void {
        $forum = Forum::factory()->create();
        $topic1 = Topic::factory()->create(['forum_id' => $forum->id]);
        $topic2 = Topic::factory()->create(['forum_id' => $forum->id]);

        $topics = $forum->topics;

        expect($topics)->toHaveCount(2);
        expect($topics->pluck('id')->all())->toContain($topic1->id, $topic2->id);
    });

    test('does not return topics from other forums', function (): void {
        $forum1 = Forum::factory()->create();
        $forum2 = Forum::factory()->create();

        Topic::factory()->create(['forum_id' => $forum1->id]);
        Topic::factory()->create(['forum_id' => $forum2->id]);

        expect($forum1->topics)->toHaveCount(1);
    });

    test('topics relationship is HasMany', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->topics())->toBeInstanceOf(HasMany::class);
    });
});

describe('Forum latestTopics relationship', function (): void {
    test('returns topics ordered by latest', function (): void {
        $forum = Forum::factory()->create();
        $older = Topic::factory()->create(['forum_id' => $forum->id, 'created_at' => now()->subDay()]);
        $newer = Topic::factory()->create(['forum_id' => $forum->id, 'created_at' => now()]);

        $latestTopics = $forum->latestTopics;

        expect($latestTopics->first()->id)->toBe($newer->id);
        expect($latestTopics->last()->id)->toBe($older->id);
    });

    test('latestTopics relationship is HasMany', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->latestTopics())->toBeInstanceOf(HasMany::class);
    });
});

describe('Forum latestTopic relationship', function (): void {
    test('returns the most recent topic', function (): void {
        $forum = Forum::factory()->create();
        Topic::factory()->create(['forum_id' => $forum->id, 'created_at' => now()->subDay()]);
        $latest = Topic::factory()->create(['forum_id' => $forum->id, 'created_at' => now()]);

        expect($forum->latestTopic->id)->toBe($latest->id);
    });

    test('returns null when forum has no topics', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->latestTopic)->toBeNull();
    });

    test('latestTopic relationship is HasOne', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->latestTopic())->toBeInstanceOf(HasOne::class);
    });
});

describe('Forum posts relationship', function (): void {
    test('returns empty collection when forum has no posts', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->posts)->toBeEmpty();
    });

    test('returns posts through topics', function (): void {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id]);
        $post = Post::factory()->forum()->create(['topic_id' => $topic->id]);

        $posts = $forum->posts;

        expect($posts)->toHaveCount(1);
        expect($posts->first()->id)->toBe($post->id);
    });

    test('returns posts from multiple topics', function (): void {
        $forum = Forum::factory()->create();
        $topic1 = Topic::factory()->create(['forum_id' => $forum->id]);
        $topic2 = Topic::factory()->create(['forum_id' => $forum->id]);

        Post::factory()->forum()->create(['topic_id' => $topic1->id]);
        Post::factory()->forum()->create(['topic_id' => $topic2->id]);

        expect($forum->posts)->toHaveCount(2);
    });

    test('posts relationship is HasManyThrough', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->posts())->toBeInstanceOf(HasManyThrough::class);
    });
});

describe('Forum groups relationship', function (): void {
    test('returns empty collection when forum has no groups', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->groups)->toBeEmpty();
    });

    test('returns groups with pivot permissions', function (): void {
        $forum = Forum::factory()->create();
        $group = Group::factory()->create();

        $forum->groups()->attach($group->id, [
            'create' => true,
            'read' => true,
            'update' => false,
            'delete' => false,
            'moderate' => false,
            'reply' => true,
            'report' => true,
            'pin' => false,
            'lock' => false,
            'move' => false,
        ]);

        $groups = $forum->groups;

        expect($groups)->toHaveCount(1);
        expect($groups->first()->id)->toBe($group->id);
        expect((bool) $groups->first()->pivot->read)->toBeTrue();
        expect((bool) $groups->first()->pivot->create)->toBeTrue();
        expect((bool) $groups->first()->pivot->delete)->toBeFalse();
    });
});

describe('Forum followers relationship (Followable)', function (): void {
    test('returns empty collection when forum has no followers', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->followers)->toBeEmpty();
    });

    test('returns followers for forum', function (): void {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();

        $forum->follow($user);

        expect($forum->followers)->toHaveCount(1);
    });
});

describe('Forum Activateable trait', function (): void {
    test('forum is active by default', function (): void {
        $forum = Forum::factory()->create();

        expect($forum->is_active)->toBeTrue();
    });

    test('active scope returns only active forums', function (): void {
        Forum::factory()->create(['is_active' => true]);
        Forum::factory()->create(['is_active' => false]);

        expect(Forum::query()->active()->count())->toBe(1);
    });

    test('inactive scope returns only inactive forums', function (): void {
        Forum::factory()->create(['is_active' => true]);
        Forum::factory()->create(['is_active' => false]);

        expect(Forum::query()->inactive()->count())->toBe(1);
    });

    test('activate method sets is_active to true', function (): void {
        $forum = Forum::factory()->create(['is_active' => false]);
        $forum->activate();

        expect($forum->refresh()->is_active)->toBeTrue();
    });

    test('deactivate method sets is_active to false', function (): void {
        $forum = Forum::factory()->create(['is_active' => true]);
        $forum->deactivate();

        expect($forum->refresh()->is_active)->toBeFalse();
    });
});

describe('Forum Orderable trait', function (): void {
    test('ordered scope returns forums by order', function (): void {
        $forum1 = Forum::factory()->create(['order' => 3]);
        $forum2 = Forum::factory()->create(['order' => 1]);
        $forum3 = Forum::factory()->create(['order' => 2]);

        $ordered = Forum::query()->ordered()->get();

        expect($ordered->first()->id)->toBe($forum2->id);
        expect($ordered->last()->id)->toBe($forum1->id);
    });
});

describe('Forum slug generation', function (): void {
    test('generates slug from name', function (): void {
        $forum = Forum::factory()->create(['name' => 'General Discussion']);

        expect($forum->slug)->toBe('general-discussion');
    });
});

describe('Forum deleting cascade', function (): void {
    test('deleting forum deletes its topics', function (): void {
        $forum = Forum::factory()->create();
        Topic::factory()->count(3)->create(['forum_id' => $forum->id]);

        expect(Topic::query()->where('forum_id', $forum->id)->count())->toBe(3);

        $forum->delete();

        expect(Topic::query()->where('forum_id', $forum->id)->count())->toBe(0);
    });

    test('deleting forum deletes topics and their posts', function (): void {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id]);
        Post::factory()->forum()->count(2)->create(['topic_id' => $topic->id]);

        expect(Post::query()->where('topic_id', $topic->id)->count())->toBe(2);

        $forum->delete();

        expect(Post::query()->where('topic_id', $topic->id)->count())->toBe(0);
    });
});

describe('Forum recursiveChildren scope', function (): void {
    test('loads nested children up to depth', function (): void {
        $parent = Forum::factory()->create();
        $child = Forum::factory()->create(['parent_id' => $parent->id]);
        $grandchild = Forum::factory()->create(['parent_id' => $child->id]);

        $forum = Forum::query()->where('id', $parent->id)->recursiveChildren()->first();

        expect($forum->children)->toHaveCount(1);
        expect($forum->children->first()->children)->toHaveCount(1);
        expect($forum->children->first()->children->first()->id)->toBe($grandchild->id);
    });
});
