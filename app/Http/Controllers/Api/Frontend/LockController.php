<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StoreLockRequest;
use App\Http\Resources\ApiResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\JsonResource;

class LockController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(StoreLockRequest $request): JsonResource
    {
        $this->authorize('lock', $request->resolveAuthorizable());

        $lockable = $request->resolveLockable();

        $lockable->lock();

        return ApiResource::success(
            resource: $lockable,
            message: sprintf('The %s has been successfully locked.', $request->validated('type')),
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(StoreLockRequest $request): JsonResource
    {
        $this->authorize('lock', $request->resolveAuthorizable());

        $lockable = $request->resolveLockable();

        $lockable->unlock();

        return ApiResource::success(
            resource: $lockable,
            message: sprintf('The %s has been successfully unlocked.', $request->validated('type')),
        );
    }
}
