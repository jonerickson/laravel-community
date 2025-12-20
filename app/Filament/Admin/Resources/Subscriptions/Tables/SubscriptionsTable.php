<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Tables;

use App\Enums\ProductType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Filament\Admin\Resources\Products\Pages\EditProduct;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Livewire\Subscriptions\ListSubscriptions;
use App\Models\Price;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->placeholder('Unknown Product')
                    ->url(fn (Subscription $record): ?string => $record->price?->product ? EditProduct::getUrl(['record' => $record->price->product]) : null),
                TextColumn::make('user.name')
                    ->sortable()
                    ->label('Customer')
                    ->searchable()
                    ->hiddenOn(ListSubscriptions::class)
                    ->url(fn (Subscription $record): ?string => $record->user ? EditUser::getUrl(['record' => $record->user]) : null)
                    ->toggleable(),
                TextColumn::make('stripe_status')
                    ->sortable()
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SubscriptionStatus::tryFrom($state)->getLabel())
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'canceled' => 'warning',
                        'incomplete', 'incomplete_expired', 'past_due' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('price.amount')
                    ->sortable()
                    ->money()
                    ->label('Price')
                    ->placeholder('No Price')
                    ->toggleable(),
                TextColumn::make('price.interval')
                    ->sortable()
                    ->money()
                    ->label('Interval')
                    ->formatStateUsing(fn ($state) => is_a($state, SubscriptionInterval::class) ? $state : SubscriptionInterval::tryFrom($state ?? ''))
                    ->placeholder('No Interval')
                    ->toggleable(),
                TextColumn::make('trial_ends_at')
                    ->sortable()
                    ->label('Trial Ends At')
                    ->since()
                    ->dateTimeTooltip()
                    ->placeholder('No Trial')
                    ->toggleable(),
                TextColumn::make('cancellation_reason')
                    ->wrap()
                    ->placeholder('No Reason Provided')
                    ->label('Cancellation Reason')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ends_at')
                    ->sortable()
                    ->label('Ends At')
                    ->since()
                    ->dateTimeTooltip()
                    ->placeholder('Active')
                    ->color(fn ($state): string => $state ? 'warning' : 'success')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->sortable()
                    ->since()
                    ->dateTimeTooltip()
                    ->label('Started')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('user.name')
                    ->relationship('user', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('Customer'),
                Filter::make('product')
                    ->schema([
                        Select::make('product')
                            ->label('Product')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(Price::query()
                                ->active()
                                ->with('product')
                                ->whereRelation('product', 'type', ProductType::Subscription)
                                ->whereHas('product', fn (Builder|Product $query) => $query->active())
                                ->get()
                                ->mapWithKeys(fn (Price $price): array => [$price->external_price_id => sprintf('%s: %s', $price->product->getLabel(), $price->getLabel())])),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['product'] ?? null),
                            fn (Builder|User $query): Builder => $query->where('stripe_price', $data['product']),
                        )
                    ),
                SelectFilter::make('stripe_status')
                    ->label('Status')
                    ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn (SubscriptionStatus $status): array => [$status->value => $status->getLabel()]))
                    ->multiple()
                    ->searchable()
                    ->default('active'),
                Filter::make('started_at')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        DatePicker::make('started_at_from')
                            ->label('Started At After'),
                        DatePicker::make('started_at_until')
                            ->label('Started At Before'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['started_at_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['started_at_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),
                Filter::make('ends_at')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        DatePicker::make('ends_at_from')
                            ->label('Ends At After'),
                        DatePicker::make('ends_at_until')
                            ->label('Ends At Before'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['ends_at_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('ends_at', '>=', $date),
                        )
                        ->when(
                            $data['ends_at_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('ends_at', '<=', $date),
                        )),
                Filter::make('trial_ends_at')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        DatePicker::make('trial_ends_at_from')
                            ->label('Trial Ends At After'),
                        DatePicker::make('trial_ends_at_until')
                            ->label('Trial Ends At Before'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['trial_ends_at_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('trial_ends_at', '>=', $date),
                        )
                        ->when(
                            $data['trial_ends_at_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('trial_ends_at', '<=', $date),
                        )),
            ])
            ->filtersFormWidth(Width::ExtraLarge)
            ->groups([
                Group::make('stripe_price')
                    ->label('Product')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn (Subscription $record): string => sprintf('%s: %s', $record->price?->product?->getLabel(), $record->price?->getLabel())),
            ])
            ->recordActions([
                ViewAction::make('view')
                    ->visible(fn (Subscription $record): bool => filled($record->user))
                    ->url(fn (Subscription $record): string => EditUser::getUrl(['record' => $record->user, 'tab' => 'subscriptions::data::tab']), shouldOpenInNewTab: true),
            ])
            ->defaultGroup('stripe_price')
            ->defaultSort('created_at', 'desc');
    }
}
