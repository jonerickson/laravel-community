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

        if (! $followable) {
            return ApiResource::error(
                message: 'The specified item could not be found.'
            );
        }

        $followable->follow();

        return ApiResource::success(
            message: "You have successfully followed the {$request->validated('type')}."
        );
    }

    public function destroy(StoreFollowRequest $request): ApiResource
    {
        $followable = $request->resolveFollowable();

        if (! $followable) {
            return ApiResource::error(
                message: 'The specified item could not be found.'
            );
        }

        $followable->unfollow();

        return ApiResource::success(
            message: "You have successfully unfollowed the {$request->validated('type')}."
        );
    }
}
