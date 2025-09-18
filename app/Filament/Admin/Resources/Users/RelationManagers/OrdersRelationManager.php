<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Admin\Resources\Orders\OrderResource;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $relatedResource = OrderResource::class;

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedCurrencyDollar;

    public function table(Table $table): Table
    {
        return $table
            ->description('The user\'s orders')
            ->toolbarActions([]);
    }
}
