<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Override;

class CommissionItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'commissionItems';

    protected static ?string $title = 'Commissions';

    protected static ?string $badgeColor = 'gray';

    #[Override]
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->commissionItems->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Commissions')
            ->description('The commissions that were earned on this order.')
            ->emptyStateHeading('No commissions')
            ->emptyStateDescription('No commissions were earned on this order.')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('price.product.seller.name')
                    ->searchable(),
                TextColumn::make('price.product.commission_rate')
                    ->label('Commission Rate')
                    ->formatStateUsing(fn ($state) => Number::percentage($state * 100)),
                TextColumn::make('commission_amount')
                    ->label('Commission Amount')
                    ->money(),
            ]);
    }
}
