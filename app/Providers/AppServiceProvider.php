<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ProductCreated;
use App\Events\ProductDeleting;
use App\Events\ProductPriceCreated;
use App\Events\ProductPriceDeleting;
use App\Events\ProductPriceUpdated;
use App\Events\ProductUpdated;
use App\Listeners\SyncProductPriceWithPaymentProvider;
use App\Listeners\SyncProductWithPaymentProvider;
use App\Models\User;
use App\Providers\Social\DiscordProvider;
use App\Providers\Social\RobloxProvider;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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
        Event::listen(ProductDeleting::class, SyncProductWithPaymentProvider::class);

        Event::listen(ProductPriceCreated::class, SyncProductPriceWithPaymentProvider::class);
        Event::listen(ProductPriceUpdated::class, SyncProductPriceWithPaymentProvider::class);
        Event::listen(ProductPriceDeleting::class, SyncProductPriceWithPaymentProvider::class);

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
