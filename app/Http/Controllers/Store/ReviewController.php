<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\CommentData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreReviewRequest;
use App\Models\Comment;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class ReviewController extends Controller
{
    use AuthorizesRequests;

    public function index(Product $subscription): Response
    {
        $this->authorize('viewAny', Comment::class);

        $reviews = CommentData::collect($subscription
            ->reviews()
            ->with('author.groups')
            ->with(['replies' => function (Comment|HasMany $query): void {
                $query->approved()->with(['author.groups'])->oldest();
            }])
            ->approved()
            ->latest()
            ->get()
            ->filter(fn (Comment $comment) => Gate::check('view', $comment))
            ->values()
            ->all(), PaginatedDataCollection::class);

        return Inertia::render('store/reviews', [
            'subscription' => ProductData::from($subscription->load('prices', 'categories')),
            'reviews' => Inertia::scroll(fn () => $reviews->items()),
        ]);
    }

    public function store(StoreReviewRequest $request, Product $subscription): RedirectResponse
    {
        $this->authorize('create', Comment::class);

        $subscription->comments()->create([
            'content' => $request->validated('content'),
            'rating' => $request->validated('rating'),
        ]);

        return back()->with('message', 'Your review has been submitted successfully.');
    }
}
