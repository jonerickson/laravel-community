<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Data\LikeSummaryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StoreLikeRequest;
use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
    public function __invoke(StoreLikeRequest $request): ApiResource
    {
        $likeable = $request->resolveLikeable();

        if (! $likeable) {
            return ApiResource::error(
                message: 'Unable to find an item to like/unlike.'
            );
        }

        $this->authorize('like', $likeable);

        $likeable->toggleLike($request->validated('emoji'), $this->user->id);

        $likeSummaryData = LikeSummaryData::from([
            'likesSummary' => $likeable->likes_summary,
            'userReactions' => $likeable->user_reactions,
        ]);

        return new ApiResource(
            resource: $likeSummaryData,
        );
    }
}
