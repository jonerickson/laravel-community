<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Prices\Actions;

use App\Managers\PaymentManager;
use App\Models\Price;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class CreateExternalPriceAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Create external price');
        $this->visible(fn (Price $record): bool => filled($record->product->external_product_id) && blank($record->external_price_id) && config('payment.default'));
        $this->color('gray');
        $this->icon(Heroicon::OutlinedPlus);
        $this->successNotificationTitle('The external price was successfully created.');
        $this->failureNotificationTitle('The external price was not created. Please try again.');
        $this->action(function (Price $record, CreateExternalPriceAction $action): void {
            $paymentManger = app(PaymentManager::class);

            $result = $paymentManger->createPrice($record);

            if (! $result) {
                $action->failure();

                return;
            }

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'create_external_price';
    }
}
