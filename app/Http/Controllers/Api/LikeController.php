<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LikeController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function __invoke(Request $request): ApiResource
    {
        $validated = $request->validate([
            'type' => 'required|string|in:post,comment',
            'id' => 'required|integer',
            'emoji' => 'required',
        ]);

        $likeable = $this->resolveLikeable($validated['type'], $validated['id']);

        if (! $likeable) {
            throw ValidationException::withMessages([
                'id' => ['The specified item could not be found.'],
            ]);
        }

        $this->authorize('like', $likeable);

        $user = Auth::user();
        $result = $likeable->toggleLike($validated['emoji'], $user->id);

        $liked = $result instanceof Like;

        return new ApiResource(
            resource: [
                'liked' => $liked,
                'likes_count' => $likeable->likes_count,
                'likes_summary' => $likeable->likes_summary,
                'user_reactions' => $likeable->user_reactions,
                'type' => $validated['type'],
                'id' => $validated['id'],
            ],
            message: 'Item liked successfully.'
        );
    }

    private function resolveLikeable(string $type, int $id)
    {
        return match ($type) {
            'post' => Post::find($id),
            'comment' => Comment::find($id),
            default => null,
        };
    }
}
