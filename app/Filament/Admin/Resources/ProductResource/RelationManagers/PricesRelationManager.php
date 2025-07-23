<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

    public function form(Form $form): Form
    {
        return $form
            ->columns()
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255)
                    ->helperText('Display name for this price option.'),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->stripCharacters(',')
                    ->mask(RawJs::make('$money($input)'))
                    ->prefix('$')
                    ->suffix('USD')
                    ->helperText('Price amount in USD.'),
                Forms\Components\Select::make('currency')
                    ->options([
                        'USD' => 'US Dollar',
                    ])
                    ->default('USD')
                    ->required(),
                Forms\Components\Select::make('interval')
                    ->options([
                        'day' => 'Daily',
                        'week' => 'Weekly',
                        'month' => 'Monthly',
                        'year' => 'Yearly',
                    ])
                    ->nullable()
                    ->helperText('Leave empty for one-time payment.'),
                Forms\Components\TextInput::make('interval_count')
                    ->label('Interval Count')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(365)
                    ->helperText('Number of intervals (e.g., every 2 months).'),
                Forms\Components\TextInput::make('stripe_price_id')
                    ->columnSpanFull()
                    ->label('Stripe Price ID')
                    ->helperText('The Stripe price ID (e.g., price_xxxxxxxxxxxx).')
                    ->placeholder('price_xxxxxxxxxxxx'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->helperText('Whether this price is available for purchase.'),
                Forms\Components\Toggle::make('is_default')
                    ->helperText('Whether this is the default price option.'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->helperText('Additional description for this price option.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('The individual pricing for the product.')
            ->emptyStateDescription('There are no prices for this product.')
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('interval')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'One-time'
                    )
                    ->colors([
                        'success' => fn (?string $state): bool => is_null($state),
                        'info' => fn (?string $state): bool => ! is_null($state),
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),
                Tables\Columns\TextColumn::make('stripe_price_id')
                    ->label('Stripe ID')
                    ->placeholder('Not linked')
                    ->copyable()
                    ->copyMessage('Stripe Price ID copied')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Price'),
                Tables\Filters\SelectFilter::make('interval')
                    ->options([
                        'day' => 'Daily',
                        'week' => 'Weekly',
                        'month' => 'Monthly',
                        'year' => 'Yearly',
                    ])
                    ->placeholder('All Types'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
