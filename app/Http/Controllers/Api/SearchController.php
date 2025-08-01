<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Policy;
use App\Models\Post;
use App\Models\Product;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResource
    {
        $query = $request->get('q', '');
        $types = $request->get('types', ['topic', 'post', 'policy', 'product']);
        $createdAfter = $request->get('created_after');
        $createdBefore = $request->get('created_before');
        $updatedAfter = $request->get('updated_after');
        $updatedBefore = $request->get('updated_before');

        if (is_string($types)) {
            $types = explode(',', $types);
        }

        $types = array_filter($types, fn ($type) => in_array($type, ['topic', 'post', 'policy', 'product']));

        if (empty($types)) {
            $types = ['topic', 'post', 'policy', 'product'];
        }

        if (empty($query) || strlen($query) < 2) {
            return ApiResource::success([], 'Search query too short', [
                'total' => 0,
                'query' => $query,
                'types' => $types,
                'date_filters' => [
                    'created_after' => $createdAfter,
                    'created_before' => $createdBefore,
                    'updated_after' => $updatedAfter,
                    'updated_before' => $updatedBefore,
                ],
            ]);
        }

        $limit = min((int) $request->get('limit', 10), 50);

        $topics = collect()
            ->when(in_array('topic', $types), function () use ($query, $limit, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore) {
                return Topic::search($query)
                    ->take($limit * 3)
                    ->get()
                    ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, function ($collection) use ($createdAfter, $createdBefore, $updatedAfter, $updatedBefore) {
                        return $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore);
                    })
                    ->take($limit)
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
                            'updated_at' => $topic->updated_at->toISOString(),
                        ];
                    });
            });

        $posts = collect()
            ->when(in_array('post', $types), function () use ($query, $limit, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore) {
                return Post::search($query)
                    ->take($limit * 3)
                    ->get()
                    ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, function ($collection) use ($createdAfter, $createdBefore, $updatedAfter, $updatedBefore) {
                        return $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore);
                    })
                    ->take($limit)
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
                            'updated_at' => $post->updated_at->toISOString(),
                        ];
                    });
            });

        $policies = collect()
            ->when(in_array('policy', $types), function () use ($query, $limit, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore) {
                return Policy::search($query)
                    ->take($limit * 3)
                    ->get()
                    ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, function ($collection) use ($createdAfter, $createdBefore, $updatedAfter, $updatedBefore) {
                        return $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore);
                    })
                    ->take($limit)
                    ->map(function (Policy $policy) {
                        return [
                            'id' => $policy->id,
                            'type' => 'policy',
                            'title' => $policy->title,
                            'version' => $policy->version,
                            'url' => $policy->url,
                            'category_name' => $policy->category->name,
                            'author_name' => $policy->author->name,
                            'effective_at' => $policy->effective_at?->toISOString(),
                            'created_at' => $policy->created_at->toISOString(),
                            'updated_at' => $policy->updated_at->toISOString(),
                        ];
                    });
            });

        $products = collect()
            ->when(in_array('product', $types), function () use ($query, $limit, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore) {
                return Product::search($query)
                    ->take($limit * 3)
                    ->get()
                    ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, function ($collection) use ($createdAfter, $createdBefore, $updatedAfter, $updatedBefore) {
                        return $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore);
                    })
                    ->take($limit)
                    ->map(function (Product $product) {
                        return [
                            'id' => $product->id,
                            'type' => 'product',
                            'title' => $product->name,
                            'description' => $product->description,
                            'url' => route('store.products.show', $product->slug),
                            'price' => $product->defaultPrice?->amount,
                            'category_name' => $product->categories->first()?->name,
                        ];
                    });
            });

        $results = $topics->concat($posts)->concat($policies)->concat($products)->take($limit);

        return ApiResource::success(
            resource: $results->values(),
            message: 'Search completed successfully',
            meta: [
                'total' => $results->count(),
                'query' => $query,
                'types' => $types,
                'date_filters' => [
                    'created_after' => $createdAfter,
                    'created_before' => $createdBefore,
                    'updated_after' => $updatedAfter,
                    'updated_before' => $updatedBefore,
                ],
                'counts' => [
                    'topics' => $topics->count(),
                    'posts' => $posts->count(),
                    'policies' => $policies->count(),
                    'products' => $products->count(),
                ],
            ]);
    }

    private function applyDateFiltersToCollection($collection, ?string $createdAfter, ?string $createdBefore, ?string $updatedAfter, ?string $updatedBefore)
    {
        return $collection
            ->when($createdAfter, fn ($col) => $col->filter(fn ($item) => $item->created_at >= $createdAfter))
            ->when($createdBefore, fn ($col) => $col->filter(fn ($item) => $item->created_at <= $createdBefore))
            ->when($updatedAfter, fn ($col) => $col->filter(fn ($item) => $item->updated_at >= $updatedAfter))
            ->when($updatedBefore, fn ($col) => $col->filter(fn ($item) => $item->updated_at <= $updatedBefore));
    }
}
