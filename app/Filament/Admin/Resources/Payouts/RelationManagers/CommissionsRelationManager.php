<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'commissions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->description('The commissions that were paid by this payout.')
            ->columns([
                TextColumn::make('order.reference_id')
                    ->copyable()
                    ->label('Order Number')
                    ->sortable(),
                TextColumn::make('order.invoice_number')
                    ->copyable()
                    ->label('Invoice Number')
                    ->sortable(),
                TextColumn::make('order.amount')
                    ->label('Sale Amount')
                    ->money(),
                TextColumn::make('amount')
                    ->label('Commission')
                    ->money()
                    ->sortable(),
                TextColumn::make('order.items.name')
                    ->label('Items'),
                TextColumn::make('order.status')
                    ->label('Order Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Commission Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('payout.status')
                    ->placeholder('No Payout Initiated')
                    ->label('Payout Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('payout.created_at')
                    ->placeholder('No Payout Initiated')
                    ->label('Payout Processed')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Order Created')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ]);
    }
}
