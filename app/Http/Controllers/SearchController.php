<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService
    ) {
        //
    }

    public function __invoke(Request $request): Response
    {
        $query = $request->get('q') ?? '';
        $types = $this->searchService->validateAndNormalizeTypes($request->get('types', ['policy', 'post', 'product', 'topic', 'user']));
        $sortBy = $request->get('sort_by', 'relevance');
        $sortOrder = $request->get('sort_order', 'desc');
        $perPage = min($request->integer('per_page', 20), 50);

        $createdAfter = $request->date('created_after');
        $createdBefore = $request->date('created_before');
        $updatedAfter = $request->date('updated_after');
        $updatedBefore = $request->date('updated_before');

        $searchResults = $this->searchService->search(
            query: $query,
            types: $types,
            createdAfter: $createdAfter,
            createdBefore: $createdBefore,
            updatedAfter: $updatedAfter,
            updatedBefore: $updatedBefore
        );

        $results = $this->searchService->sortResults($searchResults['results'], $sortBy, $sortOrder);
        $paginatedResults = $this->searchService->paginateCollection($results, $perPage, $request->integer('page', 1));

        return Inertia::render('search', [
            'results' => $paginatedResults,
            'query' => $query,
            'filters' => [
                'types' => $types,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
                'created_after' => $createdAfter,
                'created_before' => $createdBefore,
                'updated_after' => $updatedAfter,
                'updated_before' => $updatedBefore,
            ],
            'counts' => $searchResults['counts'],
        ]);
    }
}
