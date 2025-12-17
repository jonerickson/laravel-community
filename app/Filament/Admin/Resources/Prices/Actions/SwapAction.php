<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Prices\Actions;

use App\Actions\Payments\CreateSwapSubscriptionsBatchAction;
use App\Enums\PaymentBehavior;
use App\Enums\ProrationBehavior;
use App\Managers\PaymentManager;
use App\Models\Price;
use App\Models\Product;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Override;

class SwapAction extends Action
{
    protected Product|Closure|null $product = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Swap price');
        $this->icon(Heroicon::OutlinedArrowsRightLeft);
        $this->color('gray');
        $this->successNotificationTitle('The subscriptions have been successfully swapped.');
        $this->failureNotificationTitle('The subscriptions could not be swapped. Please try again.');
        $this->modalHeading('Swap Subscription');
        $this->modalDescription('Use this action to bulk swap users from one subscription price to another.');
        $this->modalSubmitActionLabel('Swap');
        $this->visible(fn (Price $record) => Price::query()->where('product_id', $record->product_id)->whereKeyNot($record)->exists());

        $this->schema([
            Select::make('price_id')
                ->label('New Price')
                ->required()
                ->preload()
                ->searchable()
                ->options(fn (Price $record) => Price::query()->active()->where('product_id', $record->product_id)->whereKeyNot($record)->get()->mapWithKeys(fn (Price $price): array => [$price->id => $price->getLabel()])),
            Radio::make('proration_behavior')
                ->required()
                ->label('Proration Behavior')
                ->default(ProrationBehavior::CreateProrations)
                ->options(ProrationBehavior::class),
        ]);

        $this->action(function (Price $record, SwapAction $action, array $data): void {
            $price = Price::query()->findOrFail($data['price_id']);

            $paymentManager = app(PaymentManager::class);

            $subscribers = $paymentManager->listSubscribers($record);

            if (is_null($subscribers)) {
                $action->failure();

                return;
            }

            if ($subscribers->isEmpty()) {
                $action->failureNotificationTitle('There are no active subscriptions for the selected price.');
                $action->failure();

                return;
            }

            CreateSwapSubscriptionsBatchAction::execute($subscribers, $price, $data['proration_behavior'], PaymentBehavior::DefaultIncomplete);
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'swap';
    }
}
