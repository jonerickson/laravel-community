<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Widgets;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Override;

class SupportTicketStatsOverview extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        $totalTickets = $this->calculateTotalTickets();
        $openTickets = $this->calculateOpenTickets();
        $assignedToMe = $this->calculateAssignedToMe();
        $avgResponseTime = $this->calculateAverageResponseTime();

        return [
            Stat::make('Total Tickets', Number::format($totalTickets))
                ->description('All support tickets')
                ->icon(Heroicon::OutlinedLifebuoy)
                ->color('primary'),

            Stat::make('Open Tickets', Number::format($openTickets))
                ->description('Currently open')
                ->icon(Heroicon::OutlinedClock)
                ->color($openTickets > 10 ? 'warning' : 'success'),

            Stat::make('Assigned to Me', Number::format($assignedToMe))
                ->description("Tickets I'm handling")
                ->icon(Heroicon::OutlinedUser)
                ->color('info'),

            Stat::make('Avg. Response Time', $avgResponseTime)
                ->description('Time to first reply')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('gray'),
        ];
    }

    protected function calculateTotalTickets(): int
    {
        return SupportTicket::count();
    }

    protected function calculateOpenTickets(): int
    {
        return SupportTicket::where('status', SupportTicketStatus::Open)->count();
    }

    protected function calculateAssignedToMe(): int
    {
        return SupportTicket::where('assigned_to', Auth::id())
            ->whereIn('status', [SupportTicketStatus::Open, SupportTicketStatus::InProgress, SupportTicketStatus::New])
            ->count();
    }

    protected function calculateAverageResponseTime(): string
    {
        $tickets = SupportTicket::whereHas('comments')
            ->with(['comments' => function ($query): void {
                $query->orderBy('created_at', 'asc')->limit(1);
            }])
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->get();

        if ($tickets->isEmpty()) {
            return 'N/A';
        }

        $totalMinutes = 0;
        $validTickets = 0;

        foreach ($tickets as $ticket) {
            if ($ticket->comments->isNotEmpty()) {
                $firstComment = $ticket->comments->first();
                $diffInMinutes = $ticket->created_at->diffInMinutes($firstComment->created_at);
                $totalMinutes += $diffInMinutes;
                $validTickets++;
            }
        }

        if ($validTickets === 0) {
            return 'N/A';
        }

        $avgMinutes = $totalMinutes / $validTickets;

        if ($avgMinutes < 60) {
            return round($avgMinutes).' min';
        }

        $avgHours = $avgMinutes / 60;

        if ($avgHours < 24) {
            return round($avgHours, 1).' hrs';
        }

        $avgDays = $avgHours / 24;

        return round($avgDays, 1).' days';
    }
}
