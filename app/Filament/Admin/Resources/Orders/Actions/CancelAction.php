<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Actions;

use App\Managers\PaymentManager;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class CancelAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cancel');
        $this->color('danger');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->visible(fn (Order $record) => $record->status->canCancel());
        $this->successNotificationTitle('The order has been successfully cancelled.');
        $this->requiresConfirmation();
        $this->modalHeading('Cancel Order');
        $this->modalDescription('Are you sure you want to cancel this order?');
        $this->action(function (Order $record) {
            $paymentManager = app(PaymentManager::class);
            $paymentManager->cancelOrder(
                order: $record
            );
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'refund';
    }
}
