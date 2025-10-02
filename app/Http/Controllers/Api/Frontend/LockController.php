<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Topic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LockController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request): JsonResource
    {
        $request->validate([
            'topic_id' => 'required|exists:topics,id',
        ]);

        $topic = Topic::findOrFail($request->input('topic_id'));

        $this->authorize('lock', $topic);

        $topic->lock();

        return ApiResource::success(
            resource: $topic,
            message: 'Topic has been locked successfully.'
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Request $request): JsonResource
    {
        $request->validate([
            'topic_id' => 'required|exists:topics,id',
        ]);

        $topic = Topic::findOrFail($request->input('topic_id'));

        $this->authorize('lock', $topic);

        $topic->unlock();

        return ApiResource::success(
            resource: $topic,
            message: 'Topic has been unlocked successfully.'
        );
    }
}
