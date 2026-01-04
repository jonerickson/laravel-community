<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StoreReviewRequest;
use App\Http\Resources\ApiResource;
use App\Models\Comment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReviewController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreReviewRequest $request): ApiResource
    {
        $this->authorize('create', Comment::class);

        $commentable = $request->resolveCommentable();

        $comment = $commentable->comments()->create([
            'content' => $request->validated('content'),
            'rating' => $request->validated('rating'),
            'parent_id' => $request->validated('parent_id'),
        ]);

        $comment->load('author');

        return ApiResource::created(
            resource: $comment,
            message: 'Your review was successfully added.'
        );
    }
}
