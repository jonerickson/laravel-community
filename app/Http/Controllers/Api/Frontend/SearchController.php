<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService
    ) {
        //
    }

    public function __invoke(Request $request): ApiResource
    {
        $query = $request->get('q', '');
        $types = $this->searchService->validateAndNormalizeTypes($request->get('types', ['policy', 'post', 'product', 'topic', 'user']));
        $createdAfter = $request->get('created_after');
        $createdBefore = $request->get('created_before');
        $updatedAfter = $request->get('updated_after');
        $updatedBefore = $request->get('updated_before');

        if (blank($query) || strlen((string) $query) < 2) {
            return ApiResource::success(
                resource: [],
                message: 'Your search query is too short.',
                meta: [
                    'total' => 0,
                    'query' => $query,
                    'types' => $types,
                    'date_filters' => [
                        'created_after' => $createdAfter,
                        'created_before' => $createdBefore,
                        'updated_after' => $updatedAfter,
                        'updated_before' => $updatedBefore,
                    ],
                ]
            );
        }

        $limit = min($request->integer('limit', 10), 50);

        $searchResults = $this->searchService->search(
            query: $query,
            types: $types,
            createdAfter: $createdAfter,
            createdBefore: $createdBefore,
            updatedAfter: $updatedAfter,
            updatedBefore: $updatedBefore,
            limit: $limit
        );

        $allCollections = collect([
            'posts' => $searchResults['results']->where('type', 'post'),
            'policies' => $searchResults['results']->where('type', 'policy'),
            'products' => $searchResults['results']->where('type', 'product'),
            'topics' => $searchResults['results']->where('type', 'topic'),
            'users' => $searchResults['results']->where('type', 'user'),
        ])->filter(fn ($collection) => $collection->isNotEmpty());

        $totalResults = $searchResults['results']->count();

        if ($totalResults <= $limit) {
            $results = $searchResults['results'];
        } else {
            $resultsPerType = max(1, intval($limit / $allCollections->count()));
            $remaining = $limit - ($resultsPerType * $allCollections->count());

            $results = collect();
            foreach ($allCollections as $collection) {
                $takeAmount = $resultsPerType + ($remaining > 0 ? 1 : 0);
                if ($remaining > 0) {
                    $remaining--;
                }
                $results = $results->concat($collection->take($takeAmount));
            }
        }

        return ApiResource::success(
            resource: $results->values(),
            meta: [
                'total' => $totalResults,
                'query' => $query,
                'types' => $types,
                'date_filters' => [
                    'created_after' => $createdAfter,
                    'created_before' => $createdBefore,
                    'updated_after' => $updatedAfter,
                    'updated_before' => $updatedBefore,
                ],
                'counts' => $searchResults['counts'],
            ]);
    }
}
