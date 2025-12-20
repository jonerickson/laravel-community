<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Discounts\Tables;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('discount_type')
                    ->label('Discount Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('value')
                    ->formatStateUsing(fn (Discount $record) => $record->value_label),
                TextColumn::make('current_balance')
                    ->placeholder('—')
                    ->label('Balance')
                    ->formatStateUsing(function ($state): string {
                        if ($state === null) {
                            return '—';
                        }

                        return Number::currency($state);
                    })
                    ->sortable(),
                TextColumn::make('times_used')
                    ->label('Uses')
                    ->formatStateUsing(function ($record): string {
                        $used = $record->times_used;
                        $max = $record->max_uses;

                        if ($max) {
                            return sprintf('%s / %s', $used, $max);
                        }

                        return (string) $used;
                    })
                    ->sortable(),
                IconColumn::make('is_valid')
                    ->label('Valid')
                    ->boolean(),
                IconColumn::make('is_expired')
                    ->label('Expired')
                    ->boolean(),
                TextColumn::make('customer.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
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
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(DiscountType::class)
                    ->native(false),
                SelectFilter::make('discount_type')
                    ->label('Discount Type')
                    ->options(DiscountValueType::class)
                    ->native(false),
                TernaryFilter::make('has_balance')
                    ->label('Has Balance')
                    ->queries(
                        true: fn (Builder|Discount $query): Builder => $query->withBalance(),
                        false: fn (Builder $query): Builder => $query->whereNotNull('current_balance')->where('current_balance', '<=', 0),
                    )
                    ->native(false),
                SelectFilter::make('user')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('expired')
                    ->label('Expired Discounts')
                    ->query(fn (Builder|Discount $query): Builder => $query->expired()),
                Filter::make('active')
                    ->default(true)
                    ->label('Active Discounts')
                    ->query(fn (Builder|Discount $query): Builder => $query->active()),
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
