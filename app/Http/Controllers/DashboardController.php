<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Post;
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
            'announcements' => Inertia::defer(fn (): Collection => $this->getAnnouncements()),
            'supportTickets' => Inertia::defer(fn (): Collection => $this->getSupportTickets()),
            'trendingTopics' => Inertia::defer(fn (): Collection => $this->getTrendingTopics()),
            'latestBlogPosts' => Inertia::defer(fn (): Collection => $this->getLatestBlogPosts()),
        ]);
    }

    private function getAnnouncements(): Collection
    {
        return Announcement::query()
            ->with('reads')
            ->current()
            ->unread()
            ->latest()
            ->get();
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
}
