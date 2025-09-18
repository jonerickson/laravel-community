<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class RefundAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Refund');
        $this->color('danger');
        $this->icon(Heroicon::OutlinedReceiptRefund);
    }

    public static function getDefaultName(): ?string
    {
        return 'refund';
    }
}
