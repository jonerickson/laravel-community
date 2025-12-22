<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Widgets;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
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
                OrderItem::query()
                    ->with(['order.user', 'price.product'])
                    ->whereHas('price.product', fn (Builder $query) => $query->where('seller_id', Auth::id()))
                    ->whereHas('order', fn (Builder $query) => $query->where('status', OrderStatus::Succeeded))
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
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->placeholder('N/A'),
                TextColumn::make('order.status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Sale Amount')
                    ->money()
                    ->sortable(),
                TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ]);
    }
}
