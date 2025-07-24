<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __invoke(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $result = $post->toggleLike($request->emoji);

        return response()->json([
            'success' => true,
            'liked' => ! is_bool($result),
            'likes_count' => $post->fresh()->likes_count,
            'likes_summary' => $post->fresh()->likes_summary,
            'user_reactions' => $post->fresh()->user_reactions,
        ]);
    }
}
