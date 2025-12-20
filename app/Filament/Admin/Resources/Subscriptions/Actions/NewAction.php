<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Actions;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
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
        $this->failureNotificationTitle('The subscription could not be started. Please try again.');
        $this->modalHeading('New subscription');
        $this->modalDescription('Enter the required information to start the user on a new subscription.');
        $this->modalSubmitActionLabel('Start');

        $this->hidden(function () {
            $paymentManager = app(PaymentManager::class);

            return $paymentManager->currentSubscription(
                user: $this->user,
            );
        });

        $this->schema([
            Select::make('price_id')
                ->label('Product')
                ->required()
                ->preload()
                ->searchable()
                ->options(fn (Get $get) => Price::query()
                    ->active()
                    ->with('product')
                    ->whereRelation('product', 'type', ProductType::Subscription)
                    ->whereHas('product', fn (Builder|Product $query) => $query->active())
                    ->get()
                    ->mapWithKeys(fn (Price $price): array => [$price->id => sprintf('%s: %s', $price->product->getLabel(), $price->getLabel())])),
        ]);

        $this->action(function (NewAction $action, array $data): void {
            $order = Order::create([
                'status' => OrderStatus::Pending,
                'user_id' => $this->getUser()->getKey(),
            ]);

            $order->items()->create([
                'price_id' => $data['price_id'],
                'quantity' => 1,
            ]);

            $paymentManager = app(PaymentManager::class);
            $result = $paymentManager->startSubscription(
                order: $order,
                firstParty: false
            );

            if ($result) {
                $action->success();
            } else {
                $action->failure();
            }
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
