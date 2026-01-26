<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Blog Index Tests
|--------------------------------------------------------------------------
*/

it('can view blog index page as guest', function (): void {
    $response = $this->get(route('blog.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('blog/index'));
});

it('can view blog index page as authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('blog.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('blog/index'));
});

it('displays published blog posts on index', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
        'published_at' => now()->subMinute(),
    ]);

    $response = $this->get(route('blog.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/index')
        ->has('posts.data', 1)
        ->where('posts.data.0.id', $post->id)
    );
});

it('does not display unpublished blog posts on index', function (): void {
    $author = User::factory()->create();
    Post::factory()->blog()->draft()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->get(route('blog.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/index')
        ->has('posts.data', 0)
    );
});

it('does not display unapproved blog posts on index for guest', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'topic_id' => null,
    ]);
    $post->update(['is_approved' => false]);

    $response = $this->get(route('blog.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/index')
        ->has('posts.data', 0)
    );
});

it('author can see own unapproved blog post in index', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'topic_id' => null,
        'published_at' => now()->subMinute(),
    ]);
    $post->update(['is_approved' => false]);

    $response = $this->actingAs($author)->get(route('blog.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/index')
        ->has('posts.data', 1)
        ->where('posts.data.0.id', $post->id)
    );
});

it('does not display forum posts on blog index', function (): void {
    $author = User::factory()->create();
    $topic = Topic::factory()->create(['created_by' => $author->id]);
    Post::factory()->forum()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => $topic->id,
    ]);

    $response = $this->get(route('blog.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/index')
        ->has('posts.data', 0)
    );
});

it('does not display posts with future published_at on index', function (): void {
    $author = User::factory()->create();
    Post::factory()->blog()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'is_published' => true,
        'published_at' => now()->addDay(),
        'topic_id' => null,
    ]);

    $response = $this->get(route('blog.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/index')
        ->has('posts.data', 0)
    );
});

/*
|--------------------------------------------------------------------------
| Blog Show Tests
|--------------------------------------------------------------------------
*/

it('can view blog post page as guest', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->get(route('blog.show', ['post' => $post->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/show')
        ->has('post')
        ->where('post.id', $post->id)
        ->where('post.title', $post->title)
    );
});

it('can view blog post page as authenticated user', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->actingAs($user)->get(route('blog.show', ['post' => $post->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/show')
        ->has('post')
        ->where('post.id', $post->id)
    );
});

it('returns 404 for non-existent blog post', function (): void {
    $response = $this->get(route('blog.show', ['post' => 'non-existent-slug']));

    $response->assertNotFound();
});

it('returns 403 for unpublished blog post viewed by guest', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->draft()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->get(route('blog.show', ['post' => $post->slug]));

    $response->assertForbidden();
});

it('returns 403 for unapproved blog post viewed by guest', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'topic_id' => null,
    ]);
    $post->update(['is_approved' => false]);

    $response = $this->get(route('blog.show', ['post' => $post->slug]));

    $response->assertForbidden();
});

it('author can view own unpublished blog post', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->draft()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->actingAs($author)->get(route('blog.show', ['post' => $post->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/show')
        ->has('post')
        ->where('post.id', $post->id)
    );
});

it('author can view own unapproved blog post', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'topic_id' => null,
    ]);
    $post->update(['is_approved' => false]);

    $response = $this->actingAs($author)->get(route('blog.show', ['post' => $post->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/show')
        ->has('post')
        ->where('post.id', $post->id)
    );
});

it('returns 403 for post scheduled for future', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'is_published' => true,
        'published_at' => now()->addDay(),
        'topic_id' => null,
    ]);

    $response = $this->get(route('blog.show', ['post' => $post->slug]));

    $response->assertForbidden();
});

it('displays approved comments on blog post', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $commentAuthor = User::factory()->create();
    Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $commentAuthor->id,
    ]);

    $response = $this->get(route('blog.show', ['post' => $post->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/show')
        ->has('comments.data', 1)
    );
});

it('does not display unapproved comments for guest', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $commentAuthor = User::factory()->create();
    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $commentAuthor->id,
    ]);
    $comment->update(['is_approved' => false]);

    $response = $this->get(route('blog.show', ['post' => $post->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/show')
        ->has('comments.data', 0)
    );
});

it('comment author can see own unapproved comment', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $commentAuthor = User::factory()->create();
    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $commentAuthor->id,
    ]);
    $comment->update(['is_approved' => false]);

    $response = $this->actingAs($commentAuthor)->get(route('blog.show', ['post' => $post->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/show')
        ->has('comments.data', 1)
    );
});

it('displays comment replies on blog post', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $commentAuthor = User::factory()->create();
    $parentComment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $commentAuthor->id,
    ]);

    $replyAuthor = User::factory()->create();
    Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $replyAuthor->id,
        'parent_id' => $parentComment->id,
    ]);

    $response = $this->get(route('blog.show', ['post' => $post->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('blog/show')
        ->has('comments.data', 2)
    );
});
