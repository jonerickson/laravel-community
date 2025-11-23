<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Prices\Actions;

use App\Actions\Payments\CreateSwapSubscriptionsBatchAction;
use App\Enums\PaymentBehavior;
use App\Enums\ProductType;
use App\Enums\ProrationBehavior;
use App\Managers\PaymentManager;
use App\Models\Price;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Illuminate\Support\Str;
use Override;

class UpdateExternalPriceAction extends Action
{
    protected Closure|Price|null $price = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->color('gray');
        $this->label('Update external price');
        $this->requiresConfirmation();
        $this->visible(fn (Price $record): bool => filled($record->external_price_id) && $record->product->type === ProductType::Subscription && config('payment.default'));
        $this->icon(Heroicon::OutlinedArrowPath);
        $this->modalHeading('Update Product Price');
        $this->modalIcon(Heroicon::OutlinedArrowPath);
        $this->modalDescription('This will create a new price for the product and all subscriptions will be automatically transitioned to the new price.');
        $this->modalSubmitActionLabel('Start Update');
        $this->modalWidth(Width::ThreeExtraLarge);
        $this->successNotificationTitle('The price has been successfully update and the current subscribers are currently being transitioned to the new price.');
        $this->failureNotificationTitle('There was an error updating the price. Please try again.');
        $this->schema([
            TextInput::make('amount')
                ->required()
                ->numeric()
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->prefix('$')
                ->suffix('USD')
                ->step(0.01)
                ->minValue(0),
            TextEntry::make('interval')
                ->getStateUsing(fn (Price $record) => $record->interval)
                ->helperText('When changing a price, the interval must remain the same. To change the interval, create a new price instead.'),
            Radio::make('proration_behavior')
                ->required()
                ->label('Proration Behavior')
                ->default(ProrationBehavior::CreateProrations)
                ->options(ProrationBehavior::class),
        ]);

        $this->action(function (UpdateExternalPriceAction $action, Price $record, array $data): void {
            $paymentManager = app(PaymentManager::class);

            $subscribers = $paymentManager->listSubscribers($record);

            if (is_null($subscribers)) {
                $action->failure();

                return;
            }

            $newPrice = $record->replicate([
                'amount',
                'external_price_id',
                'metadata',
                'reference_id',
            ]);

            $newPrice->is_active = true;
            $newPrice->reference_id = Str::uuid()->toString();
            $newPrice->amount = $data['amount'];
            $newPrice->saveQuietly();

            $price = $paymentManager->createPrice($newPrice);

            if (! $price || ! $price->externalPriceId) {
                $newPrice->delete();
                $action->failure();

                return;
            }

            if ($subscribers->isNotEmpty()) {
                CreateSwapSubscriptionsBatchAction::execute($subscribers, $newPrice, $data['proration_behavior'], PaymentBehavior::DefaultIncomplete);
            }

            $paymentManager->deletePrice($record);
            $record->deactivate();

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'update_external_price';
    }
}
