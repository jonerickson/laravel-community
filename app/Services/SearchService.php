<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\PaginatedData;
use App\Models\Policy;
use App\Models\Post;
use App\Models\Product;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class SearchService
{
    public function search(
        string $query,
        array $types = ['policy', 'post', 'product', 'topic', 'user'],
        ?string $createdAfter = null,
        ?string $createdBefore = null,
        ?string $updatedAfter = null,
        ?string $updatedBefore = null,
        ?int $limit = null
    ): array {
        $results = collect();
        $counts = [
            'topics' => 0,
            'posts' => 0,
            'policies' => 0,
            'products' => 0,
            'users' => 0,
        ];

        if (blank($query) || strlen($query) < 2) {
            return [
                'results' => $results,
                'counts' => $counts,
            ];
        }

        if (in_array('post', $types)) {
            $posts = $this->searchPosts(
                $query,
                $createdAfter,
                $createdBefore,
                $updatedAfter,
                $updatedBefore,
                $limit
            );

            $counts['posts'] = $posts->count();
            $results = $results->concat($posts);
        }

        if (in_array('policy', $types)) {
            $policies = $this->searchPolicies(
                $query,
                $createdAfter,
                $createdBefore,
                $updatedAfter,
                $updatedBefore,
                $limit
            );

            $counts['policies'] = $policies->count();
            $results = $results->concat($policies);
        }

        if (in_array('product', $types)) {
            $products = $this->searchProducts(
                $query,
                $createdAfter,
                $createdBefore,
                $updatedAfter,
                $updatedBefore,
                $limit
            );

            $counts['products'] = $products->count();
            $results = $results->concat($products);
        }

        if (in_array('topic', $types)) {
            $topics = $this->searchTopics(
                $query,
                $createdAfter,
                $createdBefore,
                $updatedAfter,
                $updatedBefore,
                $limit
            );

            $counts['topics'] = $topics->count();
            $results = $results->concat($topics);
        }

        if (in_array('user', $types)) {
            $users = $this->searchUsers(
                $query,
                $createdAfter,
                $createdBefore,
                $updatedAfter,
                $updatedBefore,
                $limit
            );

            $counts['users'] = $users->count();

            $results = $results->concat($users);
        }

        return [
            'results' => $results,
            'counts' => $counts,
        ];
    }

    public function searchPosts(
        string $query,
        ?string $createdAfter = null,
        ?string $createdBefore = null,
        ?string $updatedAfter = null,
        ?string $updatedBefore = null,
        ?int $limit = null
    ): SupportCollection {
        return Post::search($query)
            ->when($limit, fn ($search) => $search->take($limit * 3))
            ->get()
            ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, fn (Collection $collection): Collection => $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore))
            ->when($limit, fn ($collection) => $collection->take($limit))
            ->map(fn (Post $post): array => [
                'id' => $post->id,
                'type' => 'post',
                'title' => $post->title,
                'excerpt' => $post->excerpt ?: Str::of($post->content)->stripTags()->limit()->toString(),
                'url' => $post->url,
                'post_type' => $post->type->value,
                'author_name' => $post->author->name,
                'created_at' => $post->created_at->toISOString(),
                'updated_at' => $post->updated_at->toISOString(),
            ]);
    }

    public function searchPolicies(
        string $query,
        ?string $createdAfter = null,
        ?string $createdBefore = null,
        ?string $updatedAfter = null,
        ?string $updatedBefore = null,
        ?int $limit = null
    ): SupportCollection {
        return Policy::search($query)
            ->when($limit, fn ($search) => $search->take($limit * 3))
            ->get()
            ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, fn (Collection $collection): Collection => $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore))
            ->when($limit, fn ($collection) => $collection->take($limit))
            ->map(fn (Policy $policy): array => [
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
            ]);
    }

    public function searchProducts(
        string $query,
        ?string $createdAfter = null,
        ?string $createdBefore = null,
        ?string $updatedAfter = null,
        ?string $updatedBefore = null,
        ?int $limit = null
    ): SupportCollection {
        return Product::search($query)
            ->when($limit, fn ($search) => $search->take($limit * 3))
            ->get()
            ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, fn (Collection $collection): Collection => $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore))
            ->when($limit, fn ($collection) => $collection->take($limit))
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'type' => 'product',
                'title' => $product->name,
                'description' => $product->description,
                'url' => route('store.products.show', $product->slug),
                'price' => $product->defaultPrice?->amount,
                'category_name' => $product->categories->first()?->name,
                'created_at' => $product->created_at->toISOString(),
                'updated_at' => $product->updated_at->toISOString(),
            ]);
    }

    public function searchTopics(
        string $query,
        ?string $createdAfter = null,
        ?string $createdBefore = null,
        ?string $updatedAfter = null,
        ?string $updatedBefore = null,
        ?int $limit = null
    ): SupportCollection {
        return Topic::search($query)
            ->when($limit, fn ($search) => $search->take($limit * 3))
            ->get()
            ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, fn (Collection $collection): Collection => $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore))
            ->when($limit, fn ($collection) => $collection->take($limit))
            ->map(fn (Topic $topic): array => [
                'id' => $topic->id,
                'type' => 'topic',
                'title' => $topic->title,
                'description' => $topic->description,
                'url' => route('forums.topics.show', [$topic->forum->slug, $topic->slug]),
                'forum_name' => $topic->forum->name,
                'author_name' => $topic->author->name,
                'created_at' => $topic->created_at->toISOString(),
                'updated_at' => $topic->updated_at->toISOString(),
            ]);
    }

    public function searchUsers(
        string $query,
        ?string $createdAfter = null,
        ?string $createdBefore = null,
        ?string $updatedAfter = null,
        ?string $updatedBefore = null,
        ?int $limit = null
    ): SupportCollection {
        return User::search($query)
            ->get()
            ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, fn (Collection $collection): Collection => $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore))
            ->when($limit, fn ($collection) => $collection->take($limit))
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'type' => 'user',
                'title' => $user->name,
                'description' => $user->groups->pluck('name')->implode(', '),
                'url' => route('users.show', $user->reference_id),
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ]);
    }

    public function applyDateFiltersToCollection(
        Collection $collection,
        ?string $createdAfter,
        ?string $createdBefore,
        ?string $updatedAfter,
        ?string $updatedBefore
    ): Collection {
        return $collection
            ->when($createdAfter, fn ($col) => $col->filter(fn ($item): bool => $item->created_at >= $createdAfter))
            ->when($createdBefore, fn ($col) => $col->filter(fn ($item): bool => $item->created_at <= $createdBefore))
            ->when($updatedAfter, fn ($col) => $col->filter(fn ($item): bool => $item->updated_at >= $updatedAfter))
            ->when($updatedBefore, fn ($col) => $col->filter(fn ($item): bool => $item->updated_at <= $updatedBefore));
    }

    public function sortResults(SupportCollection $results, string $sortBy, string $sortOrder = 'desc'): SupportCollection
    {
        return $results->sort(function (array $a, array $b) use ($sortBy, $sortOrder): int {
            $aValue = match ($sortBy) {
                'created_at' => $a['created_at'] ?? '',
                'updated_at' => $a['updated_at'] ?? '',
                'title' => $a['title'] ?? '',
                default => 0,
            };

            $bValue = match ($sortBy) {
                'created_at' => $b['created_at'] ?? '',
                'updated_at' => $b['updated_at'] ?? '',
                'title' => $b['title'] ?? '',
                default => 0,
            };

            $comparison = $aValue <=> $bValue;

            return $sortOrder === 'asc' ? $comparison : -$comparison;
        })->values();
    }

    public function paginateCollection(SupportCollection $collection, int $perPage, int $page): array
    {
        $total = $collection->count();
        $lastPage = (int) ceil($total / $perPage);
        $page = max(1, min($page, $lastPage ?: 1));

        $offset = ($page - 1) * $perPage;
        $items = $collection->slice($offset, $perPage)->values();

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => route('search'),
        ]);

        return PaginatedData::from($paginator)->toArray();
    }

    public function validateAndNormalizeTypes(mixed $types): array
    {
        if (is_string($types)) {
            $types = explode(',', $types);
        }

        if (! is_array($types)) {
            return ['policy', 'post', 'product', 'topic', 'user'];
        }

        $types = array_filter($types, fn ($type): bool => in_array($type, ['policy', 'post', 'product', 'topic', 'user']));

        if (blank($types)) {
            return ['policy', 'post', 'product', 'topic', 'user'];
        }

        return array_values($types);
    }
}
