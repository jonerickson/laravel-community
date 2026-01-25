<?php

declare(strict_types=1);

use App\Enums\WarningConsequenceType;
use App\Http\Requests\Forums\StorePostRequest;
use App\Models\Forum;
use App\Models\Group;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserWarning;
use App\Models\Warning;
use App\Models\WarningConsequence;
use Illuminate\Support\Facades\Auth;

describe('StorePostRequest validation', function (): void {
    test('validation passes with valid content', function (): void {
        $request = new StorePostRequest([
            'content' => 'This is my reply to the topic.',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when content is missing', function (): void {
        $request = new StorePostRequest([]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation fails when content is empty', function (): void {
        $request = new StorePostRequest([
            'content' => '',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation fails when content is too short', function (): void {
        $request = new StorePostRequest([
            'content' => 'A',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation passes when content is at minimum length', function (): void {
        $request = new StorePostRequest([
            'content' => 'AB',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when content is too long', function (): void {
        $request = new StorePostRequest([
            'content' => str_repeat('A', 10001),
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation passes when content is at maximum length', function (): void {
        $request = new StorePostRequest([
            'content' => str_repeat('A', 10000),
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when content is not a string', function (): void {
        $request = new StorePostRequest([
            'content' => 12345,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation fails when content is an array', function (): void {
        $request = new StorePostRequest([
            'content' => ['array', 'not', 'string'],
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });
});

describe('StorePostRequest custom messages', function (): void {
    test('content required message is customized', function (): void {
        $request = new StorePostRequest([
            'content' => '',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('content'))->toBe('Please provide a reply before posting.');
    });
});

describe('StorePostRequest authorization', function (): void {
    test('authorize returns true when user is authenticated', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $request = new StorePostRequest;

        expect($request->authorize())->toBeTrue();

        Auth::logout();
    });

    test('authorize returns false when user is guest', function (): void {
        $request = new StorePostRequest;

        expect($request->authorize())->toBeFalse();
    });
});

describe('StorePostRequest HTTP layer', function (): void {
    test('post can be created with valid data', function (): void {
        $group = Group::factory()->asDefaultMemberGroup()->create();
        $user = User::factory()->create();

        $forum = Forum::factory()->create(['is_active' => true]);
        $forum->groups()->attach($group, [
            'create' => true,
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

        $topic = Topic::factory()->for($forum)->create();
        Post::factory()->forum()->for($topic)->create();

        $response = $this->actingAs($user)
            ->withHeader('referer', route('forums.topics.show', [$forum->slug, $topic->slug]))
            ->post(route('forums.posts.store', [$forum->slug, $topic->slug]), [
                'content' => 'This is my reply to the topic.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'topic_id' => $topic->id,
            'content' => 'This is my reply to the topic.',
            'created_by' => $user->id,
        ]);
    });

    test('post creation requires authentication', function (): void {
        $forum = Forum::factory()->create(['is_active' => true]);
        $topic = Topic::factory()->for($forum)->create();
        Post::factory()->forum()->for($topic)->create();

        $response = $this->post(route('forums.posts.store', [$forum->slug, $topic->slug]), [
            'content' => 'This is my reply to the topic.',
        ]);

        $response->assertRedirect(route('login'));
    });

    test('post creation fails with validation errors', function (): void {
        $group = Group::factory()->asDefaultMemberGroup()->create();
        $user = User::factory()->create();

        $forum = Forum::factory()->create(['is_active' => true]);
        $forum->groups()->attach($group, [
            'create' => true,
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

        $topic = Topic::factory()->for($forum)->create();
        Post::factory()->forum()->for($topic)->create();

        $response = $this->actingAs($user)
            ->withHeader('referer', route('forums.topics.show', [$forum->slug, $topic->slug]))
            ->post(route('forums.posts.store', [$forum->slug, $topic->slug]), [
                'content' => '',
            ]);

        $response->assertSessionHasErrors(['content']);
    });

    test('post creation fails when user has post restriction warning', function (): void {
        $group = Group::factory()->asDefaultMemberGroup()->create();
        $user = User::factory()->create();

        $warning = Warning::create([
            'name' => 'Test Warning',
            'points' => 10,
            'days_applied' => 30,
            'is_active' => true,
        ]);

        $warningConsequence = WarningConsequence::create([
            'type' => WarningConsequenceType::PostRestriction,
            'threshold' => 5,
            'duration_days' => 7,
            'is_active' => true,
        ]);

        UserWarning::create([
            'user_id' => $user->id,
            'warning_id' => $warning->id,
            'warning_consequence_id' => $warningConsequence->id,
            'points_at_issue' => 10,
            'points_expire_at' => now()->addDays(30),
            'consequence_expires_at' => now()->addDays(7),
        ]);

        $user->refresh();

        $forum = Forum::factory()->create(['is_active' => true]);
        $forum->groups()->attach($group, [
            'create' => true,
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

        $topic = Topic::factory()->for($forum)->create();
        Post::factory()->forum()->for($topic)->create();

        $response = $this->actingAs($user)
            ->withHeader('referer', route('forums.topics.show', [$forum->slug, $topic->slug]))
            ->post(route('forums.posts.store', [$forum->slug, $topic->slug]), [
                'content' => 'This is my reply to the topic.',
            ]);

        $response->assertSessionHasErrors(['content' => 'You have been restricted from posting.']);
    });

    test('post creation fails when content is too short', function (): void {
        $group = Group::factory()->asDefaultMemberGroup()->create();
        $user = User::factory()->create();

        $forum = Forum::factory()->create(['is_active' => true]);
        $forum->groups()->attach($group, [
            'create' => true,
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

        $topic = Topic::factory()->for($forum)->create();
        Post::factory()->forum()->for($topic)->create();

        $response = $this->actingAs($user)
            ->withHeader('referer', route('forums.topics.show', [$forum->slug, $topic->slug]))
            ->post(route('forums.posts.store', [$forum->slug, $topic->slug]), [
                'content' => 'A',
            ]);

        $response->assertSessionHasErrors(['content']);
    });

    test('post creation fails when content is too long', function (): void {
        $group = Group::factory()->asDefaultMemberGroup()->create();
        $user = User::factory()->create();

        $forum = Forum::factory()->create(['is_active' => true]);
        $forum->groups()->attach($group, [
            'create' => true,
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

        $topic = Topic::factory()->for($forum)->create();
        Post::factory()->forum()->for($topic)->create();

        $response = $this->actingAs($user)
            ->withHeader('referer', route('forums.topics.show', [$forum->slug, $topic->slug]))
            ->post(route('forums.posts.store', [$forum->slug, $topic->slug]), [
                'content' => str_repeat('A', 10001),
            ]);

        $response->assertSessionHasErrors(['content']);
    });
});
