<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Resources\Products\Tables;

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductType;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('approval_status')
                    ->label('Approval Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('commission_rate')
                    ->label('Commission Rate')
                    ->suffix('%')
                    ->sortable()
                    ->formatStateUsing(fn ($state): int|float => $state * 100),
                TextColumn::make('categories.name')
                    ->badge()
                    ->searchable()
                    ->listWithLineBreaks()
                    ->limitList(2),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->options(ProductApprovalStatus::class)
                    ->native(false),
                SelectFilter::make('type')
                    ->options(ProductType::class)
                    ->native(false),
                SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
