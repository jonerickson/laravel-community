<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Permission;
use App\Models\Subscription;
use App\Models\User;
use App\Providers\Social\DiscordProvider;
use App\Providers\Social\RobloxProvider;
use App\Services\PermissionService;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Cashier\Cashier;
use Laravel\Passport\Passport;
use Laravel\Socialite\Facades\Socialite;
use Override;

class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        Cashier::ignoreRoutes();
        Passport::ignoreRoutes();
    }

    public function boot(): void
    {
        Cashier::calculateTaxes();
        Cashier::useCustomerModel(User::class);
        Cashier::useSubscriptionModel(Subscription::class);

        Context::add('request_id', Str::uuid()->toString());

        DB::prohibitDestructiveCommands(App::isProduction());

        FilamentColor::register([
            'primary' => Color::Zinc,
        ]);

        Gate::before(function (?User $user = null) {
            if ($user?->hasRole('super-admin') === true) {
                return true;
            }
        });

        if (Schema::hasTable('permissions')) {
            Permission::all()->each(function (Permission $permission): void {
                Gate::define($permission->name, fn (?User $user = null): bool => PermissionService::hasPermissionTo($permission->name, $user));
            });
        }

        Model::automaticallyEagerLoadRelationships();
        Model::shouldBeStrict();

        Password::defaults(fn () => Password::min(8)
            ->when($this->app->environment(['staging', 'production']), function (Password $password): void {
                $password
                    ->letters()
                    ->symbols()
                    ->numbers()
                    ->mixedCase()
                    ->uncompromised();
            }));

        RateLimiter::for('login', fn (\Illuminate\Http\Request $request): array => [
            Limit::perMinute(5)->by($request->fingerprintId() ?: $request->ip()),
            Limit::perHour(30)->by($request->fingerprintId() ?: $request->ip()),
        ]);

        RateLimiter::for('register', fn (\Illuminate\Http\Request $request): array => [
            Limit::perMinute(2)->by($request->fingerprintId() ?: $request->ip()),
            Limit::perHour(5)->by($request->fingerprintId() ?: $request->ip()),
        ]);

        Socialite::extend('discord', fn () => Socialite::buildProvider(
            provider: DiscordProvider::class,
            config: config('services.discord')
        ));

        Socialite::extend('roblox', fn () => Socialite::buildProvider(
            provider: RobloxProvider::class,
            config: config('services.roblox')
        ));

        Table::configureUsing(function (Table $table): void {
            $table->emptyStateDescription('There are no items to view.');
        });

        Passport::authorizationView(
            fn ($parameters) => Inertia::render('oauth/authorize', [
                'request' => $parameters['request'],
                'authToken' => $parameters['authToken'],
                'client' => $parameters['client'],
                'user' => $parameters['user'],
                'scopes' => $parameters['scopes'],
            ])
        );

        Request::macro('fingerprintId', function (): ?string {
            /** @var \Illuminate\Http\Request $request */
            $request = app('request');

            return $request->header('X-Fingerprint-ID')
                ?? $request->cookie('fingerprint_id');
        });
    }
}
