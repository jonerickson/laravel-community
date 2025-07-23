<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Providers\Social\DiscordProvider;
use App\Providers\Social\RobloxProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
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

        $this->app->get('config')->set(
            key: 'spark.billables.user.plans',
            value: Product::query()
                ->subscriptions()
                ->with(['prices' => fn ($query) => $query->active()->withStripePrice()])
                ->get()
                ->flatMap(function (Product $product) {
                    return $product->prices()->active()->get()->map(function (ProductPrice $price) use ($product) {
                        return [
                            'name' => $product->name,
                            'short_description' => $product->description,
                            'monthly_id' => $price->stripe_price_id,
                            'yearly_id' => $price->interval === 'year' ? $price->stripe_price_id : null,
                            'features' => [
                                'Feature 1',
                                'Feature 2',
                                'Feature 3',
                            ],
                        ];
                    });
                })
                ->toArray()
        );
    }
}
