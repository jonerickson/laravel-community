<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Data\LikeSummaryData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LikeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        #[CurrentUser]
        private readonly User $user,
    ) {
        //
    }

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

        $likeable->toggleLike($validated['emoji'], $this->user->id);

        $likeSummaryData = LikeSummaryData::from([
            'likesSummary' => $likeable->likes_summary,
            'userReactions' => $likeable->user_reactions,
        ]);

        return new ApiResource(
            resource: $likeSummaryData,
        );
    }

    private function resolveLikeable(string $type, int $id): Post|Comment|null
    {
        return match ($type) {
            'post' => Post::find($id),
            'comment' => Comment::find($id),
            default => null,
        };
    }
}
