<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Discounts\Pages;

use App\Filament\Admin\Resources\Discounts\DiscountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscounts extends ListRecords
{
    protected static string $resource = DiscountResource::class;

    protected ?string $subheading = 'Manage your discounts, promotion codes and gift cards available for customers to use towards purchases.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
