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
        $createdAfter = $request->date('created_after');
        $createdBefore = $request->date('created_before');
        $updatedAfter = $request->date('updated_after');
        $updatedBefore = $request->date('updated_before');

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

        $totalResults = $searchResults['results']->count();
        $results = $this->searchService->distributeResults($searchResults['results'], $limit);

        return ApiResource::success(
            resource: $results,
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
