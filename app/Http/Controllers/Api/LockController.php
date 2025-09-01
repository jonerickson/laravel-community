<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LockController extends Controller
{
    public function store(Request $request): JsonResource
    {
        $request->validate([
            'topic_id' => 'required|exists:topics,id',
        ]);

        $topic = Topic::findOrFail($request->input('topic_id'));
        $topic->lock();

        return ApiResource::success(
            resource: $topic,
            message: 'Topic has been locked successfully.'
        );
    }

    public function destroy(Request $request): JsonResource
    {
        $request->validate([
            'topic_id' => 'required|exists:topics,id',
        ]);

        $topic = Topic::findOrFail($request->input('topic_id'));
        $topic->unlock();

        return ApiResource::success(
            resource: $topic,
            message: 'Topic has been unlocked successfully.'
        );
    }
}
