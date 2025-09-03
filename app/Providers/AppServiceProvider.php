<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Providers\Social\DiscordProvider;
use App\Providers\Social\RobloxProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
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
    }
}
