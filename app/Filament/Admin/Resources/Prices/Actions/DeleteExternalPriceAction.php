<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Prices\Actions;

use App\Managers\PaymentManager;
use App\Models\Price;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class DeleteExternalPriceAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Delete External Price');
        $this->visible(fn (Price $record): bool => filled($record->product->external_product_id) && filled($record->external_price_id) && config('payment.default'));
        $this->color('danger');
        $this->icon(Heroicon::OutlinedMinus);
        $this->requiresConfirmation();
        $this->successNotificationTitle('The external price was successfully deleted.');
        $this->failureNotificationTitle('The external price was not deleted. Please try again.');
        $this->modalDescription('Are you sure you would like to do this? This may only archive the price if it is associated with any orders/transactions.');
        $this->action(function (Price $record, DeleteExternalPriceAction $action): void {
            $paymentManger = app(PaymentManager::class);

            $result = $paymentManger->deletePrice($record);

            if (! $result) {
                $action->failure();

                return;
            }

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'delete_external_price';
    }
}
