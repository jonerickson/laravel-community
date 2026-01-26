<?php

declare(strict_types=1);

use App\Enums\PostType;
use App\Events\TopicCreated;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Event;

/*
|--------------------------------------------------------------------------
| Topic Show Tests
|--------------------------------------------------------------------------
*/

it('can view topic page as guest with read permission', function (): void {
    $guestGroup = Group::factory()->asDefaultGuest()->create(['is_active' => true]);
    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($guestGroup, [
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
    $forum->groups()->attach($guestGroup, [
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

    $author = User::factory()->create();
    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
    ]);

    // Create a published post so the topic is viewable
    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $response = $this->get(route('forums.topics.show', ['forum' => $forum->slug, 'topic' => $topic->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/topics/show')
        ->has('forum')
        ->has('topic')
        ->where('topic.id', $topic->id)
        ->where('topic.title', $topic->title)
    );
});

it('can view topic page as authenticated user with read permission', function (): void {
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

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->get(route('forums.topics.show', ['forum' => $forum->slug, 'topic' => $topic->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/topics/show')
        ->has('forum')
        ->has('topic')
        ->where('topic.id', $topic->id)
    );
});

it('cannot view topic without read permission on forum', function (): void {
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

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->get(route('forums.topics.show', ['forum' => $forum->slug, 'topic' => $topic->slug]));

    $response->assertForbidden();
});

it('cannot view topic when forum is inactive', function (): void {
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

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->get(route('forums.topics.show', ['forum' => $forum->slug, 'topic' => $topic->slug]));

    $response->assertForbidden();
});

it('returns 404 for non-existent topic', function (): void {
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

    $response = $this->actingAs($user)->get(route('forums.topics.show', ['forum' => $forum->slug, 'topic' => 'non-existent-topic']));

    $response->assertNotFound();
});

it('displays topic with posts', function (): void {
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

    Post::factory()->count(3)->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->get(route('forums.topics.show', ['forum' => $forum->slug, 'topic' => $topic->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/topics/show')
        ->has('topic')
        ->where('topic.postsCount', 3)
    );
});

it('loads topic with follower count', function (): void {
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

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->get(route('forums.topics.show', ['forum' => $forum->slug, 'topic' => $topic->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/topics/show')
        ->has('topic.followersCount')
    );
});

/*
|--------------------------------------------------------------------------
| Topic Create Tests
|--------------------------------------------------------------------------
*/

it('can view create topic page with create permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => true,
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
        'create' => true,
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

    $response = $this->actingAs($user)->get(route('forums.topics.create', ['forum' => $forum->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/topics/create')
        ->has('forum')
        ->where('forum.id', $forum->id)
    );
});

it('cannot view create topic page without create permission', function (): void {
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

    $response = $this->actingAs($user)->get(route('forums.topics.create', ['forum' => $forum->slug]));

    $response->assertForbidden();
});

it('requires authentication to view create topic page', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);

    $response = $this->get(route('forums.topics.create', ['forum' => $forum->slug]));

    $response->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Topic Store Tests
|--------------------------------------------------------------------------
*/

it('can create topic with valid data', function (): void {
    Event::fake([TopicCreated::class]);

    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => true,
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
        'create' => true,
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

    $response = $this->actingAs($user)->post(route('forums.topics.store', ['forum' => $forum->slug]), [
        'title' => 'Test Topic Title',
        'content' => 'This is the test topic content.',
    ]);

    $topic = Topic::where('title', 'Test Topic Title')->first();
    expect($topic)->not->toBeNull();
    expect($topic->forum_id)->toBe($forum->id);
    expect($topic->created_by)->toBe($user->id);

    // Check post was created
    $post = $topic->posts()->first();
    expect($post)->not->toBeNull();
    expect($post->title)->toBe('Test Topic Title');
    expect($post->content)->toBe('This is the test topic content.');
    expect($post->type)->toBe(PostType::Forum);

    $response->assertRedirect(route('forums.topics.show', ['forum' => $forum, 'topic' => $topic]));
    $response->assertSessionHas('message', 'Your topic was successfully created.');

    Event::assertDispatched(TopicCreated::class);
});

it('cannot create topic without create permission', function (): void {
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

    $response = $this->actingAs($user)->post(route('forums.topics.store', ['forum' => $forum->slug]), [
        'title' => 'Test Topic Title',
        'content' => 'This is the test topic content.',
    ]);

    $response->assertForbidden();

    expect(Topic::where('title', 'Test Topic Title')->exists())->toBeFalse();
});

it('requires authentication to create topic', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);

    $response = $this->post(route('forums.topics.store', ['forum' => $forum->slug]), [
        'title' => 'Test Topic Title',
        'content' => 'This is the test topic content.',
    ]);

    $response->assertRedirect(route('login'));
});

it('validates title is required when creating topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => true,
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
        'create' => true,
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

    $response = $this->actingAs($user)->post(route('forums.topics.store', ['forum' => $forum->slug]), [
        'content' => 'This is the test topic content.',
    ]);

    $response->assertSessionHasErrors(['title']);
});

it('validates content is required when creating topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => true,
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
        'create' => true,
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

    $response = $this->actingAs($user)->post(route('forums.topics.store', ['forum' => $forum->slug]), [
        'title' => 'Test Topic Title',
    ]);

    $response->assertSessionHasErrors(['content']);
});

it('validates title max length when creating topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => true,
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
        'create' => true,
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

    $response = $this->actingAs($user)->post(route('forums.topics.store', ['forum' => $forum->slug]), [
        'title' => str_repeat('a', 256),
        'content' => 'This is the test topic content.',
    ]);

    $response->assertSessionHasErrors(['title']);
});

it('validates title min length when creating topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => true,
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
        'create' => true,
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

    $response = $this->actingAs($user)->post(route('forums.topics.store', ['forum' => $forum->slug]), [
        'title' => 'a',
        'content' => 'This is the test topic content.',
    ]);

    $response->assertSessionHasErrors(['title']);
});

/*
|--------------------------------------------------------------------------
| Topic Delete Tests
|--------------------------------------------------------------------------
*/

it('author can delete their own topic', function (): void {
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
        'is_locked' => false,
    ]);

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->delete(route('forums.topics.destroy', ['forum' => $forum->slug, 'topic' => $topic->slug]));

    $response->assertRedirect(route('forums.show', ['forum' => $forum]));
    $response->assertSessionHas('message', 'The topic was successfully deleted.');

    expect(Topic::find($topic->id))->toBeNull();
});

it('cannot delete locked topic as author', function (): void {
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
        'is_locked' => true,
    ]);

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->delete(route('forums.topics.destroy', ['forum' => $forum->slug, 'topic' => $topic->slug]));

    $response->assertForbidden();

    expect(Topic::find($topic->id))->not->toBeNull();
});

it('cannot delete topic without permission', function (): void {
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
        'is_locked' => false,
    ]);

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($otherUser)->delete(route('forums.topics.destroy', ['forum' => $forum->slug, 'topic' => $topic->slug]));

    $response->assertForbidden();

    expect(Topic::find($topic->id))->not->toBeNull();
});

it('requires authentication to delete topic', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);

    $response = $this->delete(route('forums.topics.destroy', ['forum' => $forum->slug, 'topic' => $topic->slug]));

    $response->assertRedirect(route('login'));
});

it('admin can delete any topic', function (): void {
    $author = User::factory()->create();
    $admin = User::factory()->asAdmin()->create();

    $forum = Forum::factory()->create(['is_active' => true]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
        'is_locked' => false,
    ]);

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($admin)->delete(route('forums.topics.destroy', ['forum' => $forum->slug, 'topic' => $topic->slug]));

    $response->assertRedirect(route('forums.show', ['forum' => $forum]));

    expect(Topic::find($topic->id))->toBeNull();
});
