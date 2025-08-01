<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request): JsonResource
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'content' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        if (empty($validated['content']) && empty($validated['rating'])) {
            return ApiResource::error('Either content or rating must be provided', [], 422);
        }

        $comment = Comment::create([
            'commentable_type' => $validated['commentable_type'],
            'commentable_id' => $validated['commentable_id'],
            'content' => $validated['content'],
            'rating' => $validated['rating'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'is_approved' => true,
            'created_by' => Auth::id(),
        ]);

        $comment->load('author');

        return ApiResource::created($comment, 'Comment created successfully');
    }
}
