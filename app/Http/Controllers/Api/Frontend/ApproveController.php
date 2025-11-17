<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StoreApproveRequest;
use App\Http\Resources\ApiResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\JsonResource;

class ApproveController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(StoreApproveRequest $request): JsonResource
    {
        $approvable = $request->resolveApprovable();

        if ($approvable === null) {
            return ApiResource::error(
                message: 'The specified item could not be found.'
            );
        }

        $this->authorize('approve', $approvable);

        $approvable->approve();

        return ApiResource::success(
            resource: $approvable->fresh(),
            message: sprintf('The %s has been successfully approved.', $request->validated('type'))
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(StoreApproveRequest $request): JsonResource
    {
        $approvable = $request->resolveApprovable();

        if ($approvable === null) {
            return ApiResource::error(
                message: 'The specified item could not be found.'
            );
        }

        $this->authorize('approve', $approvable);

        $approvable->unapprove();

        return ApiResource::success(
            resource: $approvable->fresh(),
            message: sprintf('The %s has been successfully unapproved.', $request->validated('type'))
        );
    }
}
