<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Role;
use App\Managers\ExpressionLanguageManager;
use App\Models\Blacklist;
use App\Models\Fingerprint;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use App\Providers\Social\DiscordProvider;
use App\Providers\Social\RobloxProvider;
use App\Services\PermissionService;
use App\Support\Passport\IdTokenResponse;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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

        $this->app->singleton('expression-language', fn (): ExpressionLanguageManager => new ExpressionLanguageManager);
    }

    public function boot(): void
    {
        Cashier::calculateTaxes();
        Cashier::useCustomerModel(User::class);
        Cashier::useSubscriptionModel(Subscription::class);
        Cashier::keepPastDueSubscriptionsActive();

        Context::add('request_id', Str::uuid()->toString());

        DB::prohibitDestructiveCommands(App::isProduction() && ! App::runningConsoleCommand('app:install'));

        FilamentColor::register([
            'primary' => Color::Zinc,
        ]);

        Gate::before(function (?User $user, $abilitiy, $models) {
            if ($user?->hasRole(Role::Administrator) === true) {
                return true;
            }

            if (Filament::getCurrentPanel() && $user?->hasRole(Role::SupportAgent)) {
                if ($abilitiy === 'delete') {
                    return false;
                }

                $approvedResources = [
                    Blacklist::class,
                    Fingerprint::class,
                    KnowledgeBaseArticle::class,
                    KnowledgeBaseCategory::class,
                    Order::class,
                    SupportTicket::class,
                    SupportTicketCategory::class,
                    User::class,
                ];

                return Collection::make($models)->some(fn ($modelClassOrInstance): bool => in_array($modelClassOrInstance, $approvedResources)
                    || Collection::make($approvedResources)->some(fn ($approvedResource): bool => $modelClassOrInstance instanceof $approvedResource));
            }
        });

        if (rescue(fn () => DB::connection()->getPdo(), report: false) && Schema::hasTable('permissions')) {
            Permission::all()->each(function (Permission $permission): void {
                Gate::define($permission->name, fn (?User $user = null): bool => PermissionService::hasPermissionTo($permission->name, $user));
            });
        }

        Model::automaticallyEagerLoadRelationships();
        Model::shouldBeStrict(! App::isProduction());

        Passport::useAuthorizationServerResponseType(app(IdTokenResponse::class));
        Passport::tokensCan([
            'openid' => 'Can perform single sign-on',
            'profile' => 'Can access your user profile',
            'email' => 'Can access your email address',
        ]);

        Password::defaults(fn () => Password::min(8)
            ->when($this->app->environment(['staging', 'production']), function (Password $password): void {
                $password
                    ->letters()
                    ->symbols()
                    ->numbers()
                    ->mixedCase()
                    ->uncompromised();
            }));

        RateLimiter::for('comment', fn (Request $request): array => [
            Limit::perMinute(5)->by($request->fingerprintId() ?: $request->ip()),
            Limit::perHour(20)->by($request->fingerprintId() ?: $request->ip()),
        ]);

        RateLimiter::for('login', fn (Request $request): array => [
            Limit::perMinute(5)->by($request->fingerprintId() ?: $request->ip()),
            Limit::perHour(30)->by($request->fingerprintId() ?: $request->ip()),
        ]);

        RateLimiter::for('post', fn (Request $request): array => [
            Limit::perMinute(10)->by($request->fingerprintId() ?: $request->ip()),
            Limit::perHour(25)->by($request->fingerprintId() ?: $request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request): array => [
            Limit::perMinute(5)->by($request->fingerprintId() ?: $request->ip()),
            Limit::perHour(10)->by($request->fingerprintId() ?: $request->ip()),
        ]);

        RateLimiter::for('report', fn (Request $request): array => [
            Limit::perMinute(5)->by($request->fingerprintId() ?: $request->ip()),
            Limit::perHour(10)->by($request->fingerprintId() ?: $request->ip()),
        ]);

        RateLimiter::for('support-ticket', fn (Request $request): array => [
            Limit::perMinute(3)->by($request->fingerprintId() ?: $request->ip()),
            Limit::perHour(10)->by($request->fingerprintId() ?: $request->ip()),
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

        URL::forceHttps(App::isProduction());

        Vite::useCspNonce();
    }
}
