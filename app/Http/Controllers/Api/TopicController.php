<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Forums\DeleteTopicAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Throwable;

class TopicController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws Throwable
     */
    public function destroy(Request $request): ApiResource
    {
        $validated = $request->validate([
            'topic_ids' => 'required|array|min:1',
            'topic_ids.*' => 'integer|exists:topics,id',
            'forum_id' => 'required|integer|exists:forums,id',
        ]);

        $forum = Forum::find($validated['forum_id']);
        $topics = Topic::whereIn('id', $validated['topic_ids'])->get();

        foreach ($topics as $topic) {
            $this->authorize('delete', $topic);

            DeleteTopicAction::execute($topic, $forum);
        }

        return ApiResource::success(
            resource: [
                'deleted_count' => count($validated['topic_ids']),
            ],
            message: 'Topics deleted successfully.',
        );
    }
}
