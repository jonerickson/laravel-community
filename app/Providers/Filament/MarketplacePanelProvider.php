<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Marketplace\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class MarketplacePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('marketplace')
            ->path('marketplace')
            ->brandLogo(fn () => view('filament.components.logo'))
            ->colors([
                'primary' => Color::Zinc,
            ])
            ->discoverResources(in: app_path('Filament/Marketplace/Resources'), for: 'App\\Filament\\Marketplace\\Resources')
            ->discoverPages(in: app_path('Filament/Marketplace/Pages'), for: 'App\\Filament\\Marketplace\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Marketplace/Widgets'), for: 'App\\Filament\\Marketplace\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->font('Instrument Sans')
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()
            ->darkMode()
            ->theme(asset('css/filament/marketplace/theme.css'))
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): View => view('filament.components.head'),
            );
    }
}
