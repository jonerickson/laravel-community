<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StoreApproveRequest;
use App\Http\Resources\ApiResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\JsonResource;

class ApproveController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreApproveRequest $request): JsonResource
    {
        $this->authorize('moderate', $request->resolveAuthorizable());

        $approvable = $request->resolveApprovable();

        $approvable->approve();

        return ApiResource::success(
            resource: $approvable->fresh(),
            message: sprintf('The %s has been successfully approved.', $request->validated('type'))
        );
    }

    public function destroy(StoreApproveRequest $request): JsonResource
    {
        $this->authorize('moderate', $request->resolveAuthorizable());

        $approvable = $request->resolveApprovable();

        $approvable->unapprove();

        return ApiResource::success(
            resource: $approvable->fresh(),
            message: sprintf('The %s has been successfully unapproved.', $request->validated('type'))
        );
    }
}
