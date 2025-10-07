<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Data\ReadData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Announcement;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReadController extends Controller
{
    public function __invoke(Request $request): ApiResource
    {
        $validated = $request->validate([
            'type' => 'required|string|in:topic,post,forum,announcement',
            'id' => 'required|integer',
        ]);

        $readable = $this->resolveReadable($validated['type'], $validated['id']);

        if (! $readable) {
            throw ValidationException::withMessages([
                'id' => ['The specified item could not be found.'],
            ]);
        }

        $result = $readable->markAsRead(Auth::user());

        $readData = ReadData::from([
            'markedAsRead' => ! is_bool($result),
            'isReadByUser' => $readable->fresh()->is_read_by_user,
            'type' => $validated['type'],
            'id' => $validated['id'],
        ]);

        return new ApiResource(
            resource: $readData,
        );
    }

    private function resolveReadable(string $type, int $id)
    {
        return match ($type) {
            'topic' => Topic::find($id),
            'post' => Post::find($id),
            'forum' => Forum::find($id),
            'announcement' => Announcement::find($id),
            default => null,
        };
    }
}
