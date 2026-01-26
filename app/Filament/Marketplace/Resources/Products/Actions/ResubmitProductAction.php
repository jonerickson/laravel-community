<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Resources\Products\Actions;

use App\Enums\ProductApprovalStatus;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class ResubmitProductAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Re-submit');
        $this->color('warning');
        $this->icon(Heroicon::OutlinedArrowPath);
        $this->requiresConfirmation();
        $this->modalHeading('Re-submit product');
        $this->modalDescription('Are you sure you want to re-submit this product? It will need to be reviewed by staff before it becomes visible again.');
        $this->successNotificationTitle('Product re-submitted for review.');
        $this->visible(fn (Product $record): bool => $record->approval_status === ProductApprovalStatus::Withdrawn);
        $this->action(function (Product $record, Action $action): void {
            $record->update([
                'approval_status' => ProductApprovalStatus::Pending,
            ]);

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'resubmit';
    }
}
