<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentBehavior;
use App\Enums\ProductType;
use App\Enums\ProrationBehavior;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
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
        $this->hidden(fn (): bool => filled($this->user->current_subscription));
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
            DateTimePicker::make('backdate_start_date')
                ->label('Start Date')
                ->helperText('Set a past date to backdate when the subscription started.')
                ->native(false)
                ->seconds(false)
                ->maxDate(now())
                ->before(now()->format('Y-m-d')),
            DateTimePicker::make('first_billing_date')
                ->label('First Billing Date')
                ->helperText("The customer will not be charged until this date. The customer's subscription will be in the trialing state until the first billing date. Leave blank to charge immediately.")
                ->native(false)
                ->seconds(false)
                ->minDate(now())
                ->after(now()->format('Y-m-d')),
            Radio::make('proration_behavior')
                ->label('Proration Behavior')
                ->default(ProrationBehavior::AlwaysInvoice)
                ->options(ProrationBehavior::class),
            Radio::make('payment_behavior')
                ->label('Payment Behavior')
                ->default(PaymentBehavior::ErrorIfIncomplete)
                ->options(PaymentBehavior::class),
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
                firstParty: false,
                prorationBehavior: $data['proration_behavior'],
                paymentBehavior: $data['payment_behavior'],
                backdateStartDate: filled($data['backdate_start_date']) ? Carbon::parse($data['backdate_start_date']) : null,
                billingCycleAnchor: filled($data['first_billing_date']) ? Carbon::parse($data['first_billing_date']) : null,
            );

            if ($result) {
                $action->success();

                return;
            }

            if ($paymentManager->lastError !== null) {
                $action->failureNotificationTitle($paymentManager->lastError->message);
            }

            $action->failure();
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
