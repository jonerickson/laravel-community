<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Actions;

use App\Actions\Payments\SwapSubscriptionAction;
use App\Enums\PaymentBehavior;
use App\Enums\ProductType;
use App\Enums\ProrationBehavior;
use App\Managers\PaymentManager;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Override;

class SwapAction extends Action
{
    protected User|Closure|null $user = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Swap subscription');
        $this->color('primary');
        $this->successNotificationTitle('The subscription has been successfully swapped.');
        $this->failureNotificationTitle('The subscription could not be swapped. Please try again.');
        $this->modalHeading('Swap Subscription');
        $this->modalDescription('Select the new product to swap the current subscription to.');
        $this->modalSubmitActionLabel('Swap');

        $this->visible(function () {
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
            Radio::make('proration_behavior')
                ->required()
                ->label('Proration Behavior')
                ->default(ProrationBehavior::CreateProrations)
                ->options(ProrationBehavior::class),
        ]);

        $this->action(function (SwapAction $action, array $data): void {
            $result = SwapSubscriptionAction::execute($this->getUser(), Price::findOrFail($data['price_id']), $data['proration_behavior'], PaymentBehavior::DefaultIncomplete);

            if ($result) {
                $action->success();
            } else {
                $action->failure();
            }
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'swap';
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
