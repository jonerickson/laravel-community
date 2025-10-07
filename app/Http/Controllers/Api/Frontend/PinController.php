<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PinController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request): JsonResource
    {
        $request->validate([
            'topic_id' => 'sometimes|required|exists:topics,id',
            'post_id' => 'sometimes|required|exists:posts,id',
        ]);

        $pinnable = null;
        if ($request->filled('topic_id')) {
            $pinnable = Topic::findOrFail($request->input('topic_id'));
        }

        if ($request->filled('post_id')) {
            $pinnable = Post::findOrFail($request->input('post_id'));
        }

        if (blank($pinnable)) {
            return ApiResource::error(
                message: 'Please select either a topic or post is required.'
            );
        }

        $this->authorize('pin', $pinnable);

        $pinnable->pin();

        $class = Str::of($pinnable::class)->classBasename()->lower()->toString();

        return ApiResource::success(
            resource: $pinnable,
            message: "The $class has been successfully pinned."
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Request $request): JsonResource
    {
        $request->validate([
            'topic_id' => 'sometimes|required|exists:topics,id',
            'post_id' => 'sometimes|required|exists:posts,id',
        ]);

        $pinnable = null;
        if ($request->filled('topic_id')) {
            $pinnable = Topic::findOrFail($request->input('topic_id'));
        }

        if ($request->filled('post_id')) {
            $pinnable = Post::findOrFail($request->input('post_id'));
        }

        if (blank($pinnable)) {
            return ApiResource::error(
                message: 'Please select either a topic or post is required.'
            );
        }

        $this->authorize('pin', $pinnable);

        $pinnable->unpin();

        $class = Str::of($pinnable::class)->classBasename()->lower()->toString();

        return ApiResource::success(
            resource: $pinnable,
            message: "The $class has been successfully unpinned."
        );
    }
}
