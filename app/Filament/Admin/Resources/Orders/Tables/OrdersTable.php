<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Orders\Actions\CancelAction;
use App\Filament\Admin\Resources\Orders\Actions\CheckoutAction;
use App\Filament\Admin\Resources\Orders\Actions\RefundAction;
use App\Filament\Admin\Resources\Users\RelationManagers\OrdersRelationManager;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_id')
                    ->label('Order Number')
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('invoice_number')
                    ->default(new HtmlString('&mdash;'))
                    ->label('Invoice Number')
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->hiddenOn(OrdersRelationManager::class)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Total')
                    ->money()
                    ->sortable(),
                TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money()
                    ->sortable(),
                TextColumn::make('discounts_count')
                    ->label('Discounts')
                    ->counts('discounts')
                    ->badge()
                    ->color('success')
                    ->default(0)
                    ->sortable(),
                TextColumn::make('items.name')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('product')
                    ->relationship('items.price.product', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('status')
                    ->default(OrderStatus::Succeeded)
                    ->options(OrderStatus::class),
            ])
            ->recordActions([
                CheckoutAction::make(),
                ViewAction::make(),
                EditAction::make(),
                RefundAction::make(),
                CancelAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->deferLoading()
            ->defaultSort('created_at', 'desc');
    }
}
