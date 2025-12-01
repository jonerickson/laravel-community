<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryTransaction;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryTransactions';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedListBullet;

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Complete audit trail of all inventory movements for this product.')
            ->emptyStateDescription('No inventory transactions recorded yet.')
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (int $state, InventoryTransaction $record): string => $state > 0 ? '+'.$state : (string) $state)
                    ->color(fn (int $state): string => match (true) {
                        $state > 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('quantity_before')
                    ->label('Before')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('quantity_after')
                    ->label('After')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('reason')
                    ->placeholder('No Reason Provided')
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('notes')
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('reference_type')
                    ->label('Reference Type')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : 'N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reference.id')
                    ->label('Reference ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(InventoryTransactionType::class)
                    ->native(false),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
