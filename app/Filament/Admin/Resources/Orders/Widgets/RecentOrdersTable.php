<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Number;

class RecentOrdersTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with(['user', 'items.price'])
                    ->latest()
                    ->limit(15)
            )
            ->heading('Recent Orders')
            ->description('Most recent order activity.')
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->columns([
                TextColumn::make('reference_id')
                    ->label('Order #')
                    ->url(fn (Order $record): string => ViewOrder::getUrl(['record' => $record])),
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->url(fn (Order $record): ?string => $record->invoice_url, shouldOpenInNewTab: true)
                    ->placeholder('N/A'),
                TextColumn::make('items.product.name')
                    ->label('Product(s)'),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->url(fn (Order $record): ?string => $record->user ? EditUser::getUrl(['record' => $record->user]) : null),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (Order $record): string => Number::currency($record->amount / 100, 'USD'))
                    ->sortable(),
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
            ]);
    }
}
