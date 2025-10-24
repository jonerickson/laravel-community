<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Prices\Actions;

use App\Enums\ProrationBehavior;
use App\Models\Price;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
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
        $this->visible(fn (Price $record): bool => filled($record->external_price_id));
        $this->icon(Heroicon::OutlinedArrowPath);
        $this->modalHeading('Update Product Price');
        $this->modalIcon(Heroicon::OutlinedArrowPath);
        $this->modalDescription('This will update the external price for the product. In some payment processors, this may cause a new price to be created and the existing to get archived. In that case, all subscriptions will be automatically transitioned to the new price.');
        $this->modalSubmitActionLabel('Start Update');
        $this->modalWidth(Width::ThreeExtraLarge);
        $this->successNotificationTitle('The price has been successfully update.');
        $this->failureNotificationTitle('There was an error updating the price. Please try again.');
        $this->schema([
            Radio::make('proration_behavior')
                ->label('Proration Behavior')
                ->default(ProrationBehavior::CreateProrations)
                ->options(ProrationBehavior::class),
        ]);
    }

    public static function getDefaultName(): ?string
    {
        return 'update_external_price';
    }
}
