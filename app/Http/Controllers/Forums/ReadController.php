<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadController extends Controller
{
    public function __invoke(Request $request, Forum $forum, Topic $topic): JsonResource
    {
        $result = $topic->markAsRead();

        return ApiResource::success([
            'marked_as_read' => ! is_bool($result),
            'is_read_by_user' => $topic->fresh()->is_read_by_user,
        ], 'Topic marked as read successfully');
    }
}
