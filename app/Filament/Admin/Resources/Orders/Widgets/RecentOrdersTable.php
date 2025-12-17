<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Enums\BillingReason;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;

class RecentOrdersTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->completed()
                    ->with(['user', 'items.price'])
                    ->latest()
                    ->limit(15)
            )
            ->heading('Recent Orders')
            ->description('Most recent completed order activity.')
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->columns([
                IconColumn::make('billing_reason')
                    ->tooltip(fn (BillingReason $state): Htmlable|string|null => $state->getLabel())
                    ->label(''),
                TextColumn::make('reference_id')
                    ->sortable()
                    ->copyable()
                    ->label('Order #')
                    ->url(fn (Order $record): string => ViewOrder::getUrl(['record' => $record])),
                TextColumn::make('invoice_number')
                    ->sortable()
                    ->label('Invoice')
                    ->url(fn (Order $record): ?string => $record->invoice_url, shouldOpenInNewTab: true)
                    ->placeholder('N/A'),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->sortable()
                    ->url(fn (Order $record): ?string => $record->user ? EditUser::getUrl(['record' => $record->user]) : null),
                TextColumn::make('status')
                    ->sortable()
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money()
                    ->sortable(),
                TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money()
                    ->sortable(),
                TextColumn::make('discounts_count')
                    ->label('Discounts')
                    ->counts('discounts')
                    ->badge()
                    ->color('success')
                    ->default(0)
                    ->sortable(),
                TextColumn::make('items.name')
                    ->placeholder('N/A')
                    ->label('Items'),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Ordered')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Order $record): string => ViewOrder::getUrl(['record' => $record])),
            ]);
    }
}
