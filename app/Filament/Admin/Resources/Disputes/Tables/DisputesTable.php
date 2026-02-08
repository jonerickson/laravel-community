<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes\Tables;

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\Dispute;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class DisputesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('external_dispute_id')
                    ->label('Dispute ID')
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('order.reference_id')
                    ->label('Order')
                    ->url(fn (Dispute $record): ?string => $record->order ? ViewOrder::getUrl(['record' => $record->order]) : null)
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->url(fn (Dispute $record): ?string => $record->user ? EditUser::getUrl(['record' => $record->user]) : null)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('reason')
                    ->sortable(),
                TextColumn::make('amount')
                    ->formatStateUsing(fn (int $state): string => Number::currency($state / 100))
                    ->sortable(),
                TextColumn::make('evidence_due_by')
                    ->label('Evidence Due')
                    ->date()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options(DisputeStatus::class),
                SelectFilter::make('reason')
                    ->label('Reason')
                    ->multiple()
                    ->options(DisputeReason::class),
                Filter::make('created_at')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        DatePicker::make('created_at_from')
                            ->label('Created after'),
                        DatePicker::make('created_at_until')
                            ->label('Created before'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['created_at_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_at_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),
                Filter::make('amount')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextInput::make('amount_from')
                            ->numeric()
                            ->label('Amount greater than'),
                        TextInput::make('amount_to')
                            ->numeric()
                            ->label('Amount less than'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['amount_from'],
                            fn (Builder $query, $value): Builder => $query->where('amount', '>=', $value * 100),
                        )
                        ->when(
                            $data['amount_to'],
                            fn (Builder $query, $value): Builder => $query->where('amount', '<=', $value * 100),
                        )),
            ])
            ->filtersFormColumns(2)
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormWidth(Width::FiveExtraLarge)
            ->deferLoading()
            ->defaultSort('created_at', 'desc');
    }
}
