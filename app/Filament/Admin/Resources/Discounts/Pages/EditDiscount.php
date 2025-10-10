<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Discounts\Pages;

use App\Filament\Admin\Resources\Discounts\DiscountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDiscount extends EditRecord
{
    protected static string $resource = DiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
