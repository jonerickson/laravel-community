<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\Role;
use App\Filament\Admin\Resources\Comments\Widgets\CommentModerationTable;
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
use App\Filament\Admin\Resources\SupportTickets\Widgets\SupportTicketStatsOverview;
use App\Filament\Admin\Resources\SupportTickets\Widgets\UnassignedTicketsTable;
use App\Filament\Admin\Resources\Users\Widgets\RegistrationsTable;
use App\Filament\Admin\Resources\Users\Widgets\UsersAnalyticsChart;
use App\Filament\Admin\Resources\Users\Widgets\UserStatsOverview;
use App\Models\Comment;
use App\Models\Post;
use App\Models\SupportTicket;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Override;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    protected ?string $heading = 'Administration';

    protected static ?int $navigationSort = -1;

    public function getDashboardWidgets(): array
    {
        return [
            RevenueStatsOverview::make(),
            UserStatsOverview::make(),
            UsersAnalyticsChart::make(),
            RegistrationsTable::make(),
        ];
    }

    public function getCommentWidgets(): array
    {
        return [
            CommentModerationTable::make(),
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

    public function getSupportWidgets(): array
    {
        return [
            SupportTicketStatsOverview::make(),
            UnassignedTicketsTable::make(),
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
                Tabs\Tab::make('Comments')
                    ->icon(Heroicon::OutlinedMegaphone)
                    ->visible(fn () => Auth::user()->hasAnyRole(Role::Administrator))
                    ->badge((string) Comment::query()->unapproved()->count())
                    ->badgeColor('warning')
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema($this->getWidgetsSchemaComponents($this->getCommentWidgets())),
                    ]),
                Tabs\Tab::make('Forums')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->visible(fn () => Auth::user()->hasAnyRole(Role::Administrator))
                    ->badge((string) Post::query()->needingModeration()->count())
                    ->badgeColor('warning')
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema($this->getWidgetsSchemaComponents($this->getForumWidgets())),
                    ]),
                Tabs\Tab::make('Store')
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->visible(fn () => Auth::user()->hasAnyRole(Role::Administrator, Role::SupportAgent))
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema($this->getWidgetsSchemaComponents($this->getStoreWidgets())),
                    ]),
                Tabs\Tab::make('Subscriptions')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn () => Auth::user()->hasAnyRole(Role::Administrator, Role::SupportAgent))
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema($this->getWidgetsSchemaComponents($this->getSubscriptionWidgets())),
                    ]),
                Tabs\Tab::make('Support')
                    ->icon(Heroicon::OutlinedLifebuoy)
                    ->visible(fn () => Auth::user()->hasAnyRole(Role::Administrator, Role::SupportAgent))
                    ->badge((string) SupportTicket::query()->unassigned()->active()->count())
                    ->badgeColor('warning')
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema($this->getWidgetsSchemaComponents($this->getSupportWidgets())),
                    ]),
            ]);
    }

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        $name = config('app.name');

        return sprintf('Welcome to the %s Admin Control Panel. From here you can manage your entire application and perform essential administrative functions.', $name);
    }
}
