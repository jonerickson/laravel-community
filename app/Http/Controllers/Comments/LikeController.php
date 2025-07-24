<?php

declare(strict_types=1);

namespace App\Http\Controllers\Comments;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __invoke(Request $request, Comment $comment): JsonResponse
    {
        $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $result = $comment->toggleLike($request->emoji);

        return response()->json([
            'success' => true,
            'liked' => ! is_bool($result),
            'likes_count' => $comment->fresh()->likes_count,
            'likes_summary' => $comment->fresh()->likes_summary,
            'user_reactions' => $comment->fresh()->user_reactions,
        ]);
    }
}
