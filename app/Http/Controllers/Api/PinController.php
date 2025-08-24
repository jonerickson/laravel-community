<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PinController extends Controller
{
    public function store(Request $request): JsonResource
    {
        $request->validate([
            'topic_id' => 'sometimes|required|exists:topics,id',
            'post_id' => 'sometimes|required|exists:posts,id',
        ]);

        if ($request->filled('topic_id')) {
            $topic = Topic::findOrFail($request->input('topic_id'));
            $topic->pin();

            return ApiResource::success(
                resource: $topic,
                message: 'Topic has been pinned successfully.'
            );
        }

        if ($request->filled('post_id')) {
            $post = Post::findOrFail($request->input('post_id'));
            $post->pin();

            return ApiResource::success(
                resource: $post,
                message: 'Post has been pinned successfully.'
            );
        }

        return ApiResource::error(message: 'Either topic_id or post_id is required.');
    }

    public function destroy(Request $request): JsonResource
    {
        $request->validate([
            'topic_id' => 'sometimes|required|exists:topics,id',
            'post_id' => 'sometimes|required|exists:posts,id',
        ]);

        if ($request->filled('topic_id')) {
            $topic = Topic::findOrFail($request->input('topic_id'));
            $topic->unpin();

            return ApiResource::success(
                resource: $topic,
                message: 'Topic has been unpinned successfully.'
            );
        }

        if ($request->filled('post_id')) {
            $post = Post::findOrFail($request->input('post_id'));
            $post->unpin();

            return ApiResource::success(
                resource: $post,
                message: 'Post has been unpinned successfully.'
            );
        }

        return ApiResource::error(message: 'Either topic_id or post_id is required.');
    }
}
