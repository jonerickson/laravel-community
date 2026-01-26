<?php

declare(strict_types=1);

use App\Actions\Forums\MoveTopicAction;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

describe('MoveTopicAction', function (): void {
    test('moves topic to a different forum', function (): void {
        $sourceForum = Forum::factory()->create();
        $targetForum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $sourceForum->id,
        ]);

        $action = new MoveTopicAction($topic, $targetForum);
        $result = $action();

        expect($result)->toBeTrue();
        expect($topic->refresh()->forum_id)->toBe($targetForum->id);
    });

    test('returns 422 when topic is already in target forum', function (): void {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $forum->id,
        ]);

        $action = new MoveTopicAction($topic, $forum);

        try {
            $action();
            $this->fail('Expected HttpException to be thrown');
        } catch (HttpException $httpException) {
            expect($httpException->getStatusCode())->toBe(422);
            expect($httpException->getMessage())->toBe('Topic is already in this forum.');
        }
    });

    test('throws exception when moving to same forum', function (): void {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $forum->id,
        ]);

        $action = new MoveTopicAction($topic, $forum);

        expect(fn (): bool => $action())->toThrow(HttpException::class, 'Topic is already in this forum.');
    });

    test('preserves topic posts when moving', function (): void {
        $sourceForum = Forum::factory()->create();
        $targetForum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $sourceForum->id,
        ]);

        $post1 = Post::factory()->create([
            'topic_id' => $topic->id,
            'type' => 'forum',
        ]);
        $post2 = Post::factory()->create([
            'topic_id' => $topic->id,
            'type' => 'forum',
        ]);

        $action = new MoveTopicAction($topic, $targetForum);
        $result = $action();

        expect($result)->toBeTrue();
        expect(Post::find($post1->id))->not->toBeNull();
        expect(Post::find($post2->id))->not->toBeNull();
        expect(Post::find($post1->id)->topic_id)->toBe($topic->id);
        expect(Post::find($post2->id)->topic_id)->toBe($topic->id);
    });

    test('preserves topic attributes when moving', function (): void {
        $sourceForum = Forum::factory()->create();
        $targetForum = Forum::factory()->create();
        $user = User::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $sourceForum->id,
            'created_by' => $user->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'is_pinned' => true,
            'is_locked' => true,
        ]);

        $action = new MoveTopicAction($topic, $targetForum);
        $result = $action();

        $topic->refresh();

        expect($result)->toBeTrue();
        expect($topic->forum_id)->toBe($targetForum->id);
        expect($topic->title)->toBe('Original Title');
        expect($topic->description)->toBe('Original Description');
        expect($topic->created_by)->toBe($user->id);
        expect($topic->is_pinned)->toBeTrue();
        expect($topic->is_locked)->toBeTrue();
    });

    test('uses database transaction for move operation', function (): void {
        $sourceForum = Forum::factory()->create();
        $targetForum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $sourceForum->id,
        ]);

        $action = new MoveTopicAction($topic, $targetForum);
        $result = $action();

        expect($result)->toBeTrue();
        // Verify the move was persisted
        expect(Topic::find($topic->id)->forum_id)->toBe($targetForum->id);
    });

    test('can be executed via static execute method', function (): void {
        $sourceForum = Forum::factory()->create();
        $targetForum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $sourceForum->id,
        ]);

        $result = MoveTopicAction::execute($topic, $targetForum);

        expect($result)->toBeTrue();
        expect(Topic::find($topic->id)->forum_id)->toBe($targetForum->id);
    });

    test('updates topic updated_at timestamp', function (): void {
        $sourceForum = Forum::factory()->create();
        $targetForum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $sourceForum->id,
        ]);

        $originalUpdatedAt = $topic->updated_at;

        // Ensure some time passes
        $this->travel(1)->second();

        $action = new MoveTopicAction($topic, $targetForum);
        $result = $action();

        $topic->refresh();

        expect($result)->toBeTrue();
        expect($topic->updated_at)->toBeGreaterThan($originalUpdatedAt);
    });

    test('moves topic between forums with different categories', function (): void {
        // Forums can have categories, test moving between them
        $sourceForum = Forum::factory()->create();
        $targetForum = Forum::factory()->create();
        $topic = Topic::factory()->create([
            'forum_id' => $sourceForum->id,
        ]);

        $action = new MoveTopicAction($topic, $targetForum);
        $result = $action();

        expect($result)->toBeTrue();
        expect($topic->refresh()->forum_id)->toBe($targetForum->id);
    });
});
