<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Policy;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResource
    {
        $query = $request->get('q', '');

        if (empty($query) || strlen($query) < 2) {
            return ApiResource::success([], 'Search query too short', [
                'total' => 0,
                'query' => $query,
            ]);
        }

        $limit = min((int) $request->get('limit', 10), 50);

        $topics = Topic::search($query)
            ->take($limit)
            ->get()
            ->map(function (Topic $topic) {
                return [
                    'id' => $topic->id,
                    'type' => 'topic',
                    'title' => $topic->title,
                    'description' => $topic->description,
                    'url' => route('forums.topics.show', [$topic->forum->slug, $topic->slug]),
                    'forum_name' => $topic->forum->name,
                    'author_name' => $topic->author->name,
                    'created_at' => $topic->created_at->toISOString(),
                ];
            });

        $posts = Post::search($query)
            ->take($limit)
            ->get()
            ->map(function (Post $post) {
                return [
                    'id' => $post->id,
                    'type' => 'post',
                    'title' => $post->title,
                    'excerpt' => $post->excerpt ?: substr(strip_tags($post->content), 0, 150).'...',
                    'url' => $post->url,
                    'post_type' => $post->type->value,
                    'author_name' => $post->author->name,
                    'created_at' => $post->created_at->toISOString(),
                ];
            });

        $policies = Policy::search($query)
            ->take($limit)
            ->get()
            ->map(function (Policy $policy) {
                return [
                    'id' => $policy->id,
                    'type' => 'policy',
                    'title' => $policy->title,
                    'version' => $policy->version,
                    'url' => $policy->url,
                    'category_name' => $policy->category->name,
                    'author_name' => $policy->author->name,
                    'effective_date' => $policy->effective_date?->toISOString(),
                    'created_at' => $policy->created_at->toISOString(),
                ];
            });

        $results = $topics->concat($posts)->concat($policies)->take($limit);

        return ApiResource::success($results->values(), 'Search completed successfully', [
            'total' => $results->count(),
            'query' => $query,
            'counts' => [
                'topics' => $topics->count(),
                'posts' => $posts->count(),
                'policies' => $policies->count(),
            ],
        ]);
    }
}
