<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Number;

class TopProductsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->select('products.*')
                    ->selectRaw('COUNT(DISTINCT orders.id) as orders_count')
                    ->selectRaw('SUM(COALESCE(orders_items.amount, prices.amount) * orders_items.quantity) as total_revenue')
                    ->join('prices', 'products.id', '=', 'prices.product_id')
                    ->join('orders_items', 'prices.id', '=', 'orders_items.price_id')
                    ->join('orders', 'orders_items.order_id', '=', 'orders.id')
                    ->where('orders.status', OrderStatus::Succeeded)
                    ->groupBy('products.id')
                    ->orderByDesc('total_revenue')
                    ->limit(10)
            )
            ->heading('Top 10 Products by Revenue')
            ->description('Best-selling products based on total revenue generated.')
            ->deferLoading()
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->sortable(),
                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->formatStateUsing(fn ($state) => Number::format($state ?? 0))
                    ->sortable(),
                TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money(divideBy: 100)
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Product $record): string => EditProduct::getUrl([
                        'record' => $record,
                    ])),
            ]);
    }
}
