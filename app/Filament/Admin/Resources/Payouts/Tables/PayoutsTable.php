<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Tables;

use App\Enums\PayoutStatus;
use Filament\Actions\EditAction;
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
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('external_payout_id')
                    ->label('External ID')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('N/A'),
                TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('processor.name')
                    ->label('Processed By')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('N/A'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->options([
                        'PayPal' => 'PayPal',
                        'Bank Transfer' => 'Bank Transfer',
                        'Stripe' => 'Stripe Connect',
                        'Check' => 'Check',
                        'Other' => 'Other',
                    ])
                    ->native(false),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
