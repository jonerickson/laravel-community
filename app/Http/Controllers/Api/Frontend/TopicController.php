<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Actions\Forums\DeleteTopicAction;
use App\Actions\Forums\MoveTopicAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\DestroyTopicRequest;
use App\Http\Requests\Api\Frontend\MoveTopicRequest;
use App\Http\Resources\ApiResource;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Throwable;

class TopicController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws Throwable
     */
    public function update(MoveTopicRequest $request): ApiResource
    {
        $topic = Topic::findOrFail($request->validated('topic_id'));
        $targetForum = Forum::findOrFail($request->validated('target_forum_id'));

        $this->authorize('move', $topic->forum);
        $this->authorize('move', $targetForum);

        MoveTopicAction::execute($topic, $targetForum);

        return ApiResource::success(
            resource: [
                'topic_id' => $topic->id,
                'target_forum_id' => $targetForum->id,
            ],
            message: 'The topic was moved successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function destroy(DestroyTopicRequest $request): ApiResource
    {
        $forum = Forum::find($request->validated('forum_id'));
        $topics = Topic::whereIn('id', $request->validated('topic_ids'))->get();

        foreach ($topics as $topic) {
            $this->authorize('delete', $topic);

            DeleteTopicAction::execute($topic, $forum);
        }

        return ApiResource::success(
            resource: [
                'deleted_count' => count($request->validated('topic_ids')),
            ],
            message: 'The topic(s) were successfully deleted.',
        );
    }
}
