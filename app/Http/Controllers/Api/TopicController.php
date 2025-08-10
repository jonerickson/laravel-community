<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use BezhanSalleh\FilamentShield\Support\Utils;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TopicController extends Controller
{
    public function destroy(Request $request): JsonResponse
    {
        if (! Auth::user()?->hasRole(Utils::getSuperAdminName())) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $validated = $request->validate([
            'topic_ids' => 'required|array|min:1',
            'topic_ids.*' => 'integer|exists:topics,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $topics = Topic::whereIn('id', $validated['topic_ids'])->get();

                foreach ($topics as $topic) {
                    $topic->posts()->delete();
                    $topic->delete();
                }
            });

            return response()->json([
                'message' => 'Topics deleted successfully.',
                'deleted_count' => count($validated['topic_ids']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting topics.',
            ], 500);
        }
    }
}
