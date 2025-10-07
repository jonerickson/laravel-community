<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\Widgets;

use App\Models\Post;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Override;

class PostStatsOverview extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        $totalPosts = Post::count();
        $postsToday = Post::whereDate('created_at', today())->count();
        $pendingPosts = Post::query()->unpublished()->count();

        $userCount = User::count() ?: 1;
        $avgPostsPerUser = round($totalPosts / $userCount, 2);

        return [
            Stat::make('Total Posts', Number::format($totalPosts))
                ->description('All blog + forum posts')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('primary'),

            Stat::make('Posts Today', Number::format($postsToday))
                ->description('Posted in last 24h')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('success'),

            Stat::make('Pending Posts', Number::format($pendingPosts))
                ->description('Awaiting moderation')
                ->icon(Heroicon::OutlinedClock)
                ->color($pendingPosts > 0 ? 'warning' : 'success'),

            Stat::make('Avg. Posts/User', Number::format($avgPostsPerUser))
                ->description('Posts per registered user')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('gray'),
        ];
    }
}
