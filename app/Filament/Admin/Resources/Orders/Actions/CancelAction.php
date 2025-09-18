<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class CancelAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cancel');
        $this->color('gray');
        $this->icon(Heroicon::OutlinedXCircle);
    }

    public static function getDefaultName(): ?string
    {
        return 'refund';
    }
}
