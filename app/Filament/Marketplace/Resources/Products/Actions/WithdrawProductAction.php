<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Resources\Products\Actions;

use App\Enums\ProductApprovalStatus;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class WithdrawProductAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Withdraw');
        $this->color('danger');
        $this->icon(Heroicon::OutlinedArrowUturnLeft);
        $this->requiresConfirmation();
        $this->modalHeading('Withdraw product');
        $this->modalDescription('Are you sure you want to withdraw this product from the marketplace? It will no longer be visible to customers.');
        $this->successNotificationTitle('Product withdrawn from marketplace.');
        $this->visible(fn (Product $record): bool => $record->approval_status === ProductApprovalStatus::Approved);
        $this->action(function (Product $record, Action $action): void {
            $record->update([
                'approval_status' => ProductApprovalStatus::Withdrawn,
                'is_visible' => false,
            ]);

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'withdraw';
    }
}
