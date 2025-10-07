<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\Widgets;

use App\Enums\ReportStatus;
use App\Models\Post;
use App\Models\Report;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Override;

class ModerationStatsOverview extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        $unpublishedPosts = Post::query()->published()->count();
        $pendingReports = Report::where('status', ReportStatus::Pending)->count();
        $postsWithReports = Post::whereHas('pendingReports')->count();
        $reportsToday = Report::whereDate('created_at', today())->count();

        return [
            Stat::make('Unpublished Posts', Number::format($unpublishedPosts))
                ->description('Awaiting publication')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color($unpublishedPosts > 0 ? 'warning' : 'success'),

            Stat::make('Pending Reports', Number::format($pendingReports))
                ->description('Need review')
                ->icon(Heroicon::OutlinedFlag)
                ->color($pendingReports > 0 ? 'danger' : 'success'),

            Stat::make('Reported Posts', Number::format($postsWithReports))
                ->description('Posts with pending reports')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($postsWithReports > 0 ? 'danger' : 'success'),

            Stat::make('Reports Today', Number::format($reportsToday))
                ->description('New reports today')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('info'),
        ];
    }
}
