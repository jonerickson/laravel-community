<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

/*
|--------------------------------------------------------------------------
| Comment Store Tests
|--------------------------------------------------------------------------
*/

it('can create comment on blog post', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->actingAs($user)->post(route('blog.comments.store', ['post' => $post->slug]), [
        'content' => 'This is a test comment.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Your comment was successfully added.');
    $this->assertDatabaseHas('comments', [
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'content' => 'This is a test comment.',
        'created_by' => $user->id,
    ]);
});

it('requires authentication to create comment', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->post(route('blog.comments.store', ['post' => $post->slug]), [
        'content' => 'This is a test comment.',
    ]);

    $response->assertRedirect(route('login'));
});

it('requires verified email to create comment', function (): void {
    $user = User::factory()->unverified()->create();
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->actingAs($user)->post(route('blog.comments.store', ['post' => $post->slug]), [
        'content' => 'This is a test comment.',
    ]);

    $response->assertRedirect(route('verification.notice'));
});

it('validates content is required for comment', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->actingAs($user)->post(route('blog.comments.store', ['post' => $post->slug]), [
        'content' => '',
    ]);

    $response->assertSessionHasErrors('content');
});

it('validates content minimum length for comment', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->actingAs($user)->post(route('blog.comments.store', ['post' => $post->slug]), [
        'content' => 'a',
    ]);

    $response->assertSessionHasErrors('content');
});

it('validates content maximum length for comment', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->actingAs($user)->post(route('blog.comments.store', ['post' => $post->slug]), [
        'content' => str_repeat('a', 1001),
    ]);

    $response->assertSessionHasErrors('content');
});

it('can create reply to existing comment', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $parentComment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $author->id,
    ]);

    $response = $this->actingAs($user)->post(route('blog.comments.store', ['post' => $post->slug]), [
        'content' => 'This is a reply.',
        'parent_id' => $parentComment->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Your comment was successfully added.');
    $this->assertDatabaseHas('comments', [
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'content' => 'This is a reply.',
        'parent_id' => $parentComment->id,
        'created_by' => $user->id,
    ]);
});

it('returns 403 when creating comment on unpublished post', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->blog()->draft()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->actingAs($user)->post(route('blog.comments.store', ['post' => $post->slug]), [
        'content' => 'This is a test comment.',
    ]);

    $response->assertForbidden();
});

it('returns 403 when creating comment on unapproved post', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'topic_id' => null,
    ]);
    $post->update(['is_approved' => false]);

    $response = $this->actingAs($user)->post(route('blog.comments.store', ['post' => $post->slug]), [
        'content' => 'This is a test comment.',
    ]);

    $response->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Comment Update Tests
|--------------------------------------------------------------------------
*/

it('author can update own comment', function (): void {
    $author = User::factory()->create();
    $postAuthor = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $postAuthor->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $author->id,
        'content' => 'Original content.',
    ]);

    $response = $this->actingAs($author)->patch(route('blog.comments.update', ['post' => $post->slug, 'comment' => $comment->id]), [
        'content' => 'Updated content.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'The comment has been successfully updated.');
    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'content' => 'Updated content.',
    ]);
});

it('non-author cannot update comment', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $postAuthor = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $postAuthor->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $author->id,
        'content' => 'Original content.',
    ]);

    $response = $this->actingAs($user)->patch(route('blog.comments.update', ['post' => $post->slug, 'comment' => $comment->id]), [
        'content' => 'Updated content.',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'content' => 'Original content.',
    ]);
});

it('requires authentication to update comment', function (): void {
    $author = User::factory()->create();
    $postAuthor = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $postAuthor->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $author->id,
    ]);

    $response = $this->patch(route('blog.comments.update', ['post' => $post->slug, 'comment' => $comment->id]), [
        'content' => 'Updated content.',
    ]);

    $response->assertRedirect(route('login'));
});

it('validates content is required for comment update', function (): void {
    $author = User::factory()->create();
    $postAuthor = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $postAuthor->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $author->id,
    ]);

    $response = $this->actingAs($author)->patch(route('blog.comments.update', ['post' => $post->slug, 'comment' => $comment->id]), [
        'content' => '',
    ]);

    $response->assertSessionHasErrors('content');
});

it('validates content minimum length for comment update', function (): void {
    $author = User::factory()->create();
    $postAuthor = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $postAuthor->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $author->id,
    ]);

    $response = $this->actingAs($author)->patch(route('blog.comments.update', ['post' => $post->slug, 'comment' => $comment->id]), [
        'content' => 'a',
    ]);

    $response->assertSessionHasErrors('content');
});

/*
|--------------------------------------------------------------------------
| Comment Destroy Tests
|--------------------------------------------------------------------------
*/

it('author can delete own comment', function (): void {
    $author = User::factory()->create();
    $postAuthor = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $postAuthor->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $author->id,
    ]);

    $response = $this->actingAs($author)->delete(route('blog.comments.destroy', ['post' => $post->slug, 'comment' => $comment->id]));

    $response->assertRedirect();
    $response->assertSessionHas('message', 'The comment was successfully deleted.');
    $this->assertDatabaseMissing('comments', [
        'id' => $comment->id,
    ]);
});

it('non-author cannot delete comment', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $postAuthor = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $postAuthor->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $author->id,
    ]);

    $response = $this->actingAs($user)->delete(route('blog.comments.destroy', ['post' => $post->slug, 'comment' => $comment->id]));

    $response->assertForbidden();
    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
    ]);
});

it('requires authentication to delete comment', function (): void {
    $author = User::factory()->create();
    $postAuthor = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $postAuthor->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $author->id,
    ]);

    $response = $this->delete(route('blog.comments.destroy', ['post' => $post->slug, 'comment' => $comment->id]));

    $response->assertRedirect(route('login'));
});

it('author can delete own unapproved comment', function (): void {
    $author = User::factory()->create();
    $postAuthor = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $postAuthor->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
        'created_by' => $author->id,
    ]);
    $comment->update(['is_approved' => false]);

    $response = $this->actingAs($author)->delete(route('blog.comments.destroy', ['post' => $post->slug, 'comment' => $comment->id]));

    $response->assertRedirect();
    $response->assertSessionHas('message', 'The comment was successfully deleted.');
    $this->assertDatabaseMissing('comments', [
        'id' => $comment->id,
    ]);
});
