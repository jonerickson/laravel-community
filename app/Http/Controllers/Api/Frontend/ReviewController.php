<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StoreReviewRequest;
use App\Http\Resources\ApiResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request): ApiResource
    {
        $validated = $request->validated();

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
