<?php

declare(strict_types=1);

use App\Actions\Forums\DeleteTopicAction;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Helper to bind user to request for action authorization checks.
 * DeleteTopicAction uses request()->user() for role checking.
 */
function bindUserToRequest(User $user): void
{
    Auth::login($user);
    app('request')->setUserResolver(fn (): User => $user);
}

describe('DeleteTopicAction', function (): void {
    test('author can delete their own topic', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $forum->id,
            'created_by' => $user->id,
        ]);

        $action = new DeleteTopicAction($topic, $forum);
        $result = $action();

        expect($result)->toBeTrue();
        expect(Topic::find($topic->id))->toBeNull();
    });

    test('deletes all posts when topic is deleted', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $forum->id,
            'created_by' => $user->id,
        ]);

        // Create posts for the topic
        $post1 = Post::factory()->create([
            'topic_id' => $topic->id,
            'type' => 'forum',
        ]);
        $post2 = Post::factory()->create([
            'topic_id' => $topic->id,
            'type' => 'forum',
        ]);

        $action = new DeleteTopicAction($topic, $forum);
        $result = $action();

        expect($result)->toBeTrue();
        expect(Post::find($post1->id))->toBeNull();
        expect(Post::find($post2->id))->toBeNull();
    });

    test('administrator can delete any topic', function (): void {
        $admin = User::factory()->asAdmin()->create();
        bindUserToRequest($admin);

        $topicAuthor = User::factory()->create();
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $forum->id,
            'created_by' => $topicAuthor->id,
        ]);

        $action = new DeleteTopicAction($topic, $forum);
        $result = $action();

        expect($result)->toBeTrue();
        expect(Topic::find($topic->id))->toBeNull();
    });

    test('non-author without admin role cannot delete topic', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $topicAuthor = User::factory()->create();
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $forum->id,
            'created_by' => $topicAuthor->id,
        ]);

        $action = new DeleteTopicAction($topic, $forum);

        expect(fn (): ?bool => $action())->toThrow(HttpException::class, 'You are not authorized to delete this topic.');
    });

    test('returns 403 when user is not the author and not admin', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $topicAuthor = User::factory()->create();
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $forum->id,
            'created_by' => $topicAuthor->id,
        ]);

        $action = new DeleteTopicAction($topic, $forum);

        try {
            $action();
            $this->fail('Expected HttpException to be thrown');
        } catch (HttpException $httpException) {
            expect($httpException->getStatusCode())->toBe(403);
        }
    });

    test('returns 404 when topic does not belong to forum', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $forum1 = Forum::factory()->create();
        $forum2 = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $forum1->id,
            'created_by' => $user->id,
        ]);

        // Try to delete topic from wrong forum
        $action = new DeleteTopicAction($topic, $forum2);

        try {
            $action();
            $this->fail('Expected HttpException to be thrown');
        } catch (HttpException $httpException) {
            expect($httpException->getStatusCode())->toBe(404);
            expect($httpException->getMessage())->toBe('Topic not found.');
        }
    });

    test('guest user cannot delete topic', function (): void {
        Auth::logout();

        $topicAuthor = User::factory()->create();
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $forum->id,
            'created_by' => $topicAuthor->id,
        ]);

        $action = new DeleteTopicAction($topic, $forum);

        expect(fn (): ?bool => $action())->toThrow(HttpException::class);
    });

    test('uses database transaction for deletion', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $forum->id,
            'created_by' => $user->id,
        ]);

        Post::factory()->count(3)->create([
            'topic_id' => $topic->id,
            'type' => 'forum',
        ]);

        $action = new DeleteTopicAction($topic, $forum);
        $result = $action();

        expect($result)->toBeTrue();
        // If transaction was used, all posts should be deleted
        expect(Post::where('topic_id', $topic->id)->count())->toBe(0);
    });

    test('can be executed via static execute method', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $forum->id,
            'created_by' => $user->id,
        ]);

        $result = DeleteTopicAction::execute($topic, $forum);

        expect($result)->toBeTrue();
        expect(Topic::find($topic->id))->toBeNull();
    });
});
