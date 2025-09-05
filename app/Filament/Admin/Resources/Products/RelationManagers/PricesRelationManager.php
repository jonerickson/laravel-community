<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns()
            ->components([
                TextInput::make('name')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255)
                    ->helperText('Display name for this price option.'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->stripCharacters(',')
                    ->mask(RawJs::make('$money($input)'))
                    ->prefix('$')
                    ->suffix('USD')
                    ->helperText('Price amount in USD.'),
                Select::make('currency')
                    ->options([
                        'USD' => 'US Dollar',
                    ])
                    ->default('USD')
                    ->required(),
                Select::make('interval')
                    ->options([
                        'day' => 'Daily',
                        'week' => 'Weekly',
                        'month' => 'Monthly',
                        'year' => 'Yearly',
                    ])
                    ->nullable()
                    ->visible(fn () => $this->getOwnerRecord()->isSubscription())
                    ->helperText('Subscription billing interval.'),
                TextInput::make('interval_count')
                    ->label('Interval Count')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(365)
                    ->visible(fn () => $this->getOwnerRecord()->isSubscription())
                    ->helperText('Number of intervals (e.g., every 2 months).'),
                Toggle::make('is_active')
                    ->default(true)
                    ->helperText('Whether this price is available for purchase.'),
                Toggle::make('is_default')
                    ->helperText('Whether this is the default price option.'),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->helperText('Additional description for this price option.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(fn () => $this->getOwnerRecord()->isSubscription()
                ? 'Subscription pricing for this product.'
                : 'One-time pricing for this product.')
            ->emptyStateDescription(fn () => $this->getOwnerRecord()->isSubscription()
                ? 'No subscription prices set for this product.'
                : 'No prices set for this product.')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('interval')
                    ->badge()
                    ->visible(fn () => $this->getOwnerRecord()->isSubscription())
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? ucfirst($state) : 'Monthly')
                    ->color('info'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
                TernaryFilter::make('is_default')
                    ->label('Default Price'),
                SelectFilter::make('interval')
                    ->options([
                        'day' => 'Daily',
                        'week' => 'Weekly',
                        'month' => 'Monthly',
                        'year' => 'Yearly',
                    ])
                    ->placeholder('All Intervals')
                    ->visible(fn () => $this->getOwnerRecord()->isSubscription()),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
