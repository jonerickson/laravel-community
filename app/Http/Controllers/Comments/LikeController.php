<?php

declare(strict_types=1);

namespace App\Http\Controllers\Comments;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LikeController extends Controller
{
    public function __invoke(Request $request, Comment $comment): JsonResource
    {
        $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $result = $comment->toggleLike($request->emoji);
        $freshComment = $comment->fresh();

        return ApiResource::success([
            'liked' => ! is_bool($result),
            'likes_count' => $freshComment->likes_count,
            'likes_summary' => $freshComment->likes_summary,
            'user_reactions' => $freshComment->user_reactions,
        ], 'Comment reaction updated successfully');
    }
}
