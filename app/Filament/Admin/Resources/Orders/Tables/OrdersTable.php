<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Tables;

use App\Filament\Admin\Resources\Orders\Actions\CancelAction;
use App\Filament\Admin\Resources\Orders\Actions\RefundAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                    ->label('Invoice Number')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('amount')
                    ->money('USD', divideBy: 100)
                    ->sortable(),
                TextColumn::make('external_order_id')
                    ->copyable()
                    ->label('External Order ID'),
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
                EditAction::make(),
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
            ]);
    }
}
