<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StorePinRequest;
use App\Http\Resources\ApiResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\JsonResource;

class PinController extends Controller
{
    use AuthorizesRequests;

    public function store(StorePinRequest $request): JsonResource
    {
        $this->authorize('pin', $request->resolveAuthorizable());

        $pinnable = $request->resolvePinnable();

        $pinnable->pin();

        return ApiResource::success(
            resource: $pinnable,
            message: sprintf('The %s has been successfully pinned.', $request->validated('type'))
        );
    }

    public function destroy(StorePinRequest $request): JsonResource
    {
        $this->authorize('pin', $request->resolveAuthorizable());

        $pinnable = $request->resolvePinnable();

        $pinnable->unpin();

        return ApiResource::success(
            resource: $pinnable,
            message: sprintf('The %s has been successfully unpinned.', $request->validated('type'))
        );
    }
}
