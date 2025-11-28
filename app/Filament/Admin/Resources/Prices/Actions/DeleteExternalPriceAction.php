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

        $this->label('Delete external price');
        $this->visible(fn (Price $record): bool => filled($record->product->external_product_id) && filled($record->external_price_id) && config('payment.default'));
        $this->color('danger');
        $this->icon(Heroicon::OutlinedMinus);
        $this->requiresConfirmation();
        $this->successNotificationTitle('The external price was successfully deleted.');
        $this->failureNotificationTitle('The external price was not deleted. Please try again.');
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
