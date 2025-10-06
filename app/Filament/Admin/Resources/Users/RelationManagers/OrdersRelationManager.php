<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Admin\Resources\Orders\OrderResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $relatedResource = OrderResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->description('The user\'s order history.')
            ->emptyStateHeading('No order history')
            ->emptyStateDescription('This user has no order history.')
            ->emptyStateIcon('heroicon-o-shopping-cart')
            ->toolbarActions([]);
    }
}
