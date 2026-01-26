<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Resources\Products\Tables;

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductType;
use App\Filament\Marketplace\Resources\Products\Actions\ResubmitProductAction;
use App\Filament\Marketplace\Resources\Products\Actions\WithdrawProductAction;
use App\Models\Product;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
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
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean()
                    ->sortable(),
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
                    ->label('Approval Status')
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
                ViewAction::make()
                    ->url(fn (Product $record): string => route('store.products.show', $record->slug), shouldOpenInNewTab: true),
                EditAction::make()
                    ->modalHeading('Edit Product')
                    ->slideOver(),
                WithdrawProductAction::make(),
                ResubmitProductAction::make(),
            ]);
    }
}
