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
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;

class SwapAction extends Action
{
    protected User|Closure|null $user = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Swap subscription');
        $this->color('primary');
        $this->successNotificationTitle('The subscription has been successfully started.');
        $this->modalHeading('Swap Subscription');
        $this->modalDescription('Select the new product to swap the current subscription to. All charges will be prorated.');
        $this->modalSubmitActionLabel('Swap');

        $this->visible(function () {
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
                ->options(fn (Get $get) => Price::query()->active()->whereRelation('product', fn (Builder $query) => $query->whereKey($get('product_id')))->get()->mapWithKeys(fn (Price $price) => [$price->id => $price->getLabel()])),
            Checkbox::make('charge_now')
                ->default(true)
                ->inline()
                ->helperText('Charge the customer immediately. If left unchecked, the user will be sent an invoice to pay at a later time.'),
        ]);

        $this->action(function (SwapAction $action, array $data) {
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
                chargeNow: $data['charge_now'],
                firstParty: false,
            );

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'swap';
    }

    public function getUser()
    {
        return $this->evaluate($this->user);
    }

    public function user(User|Closure|null $user): static
    {
        $this->user = $user;

        return $this;
    }
}
