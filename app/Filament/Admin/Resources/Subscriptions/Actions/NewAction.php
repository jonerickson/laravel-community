<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Actions;

use App\Enums\OrderStatus;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Override;

class NewAction extends Action
{
    protected User|Closure|null $user = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('New subscription');
        $this->color('primary');
        $this->successNotificationTitle('The subscription has been successfully started.');
        $this->modalHeading('New Subscription');
        $this->modalDescription('Enter the required information to start the user on a new subscription.');
        $this->modalSubmitActionLabel('Start');

        $this->hidden(function () {
            $paymentManager = app(PaymentManager::class);

            return $paymentManager->currentSubscription(
                user: $this->user,
            );
        });

        $this->schema([
            Select::make('product_id')
                ->label('Product')
                ->required()
                ->preload()
                ->searchable()
                ->live(onBlur: true)
                ->options(Product::query()->subscriptions()->pluck('name', 'id')),
            Select::make('price_id')
                ->label('Price')
                ->required()
                ->preload()
                ->searchable()
                ->options(fn (Get $get) => Price::query()->active()->whereRelation('product', fn (Builder $query) => $query->whereKey($get('product_id')))->pluck('name', 'id')),
        ]);

        $this->action(function (NewAction $action, array $data): void {
            $order = Order::create([
                'status' => OrderStatus::Pending,
                'user_id' => $this->getUser()->getKey(),
            ]);

            $order->items()->create([
                'product_id' => $data['product_id'],
                'price_id' => $data['price_id'],
                'quantity' => 1,
            ]);

            $paymentManager = app(PaymentManager::class);
            $paymentManager->startSubscription(
                order: $order,
                firstParty: false
            );

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'new';
    }

    public function getUser(): mixed
    {
        return $this->evaluate($this->user);
    }

    public function user(User|Closure|null $user): static
    {
        $this->user = $user;

        return $this;
    }
}
