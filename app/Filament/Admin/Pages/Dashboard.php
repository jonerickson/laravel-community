<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\Orders\Widgets\OrdersAnalyticsChart;
use App\Filament\Admin\Resources\Orders\Widgets\OrderStatsOverview;
use App\Filament\Admin\Resources\Orders\Widgets\RecentOrdersTable;
use App\Filament\Admin\Resources\Orders\Widgets\RecentSubscriptionsTable;
use App\Filament\Admin\Resources\Orders\Widgets\RevenueStatsOverview;
use App\Filament\Admin\Resources\Orders\Widgets\SubscriptionStatsOverview;
use App\Filament\Admin\Resources\Orders\Widgets\TopProductsTable;
use App\Filament\Admin\Resources\Posts\Widgets\ModerationStatsOverview;
use App\Filament\Admin\Resources\Posts\Widgets\PostModerationTable;
use App\Filament\Admin\Resources\Posts\Widgets\PostStatsOverview;
use App\Filament\Admin\Resources\Users\Widgets\RegistrationsTable;
use App\Filament\Admin\Resources\Users\Widgets\UsersAnalyticsChart;
use App\Filament\Admin\Resources\Users\Widgets\UserStatsOverview;
use App\Models\Post;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    protected ?string $heading = 'Administration';

    public function getDashboardWidgets(): array
    {
        return [
            RevenueStatsOverview::make(),
            UserStatsOverview::make(),
            UsersAnalyticsChart::make(),
            RegistrationsTable::make(),
        ];
    }

    public function getForumWidgets(): array
    {
        return [
            PostStatsOverview::make(),
            ModerationStatsOverview::make(),
            PostModerationTable::make(),
        ];
    }

    public function getStoreWidgets(): array
    {
        return [
            OrderStatsOverview::make(),
            OrdersAnalyticsChart::make(),
            RecentOrdersTable::make(),
            TopProductsTable::make(),
        ];
    }

    public function getSubscriptionWidgets(): array
    {
        return [
            SubscriptionStatsOverview::make(),
            RecentSubscriptionsTable::make(),
        ];
    }

    #[Override]
    public function getWidgetsContentComponent(): Component
    {
        return Tabs::make()
            ->contained(false)
            ->persistTabInQueryString()
            ->tabs([
                Tabs\Tab::make('Dashboard')
                    ->icon(Heroicon::OutlinedHome)
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema($this->getWidgetsSchemaComponents($this->getDashboardWidgets())),
                    ]),
                Tabs\Tab::make('Forums')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->badge((string) Post::query()->needingModeration()->count())
                    ->badgeColor('warning')
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema($this->getWidgetsSchemaComponents($this->getForumWidgets())),
                    ]),
                Tabs\Tab::make('Store')
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema($this->getWidgetsSchemaComponents($this->getStoreWidgets())),
                    ]),
                Tabs\Tab::make('Subscriptions')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema($this->getWidgetsSchemaComponents($this->getSubscriptionWidgets())),
                    ]),
            ]);
    }

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        $name = config('app.name');

        return "Welcome to the $name Admin Control Panel. From here you can manage your entire application and perform essential administrative functions.";
    }
}
