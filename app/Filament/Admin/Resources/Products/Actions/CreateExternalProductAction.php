<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Actions;

use App\Managers\PaymentManager;
use App\Models\Product;
use Filament\Actions\Action;
use Override;

class CreateExternalProductAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Create external product');
        $this->visible(fn (Product $record): bool => blank($record->external_product_id) && config('payment.default'));
        $this->color('gray');
        $this->successNotificationTitle('The external product was successfully created.');
        $this->failureNotificationTitle('The external product was not created. Please try again.');
        $this->action(function (Product $record, CreateExternalProductAction $action): void {
            $paymentManger = app(PaymentManager::class);

            $result = $paymentManger->createProduct($record);

            if (! $result) {
                $action->failure();

                return;
            }

            foreach ($record->prices as $price) {
                $paymentManger->createPrice($price);
            }

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'create_external_product';
    }
}
