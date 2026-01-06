<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\PaginatedData;
use App\Data\SearchResultData;
use App\Enums\ProductType;
use App\Models\Policy;
use App\Models\Post;
use App\Models\Product;
use App\Models\Topic;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SearchService
{
    public function search(
        string $query,
        array $types = ['policy', 'post', 'product', 'topic', 'user'],
        ?CarbonInterface $createdAfter = null,
        ?CarbonInterface $createdBefore = null,
        ?CarbonInterface $updatedAfter = null,
        ?CarbonInterface $updatedBefore = null,
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
        ?CarbonInterface $createdAfter = null,
        ?CarbonInterface $createdBefore = null,
        ?CarbonInterface $updatedAfter = null,
        ?CarbonInterface $updatedBefore = null,
        ?int $limit = null
    ): SupportCollection {
        return Post::search($query)
            ->when($limit, fn ($search) => $search->take($limit * 3))
            ->get()
            ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, fn (Collection $collection): Collection => $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore))
            ->when($limit, fn ($collection) => $collection->take($limit))
            ->filter(fn (Post $post) => Gate::check('view', $post))
            ->map(fn (Post $post): SearchResultData => SearchResultData::from([
                'id' => $post->id,
                'type' => 'post',
                'title' => $post->title,
                'excerpt' => $post->excerpt ?: Str::of($post->content)->stripTags()->limit()->toString(),
                'url' => $post->url,
                'post_type' => $post->type->value,
                'author_name' => $post->author->name,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ]));
    }

    public function searchPolicies(
        string $query,
        ?CarbonInterface $createdAfter = null,
        ?CarbonInterface $createdBefore = null,
        ?CarbonInterface $updatedAfter = null,
        ?CarbonInterface $updatedBefore = null,
        ?int $limit = null
    ): SupportCollection {
        return Policy::search($query)
            ->when($limit, fn ($search) => $search->take($limit * 3))
            ->get()
            ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, fn (Collection $collection): Collection => $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore))
            ->when($limit, fn ($collection) => $collection->take($limit))
            ->filter(fn (Policy $policy) => Gate::check('view', $policy))
            ->map(fn (Policy $policy): SearchResultData => SearchResultData::from([
                'id' => $policy->id,
                'type' => 'policy',
                'title' => $policy->title,
                'description' => $policy->content,
                'version' => $policy->version,
                'url' => $policy->url,
                'category_name' => $policy->category->name,
                'author_name' => $policy->author->name,
                'effective_at' => $policy->effective_at,
                'created_at' => $policy->created_at,
                'updated_at' => $policy->updated_at,
            ]));
    }

    public function searchProducts(
        string $query,
        ?CarbonInterface $createdAfter = null,
        ?CarbonInterface $createdBefore = null,
        ?CarbonInterface $updatedAfter = null,
        ?CarbonInterface $updatedBefore = null,
        ?int $limit = null
    ): SupportCollection {
        return Product::search($query)
            ->when($limit, fn ($search) => $search->take($limit * 3))
            ->get()
            ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, fn (Collection $collection): Collection => $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore))
            ->when($limit, fn ($collection) => $collection->take($limit))
            ->filter(fn (Product $product) => Gate::check('view', $product))
            ->map(fn (Product $product): SearchResultData => SearchResultData::from([
                'id' => $product->id,
                'type' => 'product',
                'title' => $product->name,
                'description' => $product->description,
                'url' => $product->type === ProductType::Product
                    ? route('store.products.show', $product->slug)
                    : route('store.subscriptions'),
                'price' => $product->defaultPrice?->amount,
                'category_name' => $product->categories->first()?->name,
            ]));
    }

    public function searchTopics(
        string $query,
        ?CarbonInterface $createdAfter = null,
        ?CarbonInterface $createdBefore = null,
        ?CarbonInterface $updatedAfter = null,
        ?CarbonInterface $updatedBefore = null,
        ?int $limit = null
    ): SupportCollection {
        return Topic::search($query)
            ->when($limit, fn ($search) => $search->take($limit * 3))
            ->get()
            ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, fn (Collection $collection): Collection => $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore))
            ->when($limit, fn ($collection) => $collection->take($limit))
            ->filter(fn (Topic $topic) => Gate::check('view', $topic))
            ->map(fn (Topic $topic): SearchResultData => SearchResultData::from([
                'id' => $topic->id,
                'type' => 'topic',
                'title' => $topic->title,
                'description' => $topic->description,
                'url' => route('forums.topics.show', [$topic->forum->slug, $topic->slug]),
                'forum_name' => $topic->forum->name,
                'author_name' => $topic->author->name,
                'created_at' => $topic->created_at,
                'updated_at' => $topic->updated_at,
            ]));
    }

    public function searchUsers(
        string $query,
        ?CarbonInterface $createdAfter = null,
        ?CarbonInterface $createdBefore = null,
        ?CarbonInterface $updatedAfter = null,
        ?CarbonInterface $updatedBefore = null,
        ?int $limit = null
    ): SupportCollection {
        return User::search($query)
            ->get()
            ->when($createdAfter || $createdBefore || $updatedAfter || $updatedBefore, fn (Collection $collection): Collection => $this->applyDateFiltersToCollection($collection, $createdAfter, $createdBefore, $updatedAfter, $updatedBefore))
            ->when($limit, fn ($collection) => $collection->take($limit))
            ->map(fn (User $user): SearchResultData => SearchResultData::from([
                'id' => $user->id,
                'type' => 'user',
                'title' => $user->name,
                'description' => $user->groups->pluck('name')->implode(', '),
                'url' => route('users.show', $user->reference_id),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]));
    }

    public function applyDateFiltersToCollection(
        Collection $collection,
        ?CarbonInterface $createdAfter,
        ?CarbonInterface $createdBefore,
        ?CarbonInterface $updatedAfter,
        ?CarbonInterface $updatedBefore
    ): Collection {
        return $collection
            ->when($createdAfter, fn ($col) => $col->filter(fn ($item): bool => $item->created_at >= $createdAfter))
            ->when($createdBefore, fn ($col) => $col->filter(fn ($item): bool => $item->created_at <= $createdBefore))
            ->when($updatedAfter, fn ($col) => $col->filter(fn ($item): bool => $item->updated_at >= $updatedAfter))
            ->when($updatedBefore, fn ($col) => $col->filter(fn ($item): bool => $item->updated_at <= $updatedBefore));
    }

    public function sortResults(SupportCollection $results, string $sortBy, string $sortOrder = 'desc'): SupportCollection
    {
        return $results->sort(function (SearchResultData $a, SearchResultData $b) use ($sortBy, $sortOrder): int {
            $aValue = match ($sortBy) {
                'created_at' => $a->createdAt?->timestamp ?? 0,
                'updated_at' => $a->updatedAt?->timestamp ?? 0,
                'title' => $a->title,
                default => 0,
            };

            $bValue = match ($sortBy) {
                'created_at' => $b->createdAt?->timestamp ?? 0,
                'updated_at' => $b->updatedAt?->timestamp ?? 0,
                'title' => $b->title,
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

    public function distributeResults(SupportCollection $results, int $limit): SupportCollection
    {
        $allCollections = collect([
            'posts' => $results->where('type', 'post'),
            'policies' => $results->where('type', 'policy'),
            'products' => $results->where('type', 'product'),
            'topics' => $results->where('type', 'topic'),
            'users' => $results->where('type', 'user'),
        ])->filter(fn ($collection) => $collection->isNotEmpty());

        $totalResults = $results->count();

        if ($totalResults <= $limit) {
            return $results;
        }

        // Proportional Allocation
        // Step 1: Compute proportional share (A_i = L × R_i / ∑R_j)
        $allocations = $allCollections->map(function ($collection) use ($limit, $totalResults): array {
            $count = $collection->count();
            $proportionalShare = ($limit * $count) / $totalResults;

            return [
                'collection' => $collection,
                'count' => $count,
                'proportional_share' => $proportionalShare,
                'floor' => (int) floor($proportionalShare),
                'remainder' => $proportionalShare - floor($proportionalShare),
            ];
        });

        // Step 2: Floor the allocations (a_i = ⌊A_i⌋)
        $allocatedSlots = $allocations->sum('floor');

        // Step 3: Distribute remaining slots using largest remainders
        $remainingSlots = $limit - $allocatedSlots;

        $allocations = $allocations->sortByDesc('remainder')->values();

        // Assign remaining slots to groups with largest remainders
        $finalAllocations = $allocations->map(function (array $allocation, $index) use ($remainingSlots): array {
            $extraSlot = $index < $remainingSlots ? 1 : 0;
            $allocation['final_allocation'] = $allocation['floor'] + $extraSlot;

            return $allocation;
        });

        // Build the distributed results collection
        $distributedResults = collect();
        foreach ($finalAllocations as $allocation) {
            $takeAmount = min($allocation['final_allocation'], $allocation['count']);
            $distributedResults = $distributedResults->concat($allocation['collection']->take($takeAmount));
        }

        return $distributedResults->values();
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
