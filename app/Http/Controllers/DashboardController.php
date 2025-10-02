<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\AnnouncementData;
use App\Data\PostData;
use App\Data\ProductData;
use App\Data\SupportTicketData;
use App\Data\TopicData;
use App\Models\Announcement;
use App\Models\Post;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'newestProduct' => $this->getNewestProduct(),
            'popularProduct' => $this->getPopularProduct(),
            'featuredProduct' => $this->getFeaturedProduct(),
            'announcements' => Inertia::defer(fn (): Collection => $this->getAnnouncements()),
            'supportTickets' => Inertia::defer(fn (): Collection => $this->getSupportTickets()),
            'trendingTopics' => Inertia::defer(fn (): Collection => $this->getTrendingTopics()),
            'latestBlogPosts' => Inertia::defer(fn (): Collection => $this->getLatestBlogPosts()),
        ]);
    }

    private function getAnnouncements(): Collection
    {
        return AnnouncementData::collect(Announcement::query()
            ->with('author')
            ->with('reads')
            ->current()
            ->unread()
            ->latest()
            ->get());
    }

    private function getSupportTickets(): Collection
    {
        return SupportTicketData::collect(SupportTicket::query()
            ->with('category')
            ->with('author')
            ->whereBelongsTo(Auth::user(), 'author')
            ->active()
            ->latest()
            ->limit(5)
            ->get()
            ->filter(fn (SupportTicket $ticket) => Gate::check('view', $ticket)));
    }

    private function getTrendingTopics(): Collection
    {
        return TopicData::collect(Topic::trending(5)
            ->with('forum')
            ->with('author')
            ->with('lastPost.author')
            ->get()
            ->filter(fn (Topic $topic) => Gate::check('view', [$topic, $topic->forum])));
    }

    private function getLatestBlogPosts(): Collection
    {
        return PostData::collect(Post::query()
            ->blog()
            ->published()
            ->with('author')
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->filter(fn (Post $post) => Gate::check('view', $post)));
    }

    private function getNewestProduct(): ?ProductData
    {
        $product = Product::query()
            ->with('defaultPrice')
            ->with('categories')
            ->with(['prices' => function (HasMany $query): void {
                $query->active();
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
            ->with('defaultPrice')
            ->with('categories')
            ->with(['prices' => function (HasMany $query): void {
                $query->active();
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
            ->featured()
            ->with('defaultPrice')
            ->with('categories')
            ->with(['prices' => function (HasMany $query): void {
                $query->active();
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
