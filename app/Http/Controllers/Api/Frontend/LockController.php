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
        $lockable = $request->resolveLockable();

        $this->authorize('lock', $lockable);

        $lockable->lock();

        return ApiResource::success(
            resource: $lockable,
            message: 'The item has been successfully locked.'
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(StoreLockRequest $request): JsonResource
    {
        $lockable = $request->resolveLockable();

        $this->authorize('lock', $lockable);

        $lockable->unlock();

        return ApiResource::success(
            resource: $lockable,
            message: 'The item has been successfully unlocked.'
        );
    }
}
