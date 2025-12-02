<?php

declare(strict_types=1);

namespace App\Http\Controllers\KnowledgeBase;

use App\Data\KnowledgeBaseArticleData;
use App\Data\KnowledgeBaseCategoryData;
use App\Enums\KnowledgeBaseArticleType;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class KnowledgeBaseController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $query = KnowledgeBaseArticle::query()
            ->with(['category', 'author.groups'])
            ->published()
            ->latest('published_at');

        if ($request->filled('category')) {
            $query->inCategory((int) $request->input('category'));
        }

        if ($request->filled('type')) {
            $type = KnowledgeBaseArticleType::tryFrom($request->input('type'));
            if ($type) {
                $query->ofType($type);
            }
        }

        $articles = KnowledgeBaseArticleData::collect(
            $query->paginate(12),
            PaginatedDataCollection::class
        );

        $categories = KnowledgeBaseCategoryData::collect(
            KnowledgeBaseCategory::query()
                ->active()
                ->ordered()
                ->withCount(['publishedArticles as articles_count'])
                ->get()
        );

        return Inertia::render('knowledge-base/index', [
            'articles' => Inertia::scroll(fn () => $articles->items()),
            'categories' => $categories,
            'filters' => [
                'category' => $request->input('category'),
                'type' => $request->input('type'),
            ],
        ]);
    }

    public function show(KnowledgeBaseArticle $article): Response
    {
        $this->authorize('view', $article);

        $article->load(['category', 'author']);

        $relatedArticles = KnowledgeBaseArticleData::collect(
            KnowledgeBaseArticle::query()
                ->published()
                ->where('id', '!=', $article->id)
                ->when($article->category_id, fn ($query) => $query->where('category_id', $article->category_id))
                ->latest('published_at')
                ->limit(3)
                ->get()
        );

        return Inertia::render('knowledge-base/show', [
            'article' => KnowledgeBaseArticleData::from($article),
            'relatedArticles' => $relatedArticles,
        ]);
    }

    public function search(Request $request): Response
    {
        $query = $request->input('q', '');

        if (blank($query)) {
            return Inertia::render('knowledge-base/search', [
                'results' => [],
                'query' => $query,
            ]);
        }

        $results = KnowledgeBaseArticle::search($query)
            ->query(fn ($builder) => $builder->with(['category', 'author']))
            ->get()
            ->filter(fn (KnowledgeBaseArticle $article) => $article->is_published);

        return Inertia::render('knowledge-base/search', [
            'results' => KnowledgeBaseArticleData::collect($results),
            'query' => $query,
        ]);
    }
}
