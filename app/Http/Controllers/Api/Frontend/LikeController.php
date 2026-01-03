<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Data\LikeSummaryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StoreLikeRequest;
use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LikeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        #[CurrentUser]
        private readonly User $user,
    ) {
        //
    }

    public function __invoke(StoreLikeRequest $request): ApiResource
    {
        $likeable = $request->resolveLikeable();

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
