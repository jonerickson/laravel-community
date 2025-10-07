<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FollowController extends Controller
{
    public function store(Request $request): ApiResource
    {
        $validated = $request->validate([
            'type' => 'required|string|in:forum,topic',
            'id' => 'required|integer',
        ]);

        $followable = $this->resolveFollowable($validated['type'], $validated['id']);

        if (! $followable) {
            throw ValidationException::withMessages([
                'id' => ['The specified item could not be found.'],
            ]);
        }

        $followable->follow();

        return ApiResource::success(
            message: "You have successfully followed the {$validated['type']}."
        );
    }

    public function destroy(Request $request): ApiResource
    {
        $validated = $request->validate([
            'type' => 'required|string|in:forum,topic',
            'id' => 'required|integer',
        ]);

        $followable = $this->resolveFollowable($validated['type'], $validated['id']);

        if (! $followable) {
            throw ValidationException::withMessages([
                'id' => ['The specified item could not be found.'],
            ]);
        }

        $followable->unfollow();

        return ApiResource::success(
            message: "You have successfully unfollowed the {$validated['type']}."
        );
    }

    private function resolveFollowable(string $type, int $id): Forum|Topic|null
    {
        return match ($type) {
            'forum' => Forum::find($id),
            'topic' => Topic::find($id),
            default => null,
        };
    }
}
