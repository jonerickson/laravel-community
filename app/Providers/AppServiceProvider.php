<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use App\Providers\Social\DiscordProvider;
use App\Providers\Social\RobloxProvider;
use App\Services\PermissionService;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Cashier\Cashier;
use Laravel\Passport\Passport;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Cashier::calculateTaxes();
        Cashier::useCustomerModel(User::class);

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

        Permission::all()->each(function (Permission $permission): void {
            Gate::define($permission->name, fn (?User $user = null): bool => PermissionService::hasPermissionTo($permission->name, $user));
        });

        Model::automaticallyEagerLoadRelationships();
        Model::shouldBeStrict();

        Socialite::extend('discord', fn () => Socialite::buildProvider(
            provider: DiscordProvider::class,
            config: config('services.discord')
        ));

        Socialite::extend('roblox', fn () => Socialite::buildProvider(
            provider: RobloxProvider::class,
            config: config('services.roblox')
        ));

        Table::configureUsing(function (Table $table) {
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
    }
}
