<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StorePinRequest;
use App\Http\Resources\ApiResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PinController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(StorePinRequest $request): JsonResource
    {
        $pinnable = $request->resolvePinnable();

        if (! $pinnable) {
            return ApiResource::error(
                message: 'Please select either a topic or post to pin.'
            );
        }

        $this->authorize('pin', $pinnable);

        $pinnable->pin();

        $class = Str::of($pinnable::class)->classBasename()->lower()->toString();

        return ApiResource::success(
            resource: $pinnable,
            message: "The $class has been successfully pinned."
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(StorePinRequest $request): JsonResource
    {
        $pinnable = $request->resolvePinnable();

        if (! $pinnable) {
            return ApiResource::error(
                message: 'Please select either a topic or post to unpin.'
            );
        }

        $this->authorize('pin', $pinnable);

        $pinnable->unpin();

        $class = Str::of($pinnable::class)->classBasename()->lower()->toString();

        return ApiResource::success(
            resource: $pinnable,
            message: "The $class has been successfully unpinned."
        );
    }
}
