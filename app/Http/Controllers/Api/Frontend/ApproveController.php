<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

class ApproveController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request): JsonResource
    {
        $validated = $request->validate([
            'type' => 'required|string|in:post,comment',
            'id' => 'required|integer',
        ]);

        $approvable = $this->resolveApprovable($validated['type'], $validated['id']);

        if (! $approvable) {
            throw ValidationException::withMessages([
                'id' => ['The specified item could not be found.'],
            ]);
        }

        $this->authorize('approve', $approvable);

        $approvable->approve();

        return ApiResource::success(
            resource: $approvable->fresh(),
            message: "The {$validated['type']} has been successfully approved."
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Request $request): JsonResource
    {
        $validated = $request->validate([
            'type' => 'required|string|in:post,comment',
            'id' => 'required|integer',
        ]);

        $approvable = $this->resolveApprovable($validated['type'], $validated['id']);

        if (! $approvable) {
            throw ValidationException::withMessages([
                'id' => ['The specified item could not be found.'],
            ]);
        }

        $this->authorize('approve', $approvable);

        $approvable->unapprove();

        return ApiResource::success(
            resource: $approvable->fresh(),
            message: "The {$validated['type']} has been successfully unapproved."
        );
    }

    private function resolveApprovable(string $type, int $id)
    {
        return match ($type) {
            'post' => Post::find($id),
            'comment' => Comment::find($id),
            default => null,
        };
    }
}
