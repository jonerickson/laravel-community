<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StorePublishRequest;
use App\Http\Resources\ApiResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\JsonResource;

class PublishController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(StorePublishRequest $request): JsonResource
    {
        $publishable = $request->resolvePublishable();

        $this->authorize('publish', $publishable);

        $publishable->publish();

        return ApiResource::success(
            resource: $publishable->fresh(),
            message: 'The item has been successfully published.'
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(StorePublishRequest $request): JsonResource
    {
        $publishable = $request->resolvePublishable();

        $this->authorize('publish', $publishable);

        $publishable->unpublish();

        return ApiResource::success(
            resource: $publishable->fresh(),
            message: 'The item has been successfully unpublished.'
        );
    }
}
