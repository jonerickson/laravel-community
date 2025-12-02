<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use App\Enums\PriceType;
use App\Enums\SubscriptionInterval;
use App\Filament\Admin\Resources\Prices\Actions\CreateExternalPriceAction;
use App\Filament\Admin\Resources\Prices\Actions\DeleteExternalPriceAction;
use App\Filament\Admin\Resources\Prices\Actions\SwapAction;
use App\Filament\Admin\Resources\Prices\Actions\SyncExternalPriceAction;
use App\Filament\Admin\Resources\Prices\Actions\UpdateExternalPriceAction;
use App\Models\Price;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedCurrencyDollar;

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
                Radio::make('type')
                    ->disabled(fn ($operation, ?Price $record): bool => $operation === 'edit' && filled($record->external_price_id))
                    ->live()
                    ->columnSpanFull()
                    ->label('Type')
                    ->helperText('The type of price.')
                    ->options(PriceType::class)
                    ->required(),
                TextInput::make('amount')
                    ->disabled(fn ($operation, ?Price $record): bool => $operation === 'edit' && filled($record->external_price_id))
                    ->required()
                    ->numeric()
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->prefix('$')
                    ->suffix('USD')
                    ->step(0.01)
                    ->minValue(0),
                Select::make('currency')
                    ->disabled(fn ($operation, ?Price $record): bool => $operation === 'edit' && filled($record->external_price_id))
                    ->options([
                        'USD' => 'US Dollar',
                    ])
                    ->default('USD')
                    ->required(),
                Select::make('interval')
                    ->disabled(fn ($operation, ?Price $record): bool => $operation === 'edit' && filled($record->external_price_id))
                    ->options(SubscriptionInterval::class)
                    ->nullable()
                    ->visible(fn (Get $get): bool => $get('type') === PriceType::Recurring)
                    ->helperText('Subscription billing interval.'),
                TextInput::make('interval_count')
                    ->disabled(fn ($operation, ?Price $record): bool => $operation === 'edit' && filled($record->external_price_id))
                    ->label('Interval Count')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(365)
                    ->visible(fn (Get $get): bool => $get('type') === PriceType::Recurring)
                    ->helperText('Number of intervals (e.g., every 2 months).'),
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Whether this price is available for purchase.'),
                        Toggle::make('is_visible')
                            ->label('Visible')
                            ->default(true)
                            ->helperText('Whether this price can be seen publicly.'),
                        Toggle::make('is_default')
                            ->label('Default')
                            ->helperText('Whether this is the default price option.'),
                    ]),
                Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull()
                    ->helperText('Additional description for this price option.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(fn (): string => $this->getOwnerRecord()->isSubscription()
                ? 'Subscription pricing for this product.'
                : 'One-time pricing for this product.')
            ->emptyStateDescription(fn (): string => $this->getOwnerRecord()->isSubscription()
                ? 'No subscription prices set for this product.'
                : 'No prices set for this product.')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->money()
                    ->sortable(),
                TextColumn::make('type')
                    ->sortable()
                    ->placeholder('No Type')
                    ->badge(),
                TextColumn::make('interval')
                    ->sortable()
                    ->badge()
                    ->placeholder('No Interval')
                    ->color('info'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                IconColumn::make('is_visible')
                    ->boolean()
                    ->label('Visible')
                    ->sortable(),
                IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default')
                    ->sortable(),
                IconColumn::make('external_price_id')
                    ->visible(fn () => config('payment.default'))
                    ->label('External Price')
                    ->default(false)
                    ->boolean(),
                TextColumn::make('subscriptions_count')
                    ->visible(fn (): bool => (bool) $this->getOwnerRecord()->isSubscription())
                    ->label('# of Subscriptions')
                    ->numeric()
                    ->counts('subscriptions'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->default()
                    ->label('Active Status'),
                TernaryFilter::make('is_default')
                    ->label('Default Price'),
                SelectFilter::make('interval')
                    ->options(SubscriptionInterval::class)
                    ->placeholder('All Intervals')
                    ->visible(fn () => $this->getOwnerRecord()->isSubscription()),
                TernaryFilter::make('is_visible')
                    ->label('Visible'),
            ])
            ->headerActions([
                SyncExternalPriceAction::make('sync')
                    ->product(fn (): Model => $this->getOwnerRecord()),
                CreateAction::make(),
            ])
            ->recordActions([
                CreateExternalPriceAction::make(),
                DeleteExternalPriceAction::make(),
                UpdateExternalPriceAction::make(),
                SwapAction::make(),
                EditAction::make()
                    ->modalDescription('Use the update external price tool to update the product price.'),
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
