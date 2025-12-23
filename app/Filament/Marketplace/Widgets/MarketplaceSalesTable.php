<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Widgets;

use App\Models\Commission;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;
use Override;

class MarketplaceSalesTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    #[Override]
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Commission::query()
                    ->whereBelongsTo(Auth::user(), 'seller')
                    ->with(['order.items.price.product'])
                    ->latest()
                    ->limit(15)
            )
            ->heading('Recent Sales')
            ->description('Recent orders containing your products.')
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->emptyStateHeading('No recent orders found')
            ->emptyStateDescription('No recent orders found. Get started selling today!')
            ->columns([
                TextColumn::make('order.reference_id')
                    ->copyable()
                    ->label('Order Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.invoice_number')
                    ->copyable()
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->placeholder('N/A'),
                TextColumn::make('order.amount')
                    ->label('Sale Amount')
                    ->money()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Commission')
                    ->money()
                    ->sortable(),
                TextColumn::make('order.items.name')
                    ->label('Items')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.status')
                    ->label('Order Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Commission Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('payout.status')
                    ->placeholder('No Payout Initiated')
                    ->label('Payout Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ]);
    }
}
