<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StoreFollowRequest;
use App\Http\Resources\ApiResource;

class FollowController extends Controller
{
    public function store(StoreFollowRequest $request): ApiResource
    {
        $followable = $request->resolveFollowable();

        if ($followable === null) {
            return ApiResource::error(
                message: 'The specified item could not be found.'
            );
        }

        $followable->follow();

        return ApiResource::success(
            message: sprintf('You have successfully followed the %s.', $request->validated('type'))
        );
    }

    public function destroy(StoreFollowRequest $request): ApiResource
    {
        $followable = $request->resolveFollowable();

        if ($followable === null) {
            return ApiResource::error(
                message: 'The specified item could not be found.'
            );
        }

        $followable->unfollow();

        return ApiResource::success(
            message: sprintf('You have successfully unfollowed the %s.', $request->validated('type'))
        );
    }
}
