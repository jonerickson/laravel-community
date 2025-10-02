<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublishController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request): JsonResource
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
        ]);

        $post = Post::findOrFail($request->input('post_id'));

        $this->authorize('publish', $post);

        $post->update(['is_published' => true]);

        return ApiResource::success(
            resource: $post->fresh(),
            message: 'Post has been published successfully.'
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Request $request): JsonResource
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
        ]);

        $post = Post::findOrFail($request->input('post_id'));

        $this->authorize('publish', $post);

        $post->update(['is_published' => false]);

        return ApiResource::success(
            resource: $post->fresh(),
            message: 'Post has been unpublished successfully.'
        );
    }
}
