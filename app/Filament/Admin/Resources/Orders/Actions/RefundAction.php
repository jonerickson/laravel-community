<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Actions;

use App\Enums\OrderRefundReason;
use App\Managers\PaymentManager;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Override;

class RefundAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Refund');
        $this->color('info');
        $this->icon(Heroicon::OutlinedReceiptRefund);
        $this->requiresConfirmation();
        $this->modalHeading('Refund');
        $this->modalDescription("Are you sure you want to refund this order? Refunds can take up to 5-7 business days to appear on a customer's statement.");
        $this->modalSubmitActionLabel('Refund');
        $this->schema([
            Select::make('reason')
                ->helperText('Select the reason for the refund.')
                ->required()
                ->options(OrderRefundReason::class),
            Textarea::make('notes')
                ->nullable()
                ->helperText('Optional refund notes.'),
        ]);
        $this->visible(fn (Order $record): bool => $record->status->canRefund() && filled($record->external_order_id));
        $this->action(function (Order $record, array $data): void {
            $paymentManager = app(PaymentManager::class);
            $paymentManager->refundOrder(
                order: $record,
                reason: $data['reason'],
                notes: $data['notes'] ?? null,
            );
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'refund';
    }
}
