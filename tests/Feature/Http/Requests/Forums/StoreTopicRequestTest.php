<?php

declare(strict_types=1);

use App\Enums\WarningConsequenceType;
use App\Http\Requests\Forums\StoreTopicRequest;
use App\Models\Forum;
use App\Models\Group;
use App\Models\User;
use App\Models\UserWarning;
use App\Models\Warning;
use App\Models\WarningConsequence;
use Illuminate\Support\Facades\Auth;

describe('StoreTopicRequest validation', function (): void {
    test('validation passes with valid title and content', function (): void {
        $request = new StoreTopicRequest([
            'title' => 'My Topic Title',
            'content' => 'This is the content of my topic.',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when title is missing', function (): void {
        $request = new StoreTopicRequest([
            'content' => 'This is the content of my topic.',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('title'))->toBeTrue();
    });

    test('validation fails when title is empty', function (): void {
        $request = new StoreTopicRequest([
            'title' => '',
            'content' => 'This is the content of my topic.',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('title'))->toBeTrue();
    });

    test('validation fails when title is too short', function (): void {
        $request = new StoreTopicRequest([
            'title' => 'A',
            'content' => 'This is the content of my topic.',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('title'))->toBeTrue();
    });

    test('validation passes when title is at minimum length', function (): void {
        $request = new StoreTopicRequest([
            'title' => 'AB',
            'content' => 'This is the content of my topic.',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when title is too long', function (): void {
        $request = new StoreTopicRequest([
            'title' => str_repeat('A', 256),
            'content' => 'This is the content of my topic.',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('title'))->toBeTrue();
    });

    test('validation passes when title is at maximum length', function (): void {
        $request = new StoreTopicRequest([
            'title' => str_repeat('A', 255),
            'content' => 'This is the content of my topic.',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when title is not a string', function (): void {
        $request = new StoreTopicRequest([
            'title' => 12345,
            'content' => 'This is the content of my topic.',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('title'))->toBeTrue();
    });

    test('validation fails when content is missing', function (): void {
        $request = new StoreTopicRequest([
            'title' => 'My Topic Title',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation fails when content is empty', function (): void {
        $request = new StoreTopicRequest([
            'title' => 'My Topic Title',
            'content' => '',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation fails when content is too short', function (): void {
        $request = new StoreTopicRequest([
            'title' => 'My Topic Title',
            'content' => 'A',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation passes when content is at minimum length', function (): void {
        $request = new StoreTopicRequest([
            'title' => 'My Topic Title',
            'content' => 'AB',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when content is too long', function (): void {
        $request = new StoreTopicRequest([
            'title' => 'My Topic Title',
            'content' => str_repeat('A', 10001),
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation passes when content is at maximum length', function (): void {
        $request = new StoreTopicRequest([
            'title' => 'My Topic Title',
            'content' => str_repeat('A', 10000),
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when content is not a string', function (): void {
        $request = new StoreTopicRequest([
            'title' => 'My Topic Title',
            'content' => 12345,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });
});

describe('StoreTopicRequest custom messages', function (): void {
    test('title required message is customized', function (): void {
        $request = new StoreTopicRequest([
            'title' => '',
            'content' => 'This is the content of my topic.',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('title'))->toBe('Please provide a title for your topic.');
    });

    test('title max message is customized', function (): void {
        $request = new StoreTopicRequest([
            'title' => str_repeat('A', 256),
            'content' => 'This is the content of my topic.',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('title'))->toBe('The title cannot be longer than 255 characters.');
    });

    test('content required message is customized', function (): void {
        $request = new StoreTopicRequest([
            'title' => 'My Topic Title',
            'content' => '',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('content'))->toBe('Please provide content for your topic.');
    });
});

describe('StoreTopicRequest authorization', function (): void {
    test('authorize returns true when user is authenticated', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $request = new StoreTopicRequest;

        expect($request->authorize())->toBeTrue();

        Auth::logout();
    });

    test('authorize returns false when user is guest', function (): void {
        $request = new StoreTopicRequest;

        expect($request->authorize())->toBeFalse();
    });
});

describe('StoreTopicRequest HTTP layer', function (): void {
    test('topic can be created with valid data', function (): void {
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

        $response = $this->actingAs($user)->post(route('forums.topics.store', $forum->slug), [
            'title' => 'My Test Topic',
            'content' => 'This is the content of my test topic.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('topics', [
            'forum_id' => $forum->id,
            'title' => 'My Test Topic',
            'created_by' => $user->id,
        ]);
    });

    test('topic creation requires authentication', function (): void {
        $forum = Forum::factory()->create(['is_active' => true]);

        $response = $this->post(route('forums.topics.store', $forum->slug), [
            'title' => 'My Test Topic',
            'content' => 'This is the content of my test topic.',
        ]);

        $response->assertRedirect(route('login'));
    });

    test('topic creation fails with validation errors', function (): void {
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

        $response = $this->actingAs($user)->post(route('forums.topics.store', $forum->slug), [
            'title' => '',
            'content' => '',
        ]);

        $response->assertSessionHasErrors(['title', 'content']);
    });

    test('topic creation fails when user has post restriction warning', function (): void {
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

        $response = $this->actingAs($user)->post(route('forums.topics.store', $forum->slug), [
            'title' => 'My Test Topic',
            'content' => 'This is the content of my test topic.',
        ]);

        $response->assertSessionHasErrors(['content' => 'You have been restricted from posting.']);
    });

    test('topic creation fails when title is too short', function (): void {
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

        $response = $this->actingAs($user)->post(route('forums.topics.store', $forum->slug), [
            'title' => 'A',
            'content' => 'This is the content of my test topic.',
        ]);

        $response->assertSessionHasErrors(['title']);
    });

    test('topic creation fails when content is too long', function (): void {
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

        $response = $this->actingAs($user)->post(route('forums.topics.store', $forum->slug), [
            'title' => 'Valid Title',
            'content' => str_repeat('A', 10001),
        ]);

        $response->assertSessionHasErrors(['content']);
    });
});
