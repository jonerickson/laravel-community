<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\SupportTicket;
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
        ]);
    }

    private function getAnnouncements(): Collection
    {
        return Announcement::query()
            ->current()
            ->unread()
            ->latest()
            ->get();
    }

    private function getSupportTickets(): Collection
    {
        return SupportTicket::with(['category', 'author'])
            ->whereBelongsTo(Auth::user(), 'author')
            ->active()
            ->latest()
            ->limit(5)
            ->get();
    }
}
