<?php

namespace App\Providers;

use App\Providers\Social\DiscordProvider;
use App\Providers\Social\RobloxProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FilamentColor::register([
            'primary' => Color::Zinc,
        ]);

        Socialite::extend('discord', fn() => Socialite::buildProvider(
            provider: DiscordProvider::class,
            config: config('services.discord')
        ));

        Socialite::extend('roblox', fn() => Socialite::buildProvider(
            provider: RobloxProvider::class,
            config: config('services.roblox')
        ));
    }
}
