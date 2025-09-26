<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Tables;

use App\Filament\Admin\Resources\Orders\Actions\CancelAction;
use App\Filament\Admin\Resources\Orders\Actions\CheckoutAction;
use App\Filament\Admin\Resources\Orders\Actions\RefundAction;
use App\Filament\Admin\Resources\Users\RelationManagers\OrdersRelationManager;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
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
                    ->searchable(),
                TextColumn::make('invoice_number')
                    ->default(new HtmlString('&mdash;'))
                    ->label('Invoice Number')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->hiddenOn(OrdersRelationManager::class)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('amount')
                    ->money('USD', divideBy: 100)
                    ->sortable(),
                TextColumn::make('items.product.name')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                CheckoutAction::make(),
                RefundAction::make(),
                ViewAction::make(),
                EditAction::make(),
                CancelAction::make(),
                DeleteAction::make(),
                ActionGroup::make([
                    CancelAction::make(),
                    RefundAction::make(),
                ]),
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
