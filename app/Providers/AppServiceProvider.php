<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\PriceCreated;
use App\Events\PriceDeleted;
use App\Events\PriceUpdated;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Events\Stripe\CustomerDeleted;
use App\Events\Stripe\CustomerUpdated;
use App\Events\Stripe\PaymentActionRequired;
use App\Events\Stripe\PaymentSucceeded;
use App\Events\Stripe\SubscriptionCreated;
use App\Events\Stripe\SubscriptionDeleted;
use App\Events\Stripe\SubscriptionUpdated;
use App\Listeners\Stripe\ProcessWebhook;
use App\Listeners\SyncPriceWithPaymentProvider;
use App\Listeners\SyncProductWithPaymentProvider;
use App\Models\Permission;
use App\Models\User;
use App\Providers\Social\DiscordProvider;
use App\Providers\Social\RobloxProvider;
use App\Services\PermissionService;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

        Gate::before(function (?User $user = null): void {
            if ($user?->hasRole('super-admin') === true) {
                // return true;
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

        Event::listen(ProductCreated::class, SyncProductWithPaymentProvider::class);
        Event::listen(ProductUpdated::class, SyncProductWithPaymentProvider::class);
        Event::listen(ProductDeleted::class, SyncProductWithPaymentProvider::class);

        Event::listen(PriceCreated::class, SyncPriceWithPaymentProvider::class);
        Event::listen(PriceUpdated::class, SyncPriceWithPaymentProvider::class);
        Event::listen(PriceDeleted::class, SyncPriceWithPaymentProvider::class);

        Event::listen(CustomerDeleted::class, ProcessWebhook::class);
        Event::listen(CustomerUpdated::class, ProcessWebhook::class);
        Event::listen(PaymentActionRequired::class, ProcessWebhook::class);
        Event::listen(PaymentSucceeded::class, ProcessWebhook::class);
        Event::listen(SubscriptionCreated::class, ProcessWebhook::class);
        Event::listen(SubscriptionUpdated::class, ProcessWebhook::class);
        Event::listen(SubscriptionDeleted::class, ProcessWebhook::class);

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
