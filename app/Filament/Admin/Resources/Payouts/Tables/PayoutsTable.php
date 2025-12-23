<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Tables;

use App\Enums\PayoutDriver;
use App\Enums\PayoutStatus;
use App\Filament\Admin\Resources\Payouts\Actions\CancelAction;
use App\Filament\Admin\Resources\Payouts\Actions\RetryAction;
use App\Filament\Admin\Resources\Users\RelationManagers\PayoutsRelationManager;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('seller.name')
                    ->hiddenOn(PayoutsRelationManager::class)
                    ->label('Seller')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('payout_method')
                    ->label('Method')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('author.name')
                    ->label('Processed By')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('N/A'),
                TextColumn::make('created_at')
                    ->label('Processed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PayoutStatus::class)
                    ->native(false),
                SelectFilter::make('payout_method')
                    ->label('Method')
                    ->options(PayoutDriver::class)
                    ->native(false),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                RetryAction::make(),
                CancelAction::make(),
                ViewAction::make(),
            ]);
    }
}
