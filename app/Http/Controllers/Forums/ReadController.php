<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadController extends Controller
{
    public function __invoke(Request $request, Forum $forum, Topic $topic): JsonResponse
    {
        $result = $topic->markAsRead();

        return response()->json([
            'success' => true,
            'marked_as_read' => ! is_bool($result),
            'is_read_by_user' => $topic->fresh()->is_read_by_user,
        ]);
    }
}
