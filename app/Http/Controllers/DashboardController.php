<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\AnnouncementData;
use App\Data\ProductData;
use App\Models\Announcement;
use App\Models\Post;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
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
            'announcements' => Inertia::defer(fn () => $this->getAnnouncements()),
            'supportTickets' => Inertia::defer(fn (): Collection => $this->getSupportTickets()),
            'trendingTopics' => Inertia::defer(fn (): Collection => $this->getTrendingTopics()),
            'latestBlogPosts' => Inertia::defer(fn (): Collection => $this->getLatestBlogPosts()),
        ]);
    }

    private function getAnnouncements()
    {
        $announcements = Announcement::query()
            ->with('author')
            ->with('reads')
            ->current()
            ->unread()
            ->latest()
            ->get();

        return AnnouncementData::collect($announcements);
    }

    private function getSupportTickets(): Collection
    {
        return SupportTicket::query()
            ->with('category')
            ->with('author')
            ->whereBelongsTo(Auth::user(), 'author')
            ->active()
            ->latest()
            ->limit(5)
            ->get();
    }

    private function getTrendingTopics(): Collection
    {
        return Topic::trending(5)
            ->with('forum')
            ->with('author')
            ->with('lastPost.author')
            ->get();
    }

    private function getLatestBlogPosts(): Collection
    {
        return Post::query()
            ->blog()
            ->published()
            ->with('author')
            ->latest('published_at')
            ->limit(3)
            ->get();
    }

    private function getNewestProduct(): ?ProductData
    {
        return ProductData::from(Product::query()
            ->with('defaultPrice')
            ->with('prices')
            ->with('categories')
            ->latest()
            ->first());
    }

    private function getPopularProduct(): ?ProductData
    {
        return ProductData::from(Product::query()
            ->with('defaultPrice')
            ->with('prices')
            ->with('categories')
            ->trending()
            ->first());
    }

    private function getFeaturedProduct(): ?ProductData
    {
        return ProductData::from(Product::query()
            ->featured()
            ->with('defaultPrice')
            ->with('prices')
            ->with('categories')
            ->inRandomOrder()
            ->first());
    }
}
