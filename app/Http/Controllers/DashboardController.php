<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\PostData;
use App\Data\ProductData;
use App\Data\SupportTicketData;
use App\Data\TopicData;
use App\Models\Post;
use App\Models\Price;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
    ) {
        //
    }

    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'newestProduct' => Inertia::defer(fn (): ?ProductData => $this->getNewestProduct(), 'products'),
            'popularProduct' => Inertia::defer(fn (): ?ProductData => $this->getPopularProduct(), 'products'),
            'featuredProduct' => Inertia::defer(fn (): ?ProductData => $this->getFeaturedProduct(), 'products'),
            'supportTickets' => Inertia::defer(fn (): Collection => $this->getSupportTickets(), 'tickets'),
            'trendingTopics' => Inertia::defer(fn (): Collection => $this->getTrendingTopics(), 'topics'),
            'latestBlogPosts' => Inertia::defer(fn (): Collection => $this->getLatestBlogPosts(), 'posts'),
        ]);
    }

    private function getSupportTickets(): Collection
    {
        return SupportTicketData::collect(SupportTicket::query()
            ->with(['category', 'author.groups'])
            ->whereBelongsTo($this->user, 'author')
            ->active()
            ->latest()
            ->limit(5)
            ->get()
            ->filter(fn (SupportTicket $ticket) => Gate::check('view', $ticket)));
    }

    private function getTrendingTopics(): Collection
    {
        return TopicData::collect(Topic::trending(5)
            ->with(['forum', 'author.groups', 'lastPost.author.groups'])
            ->get()
            ->filter(fn (Topic $topic) => Gate::check('view', $topic)));
    }

    private function getLatestBlogPosts(): Collection
    {
        return PostData::collect(Post::query()
            ->blog()
            ->published()
            ->with(['author.groups'])
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->filter(fn (Post $post) => Gate::check('view', $post)));
    }

    private function getNewestProduct(): ?ProductData
    {
        $product = Product::query()
            ->products()
            ->approved()
            ->visible()
            ->active()
            ->with(['defaultPrice', 'categories', 'inventoryItem'])
            ->with(['prices' => function (Price|HasMany $query): void {
                $query->active()->visible();
            }])
            ->latest()
            ->get()
            ->filter(fn (Product $product) => Gate::check('view', $product))
            ->first();

        if (! $product) {
            return null;
        }

        return ProductData::from($product);
    }

    private function getPopularProduct(): ?ProductData
    {
        $product = Product::query()
            ->products()
            ->approved()
            ->visible()
            ->active()
            ->with(['defaultPrice', 'categories', 'approvedReviews', 'inventoryItem'])
            ->with(['prices' => function (Price|HasMany $query): void {
                $query->active()->visible();
            }])
            ->trending()
            ->get()
            ->filter(fn (Product $product) => Gate::check('view', $product))
            ->first();

        if (! $product) {
            return null;
        }

        return ProductData::from($product);
    }

    private function getFeaturedProduct(): ?ProductData
    {
        $product = Product::query()
            ->products()
            ->approved()
            ->visible()
            ->active()
            ->featured()
            ->with(['defaultPrice', 'categories', 'inventoryItem'])
            ->with(['prices' => function (Price|HasMany $query): void {
                $query->active()->visible();
            }])
            ->inRandomOrder()
            ->get()
            ->filter(fn (Product $product) => Gate::check('view', $product))
            ->first();

        if (! $product) {
            return null;
        }

        return ProductData::from($product);
    }
}
