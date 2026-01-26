<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use App\Traits\HasAuthor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

describe('HasAuthor author relationship', function (): void {
    test('returns the user who created the model', function (): void {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['created_by' => $user->id]);

        expect($topic->author->id)->toBe($user->id);
        expect($topic->author->name)->toBe($user->name);
    });

    test('author relationship is BelongsTo', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->author())->toBeInstanceOf(BelongsTo::class);
    });

    test('returns default guest user when created_by is null', function (): void {
        $post = Post::factory()->blog()->create([
            'topic_id' => null,
            'created_by' => null,
        ]);

        expect($post->author->id)->toBe(0);
        expect($post->author->name)->toBe('Guest');
        expect($post->author->email)->toBe(config('app.email'));
    });

    test('default guest user has id of 0', function (): void {
        $post = Post::factory()->blog()->create([
            'topic_id' => null,
            'created_by' => null,
        ]);

        expect($post->author->id)->toBe(0);
    });

    test('author relationship never returns null due to withDefault', function (): void {
        $post = Post::factory()->blog()->create([
            'topic_id' => null,
            'created_by' => null,
        ]);

        expect($post->author)->not->toBeNull();
    });
});

describe('HasAuthor creator alias', function (): void {
    test('creator returns same relationship as author', function (): void {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['created_by' => $user->id]);

        expect($topic->creator->id)->toBe($user->id);
        expect($topic->creator->id)->toBe($topic->author->id);
    });

    test('creator relationship is BelongsTo', function (): void {
        $topic = Topic::factory()->create();

        expect($topic->creator())->toBeInstanceOf(BelongsTo::class);
    });
});

describe('HasAuthor isAuthoredBy method', function (): void {
    test('returns true for the author', function (): void {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['created_by' => $user->id]);

        expect($topic->isAuthoredBy($user))->toBeTrue();
    });

    test('returns false for a different user', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $topic = Topic::factory()->create(['created_by' => $user->id]);

        expect($topic->isAuthoredBy($otherUser))->toBeFalse();
    });

    test('returns false when created_by is null', function (): void {
        $user = User::factory()->create();
        $post = Post::factory()->blog()->create([
            'topic_id' => null,
            'created_by' => null,
        ]);

        expect($post->isAuthoredBy($user))->toBeFalse();
    });

    test('works across different models using the trait', function (): void {
        $user = User::factory()->create();

        $topic = Topic::factory()->create(['created_by' => $user->id]);
        $post = Post::factory()->blog()->create([
            'topic_id' => null,
            'created_by' => $user->id,
        ]);

        expect($topic->isAuthoredBy($user))->toBeTrue();
        expect($post->isAuthoredBy($user))->toBeTrue();
    });
});

describe('HasAuthor authorName attribute', function (): void {
    test('returns author name when author exists', function (): void {
        $user = User::factory()->create(['name' => 'John Doe']);
        $topic = Topic::factory()->create(['created_by' => $user->id]);

        expect($topic->author_name)->toBe('John Doe');
    });

    test('returns Guest when no author', function (): void {
        $post = Post::factory()->blog()->create([
            'topic_id' => null,
            'created_by' => null,
        ]);

        expect($post->author_name)->toBe('Guest');
    });
});

describe('HasAuthor auto-fill created_by on creation', function (): void {
    test('auto-fills created_by with authenticated user id', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $topic = Topic::factory()->create(['created_by' => null]);

        expect($topic->created_by)->toBe($user->id);
    });

    test('does not override explicitly set created_by', function (): void {
        $user = User::factory()->create();
        $author = User::factory()->create();
        Auth::login($user);

        $topic = Topic::factory()->create(['created_by' => $author->id]);

        expect($topic->created_by)->toBe($author->id);
        expect($topic->created_by)->not->toBe($user->id);
    });

    test('sets created_by to null when no user is authenticated', function (): void {
        Auth::logout();

        $post = Post::factory()->blog()->create([
            'topic_id' => null,
            'created_by' => null,
        ]);

        expect($post->created_by)->toBeNull();
    });
});

describe('HasAuthor fillable', function (): void {
    test('created_by is fillable on Topic', function (): void {
        $topic = new Topic;

        expect($topic->getFillable())->toContain('created_by');
    });

    test('created_by is fillable on Post', function (): void {
        $post = new Post;

        expect($post->getFillable())->toContain('created_by');
    });

    test('created_by is fillable on Comment', function (): void {
        $comment = new Comment;

        expect($comment->getFillable())->toContain('created_by');
    });
});

describe('HasAuthor uses trait', function (): void {
    test('Topic uses HasAuthor trait', function (): void {
        expect(in_array(HasAuthor::class, class_uses_recursive(Topic::class)))->toBeTrue();
    });

    test('Post uses HasAuthor trait', function (): void {
        expect(in_array(HasAuthor::class, class_uses_recursive(Post::class)))->toBeTrue();
    });

    test('Comment uses HasAuthor trait', function (): void {
        expect(in_array(HasAuthor::class, class_uses_recursive(Comment::class)))->toBeTrue();
    });

});
