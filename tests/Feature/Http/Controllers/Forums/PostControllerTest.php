<?php

declare(strict_types=1);

use App\Enums\PostType;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Post Store Tests
|--------------------------------------------------------------------------
*/

it('can create post (reply) in topic as authenticated user', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => true,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => true,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    // Create initial post so topic is viewable
    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $topicUrl = route('forums.topics.show', ['forum' => $forum->slug, 'topic' => $topic->slug]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $topicUrl)
        ->post(route('forums.posts.store', [
            'forum' => $forum->slug,
            'topic' => $topic->slug,
        ]), [
            'content' => 'This is a test reply to the topic.',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Your reply was successfully added.');

    $post = Post::where('content', 'This is a test reply to the topic.')->first();
    expect($post)->not->toBeNull();
    expect($post->topic_id)->toBe($topic->id);
    expect($post->created_by)->toBe($user->id);
    expect($post->type)->toBe(PostType::Forum);
    expect($post->title)->toBe('Re: '.$topic->title);
});

it('requires authentication to create post', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);

    $response = $this->post(route('forums.posts.store', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
    ]), [
        'content' => 'This is a test reply.',
    ]);

    $response->assertRedirect(route('login'));
});

it('cannot create post without view permission on forum', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create(['is_active' => true]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => false,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => true,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('forums.posts.store', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
    ]), [
        'content' => 'This is a test reply.',
    ]);

    $response->assertForbidden();
});

it('cannot create post when forum is inactive', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create(['is_active' => false]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => true,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('forums.posts.store', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
    ]), [
        'content' => 'This is a test reply.',
    ]);

    $response->assertForbidden();
});

it('validates content is required when creating post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => true,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => true,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('forums.posts.store', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
    ]), []);

    $response->assertSessionHasErrors(['content']);
});

it('validates content min length when creating post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => true,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => true,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('forums.posts.store', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
    ]), [
        'content' => 'a',
    ]);

    $response->assertSessionHasErrors(['content']);
});

it('validates content max length when creating post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => true,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => true,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('forums.posts.store', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
    ]), [
        'content' => str_repeat('a', 10001),
    ]);

    $response->assertSessionHasErrors(['content']);
});

/*
|--------------------------------------------------------------------------
| Post Edit Tests
|--------------------------------------------------------------------------
*/

it('can view edit post page as author', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->get(route('forums.posts.edit', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $post->slug,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/posts/edit')
        ->has('forum')
        ->has('topic')
        ->has('post')
        ->where('post.id', $post->id)
    );
});

it('cannot view edit post page as non-author without permission', function (): void {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $otherUser->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
    ]);

    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($otherUser)->get(route('forums.posts.edit', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $post->slug,
    ]));

    $response->assertForbidden();
});

it('requires authentication to view edit post page', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'is_approved' => true,
    ]);

    $response = $this->get(route('forums.posts.edit', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $post->slug,
    ]));

    $response->assertRedirect(route('login'));
});

it('user with delete permission can view edit post page', function (): void {
    $author = User::factory()->create();
    $moderator = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $moderator->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => true,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => true,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
    ]);

    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($moderator)->get(route('forums.posts.edit', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $post->slug,
    ]));

    $response->assertOk();
});

/*
|--------------------------------------------------------------------------
| Post Update Tests
|--------------------------------------------------------------------------
*/

it('author can update their own post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
        'content' => 'Original content',
    ]);

    $response = $this->actingAs($user)->patch(route('forums.posts.update', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $post->slug,
    ]), [
        'content' => 'Updated content',
    ]);

    $response->assertRedirect(route('forums.topics.show', ['forum' => $forum, 'topic' => $topic]));
    $response->assertSessionHas('message', 'The post was successfully updated.');

    expect($post->refresh()->content)->toBe('Updated content');
});

it('cannot update post as non-author without permission', function (): void {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $otherUser->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
    ]);

    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
        'content' => 'Original content',
    ]);

    $response = $this->actingAs($otherUser)->patch(route('forums.posts.update', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $post->slug,
    ]), [
        'content' => 'Updated content',
    ]);

    $response->assertForbidden();

    expect($post->refresh()->content)->toBe('Original content');
});

it('requires authentication to update post', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'is_approved' => true,
    ]);

    $response = $this->patch(route('forums.posts.update', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $post->slug,
    ]), [
        'content' => 'Updated content',
    ]);

    $response->assertRedirect(route('login'));
});

it('validates content is required when updating post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->patch(route('forums.posts.update', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $post->slug,
    ]), []);

    $response->assertSessionHasErrors(['content']);
});

it('user with delete permission can update other users posts', function (): void {
    $author = User::factory()->create();
    $moderator = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $moderator->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => true,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => true,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
    ]);

    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
        'content' => 'Original content',
    ]);

    $response = $this->actingAs($moderator)->patch(route('forums.posts.update', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $post->slug,
    ]), [
        'content' => 'Moderator updated content',
    ]);

    $response->assertRedirect();

    expect($post->refresh()->content)->toBe('Moderator updated content');
});

/*
|--------------------------------------------------------------------------
| Post Delete Tests
|--------------------------------------------------------------------------
*/

it('author can delete their own post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    // Create two posts - can only delete if not the last one
    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $postToDelete = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->delete(route('forums.posts.destroy', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $postToDelete->slug,
    ]));

    $response->assertRedirect();
    $response->assertSessionHas('message', 'The post was successfully deleted.');

    expect(Post::find($postToDelete->id))->toBeNull();
});

it('cannot delete the last post in a topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $onlyPost = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->delete(route('forums.posts.destroy', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $onlyPost->slug,
    ]));

    $response->assertRedirect();
    $response->assertSessionHas('message', 'You cannot delete the last post in a topic. Delete the topic instead.');
    $response->assertSessionHas('messageVariant', 'error');

    expect(Post::find($onlyPost->id))->not->toBeNull();
});

it('cannot delete post as non-author without permission', function (): void {
    $author = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $otherUser->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
    ]);

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $postToDelete = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($otherUser)->delete(route('forums.posts.destroy', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $postToDelete->slug,
    ]));

    $response->assertForbidden();

    expect(Post::find($postToDelete->id))->not->toBeNull();
});

it('requires authentication to delete post', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'is_approved' => true,
    ]);

    $response = $this->delete(route('forums.posts.destroy', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $post->slug,
    ]));

    $response->assertRedirect(route('login'));
});

it('user with delete permission can delete other users posts', function (): void {
    $author = User::factory()->create();
    $moderator = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $moderator->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => true,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => true,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
    ]);

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $postToDelete = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($moderator)->delete(route('forums.posts.destroy', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $postToDelete->slug,
    ]));

    $response->assertRedirect();

    expect(Post::find($postToDelete->id))->toBeNull();
});

it('admin can delete any post', function (): void {
    $author = User::factory()->create();
    $admin = User::factory()->asAdmin()->create();

    $forum = Forum::factory()->create(['is_active' => true]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
    ]);

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $postToDelete = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($admin)->delete(route('forums.posts.destroy', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $postToDelete->slug,
    ]));

    $response->assertRedirect();

    expect(Post::find($postToDelete->id))->toBeNull();
});

it('admin can update any post', function (): void {
    $author = User::factory()->create();
    $admin = User::factory()->asAdmin()->create();

    $forum = Forum::factory()->create(['is_active' => true]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
    ]);

    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
        'content' => 'Original content',
    ]);

    $response = $this->actingAs($admin)->patch(route('forums.posts.update', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => $post->slug,
    ]), [
        'content' => 'Admin updated content',
    ]);

    $response->assertRedirect();

    expect($post->refresh()->content)->toBe('Admin updated content');
});

it('returns 404 for non-existent post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('forums.posts.edit', [
        'forum' => $forum->slug,
        'topic' => $topic->slug,
        'post' => 'non-existent-post',
    ]));

    $response->assertNotFound();
});
