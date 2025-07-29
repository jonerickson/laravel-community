<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LikeController extends Controller
{
    public function __invoke(Request $request, Post $post): JsonResource
    {
        $request->validate([
            'emoji' => 'required|string|max:50',
        ]);

        $result = $post->toggleLike($request->input('emoji'));
        $freshPost = $post->fresh();

        return ApiResource::success([
            'liked' => ! is_bool($result),
            'likes_count' => $freshPost->likes_count,
            'likes_summary' => $freshPost->likes_summary,
            'user_reactions' => $freshPost->user_reactions,
        ], 'Reaction updated successfully');
    }
}
