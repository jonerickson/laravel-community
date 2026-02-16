<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Actions;

use App\Managers\PaymentManager;
use App\Models\Product;
use Filament\Actions\Action;
use Override;

class DeleteExternalProductAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Delete External Product');
        $this->visible(fn (Product $record): bool => filled($record->external_product_id) && config('payment.default'));
        $this->color('danger');
        $this->requiresConfirmation();
        $this->successNotificationTitle('The external product was successfully deleted.');
        $this->failureNotificationTitle('The external product was not deleted. Please try again. You may need to manually delete all prices from the product on the payment processor dashboard.');
        $this->modalHeading('Delete external product');
        $this->modalDescription('Are you sure you would like to do this? This may only archive the product if it is associated with any orders/transactions.');
        $this->action(function (Product $record, DeleteExternalProductAction $action): void {
            $paymentManger = app(PaymentManager::class);

            foreach ($record->prices as $price) {
                $paymentManger->deletePrice($price);
            }

            $result = $paymentManger->deleteProduct($record);

            if (! $result) {
                $action->failure();

                return;
            }

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'delete_external_product';
    }
}
