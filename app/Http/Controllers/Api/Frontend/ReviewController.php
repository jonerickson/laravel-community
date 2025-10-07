<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request): ApiResource
    {
        $this->authorize('create', Comment::class);

        $validated = $request->validate([
            'commentable_type' => 'required|string|in:post,comment,product',
            'commentable_id' => 'required|integer',
            'content' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        if (empty($validated['content']) && empty($validated['rating'])) {
            return ApiResource::error('Either content or rating must be provided', [], 422);
        }

        $commentableType = match ($validated['commentable_type']) {
            'post' => Post::class,
            'comment' => Comment::class,
            'product' => Product::class,
        };

        $comment = Comment::create([
            'commentable_type' => $commentableType,
            'commentable_id' => $validated['commentable_id'],
            'content' => $validated['content'],
            'rating' => $validated['rating'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $comment->load('author');

        return ApiResource::created(
            resource: $comment,
            message: 'Your review was successfully added.'
        );
    }
}
