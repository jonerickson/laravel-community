<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Actions\Payments\CreateSwapSubscriptionsBatchAction;
use App\Enums\PaymentBehavior;
use App\Enums\ProductType;
use App\Enums\ProrationBehavior;
use App\Models\Price;
use App\Models\Product;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Override;

class BulkSwapSubscriptionsAction extends BulkAction
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Swap subscriptions');
        $this->icon(Heroicon::OutlinedArrowPath);
        $this->color('gray');
        $this->requiresConfirmation();
        $this->modalHeading('Swap Subscriptions');
        $this->modalDescription("Are you sure you want to swap these user's subscriptions?");
        $this->modalWidth(Width::ThreeExtraLarge);
        $this->modalIcon(Heroicon::OutlinedArrowPath);
        $this->successNotificationTitle("The user's subscriptions have been successfully swapped.");
        $this->schema([
            Select::make('price_id')
                ->label('New Subscription')
                ->required()
                ->preload()
                ->searchable()
                ->options(fn () => Price::query()
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
        $this->action(function (BulkSwapSubscriptionsAction $action, array $data, Collection $records): void {
            $price = Price::query()->findOrFail($data['price_id']);

            CreateSwapSubscriptionsBatchAction::execute($records, $price, $data['proration_behavior'], PaymentBehavior::DefaultIncomplete);

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'bulk_swap_subscriptions';
    }
}
