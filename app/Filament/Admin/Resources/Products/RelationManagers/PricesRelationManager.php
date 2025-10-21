<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use App\Enums\SubscriptionInterval;
use App\Filament\Admin\Resources\Products\Actions\CreateExternalPriceAction;
use App\Filament\Admin\Resources\Products\Actions\DeleteExternalPriceAction;
use App\Managers\PaymentManager;
use App\Models\Price;
use App\Models\Product;
use Filament\Actions\Action;
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
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

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
                    ->visible(fn () => $this->getOwnerRecord()->isSubscription())
                    ->helperText('Subscription billing interval.'),
                TextInput::make('interval_count')
                    ->disabled(fn ($operation, ?Price $record): bool => $operation === 'edit' && filled($record->external_price_id))
                    ->label('Interval Count')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(365)
                    ->visible(fn () => $this->getOwnerRecord()->isSubscription())
                    ->helperText('Number of intervals (e.g., every 2 months).'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Whether this price is available for purchase.'),
                Toggle::make('is_default')
                    ->label('Default')
                    ->helperText('Whether this is the default price option.'),
                Textarea::make('description')
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
                TextColumn::make('interval')
                    ->badge()
                    ->visible(fn () => $this->getOwnerRecord()->isSubscription())
                    ->color('info'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default')
                    ->sortable(),
                IconColumn::make('external_price_id')
                    ->label('External Price')
                    ->default(false)
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Created')
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
                    ->options(SubscriptionInterval::class)
                    ->placeholder('All Intervals')
                    ->visible(fn () => $this->getOwnerRecord()->isSubscription()),
            ])
            ->headerActions([
                Action::make('sync')
                    ->color('gray')
                    ->label('Sync prices')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => filled($this->getOwnerRecord()->external_product_id))
                    ->modalHeading('Sync Product Prices')
                    ->modalIcon(Heroicon::OutlinedArrowPath)
                    ->modalDescription('This will remove any existing prices for this product locally and pull in the latest product prices from your payment processor.')
                    ->modalSubmitActionLabel('Sync')
                    ->successNotificationTitle('The prices have been successfully synced.')
                    ->failureNotificationTitle('There was an error syncing the prices. Please try again.')
                    ->action(function (Action $action): void {
                        /** @var Product $product */
                        $product = $this->getOwnerRecord();

                        $result = DB::transaction(function () use ($product): true {
                            $paymentManager = app(PaymentManager::class);

                            if ($prices = $paymentManager->listPrices($product)) {
                                $product->prices()->delete();
                                $product->prices()->saveMany($prices);
                            }

                            return true;
                        });

                        if ($result) {
                            $action->success();
                        } else {
                            $action->failure();
                        }
                    }),
                CreateAction::make(),
            ])
            ->recordActions([
                CreateExternalPriceAction::make(),
                DeleteExternalPriceAction::make(),
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
