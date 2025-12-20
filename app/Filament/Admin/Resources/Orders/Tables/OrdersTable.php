<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Tables;

use App\Enums\BillingReason;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\SubscriptionStatus;
use App\Filament\Admin\Resources\Orders\Actions\CancelAction;
use App\Filament\Admin\Resources\Orders\Actions\CheckoutAction;
use App\Filament\Admin\Resources\Orders\Actions\RefundAction;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\RelationManagers\OrdersRelationManager;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('billing_reason')
                    ->tooltip(fn (BillingReason $state): string|Htmlable|null => $state->getLabel())
                    ->label(''),
                TextColumn::make('reference_id')
                    ->label('Order Number')
                    ->copyable()
                    ->sortable()
                    ->searchable(['reference_id', 'external_invoice_id', 'external_checkout_id', 'external_order_id', 'external_payment_id', 'external_event_id']),
                TextColumn::make('invoice_number')
                    ->default(new HtmlString('&mdash;'))
                    ->label('Invoice Number')
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->sortable()
                    ->hiddenOn(OrdersRelationManager::class)
                    ->url(fn (Order $record): ?string => $record->user ? EditUser::getUrl(['record' => $record->user]) : null)
                    ->searchable(),
                TextColumn::make('status')
                    ->sortable()
                    ->badge()
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Total')
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
                    ->searchable(),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('billing_reason')
                    ->multiple()
                    ->searchable()
                    ->label('Billing Reason')
                    ->options(BillingReason::class),
                SelectFilter::make('user')
                    ->label('Customer')
                    ->relationship('user', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple(),
                Filter::make('items')
                    ->schema([
                        TextInput::make('items_name')
                            ->label('Line Item Name/Description'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['items_name'],
                            fn (Builder|Order $query): Builder => $query
                                ->where(function (Builder $query) use ($data): void {
                                    $query->whereRelation('items', 'name', 'like', sprintf('%%%s%%', $data['items_name']))
                                        ->orWhereRelation('items', 'description', 'like', sprintf('%%%s%%', $data['items_name']));
                                })
                        )),
                SelectFilter::make('status')
                    ->label('Order Status')
                    ->default(OrderStatus::Succeeded)
                    ->options(OrderStatus::class),
                SelectFilter::make('products')
                    ->columnSpanFull()
                    ->relationship('items.price.product', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple(),
                Filter::make('subscription')
                    ->schema([
                        Select::make('subscription')
                            ->label('Subscription Packages')
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
                            filled($data['subscription'] ?? null),
                            fn (Builder|Order $query): Builder => $query->whereRelation('subscriptions', 'stripe_price', $data['subscription']),
                        )
                    ),
                Filter::make('subscription_status')
                    ->schema([
                        Select::make('subscription_status')
                            ->label('Subscription Status')
                            ->searchable()
                            ->preload()
                            ->options(SubscriptionStatus::class),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['subscription_status'],
                            fn (Builder $query, $date): Builder => $query->whereRelation('subscriptions', 'stripe_status', $data['subscription_status']),
                        )
                    ),
                Filter::make('amount_due')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextInput::make('amount_due_from')
                            ->numeric()
                            ->label('Amount Due Greater Than'),
                        TextInput::make('amount_due_after')
                            ->numeric()
                            ->label('Amount Due Less Than'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['amount_due_from'],
                            fn (Builder $query, $date): Builder => $query->where('amount_due', '>=', $date * 100),
                        )
                        ->when(
                            $data['amount_due_after'],
                            fn (Builder $query, $date): Builder => $query->where('amount_due', '<=', $date * 100),
                        )),
                Filter::make('amount_overpaid')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextInput::make('amount_overpaid_from')
                            ->numeric()
                            ->label('Amount Overpaid Greater Than'),
                        TextInput::make('amount_overpaid_after')
                            ->numeric()
                            ->label('Amount Overpaid Less Than'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['amount_overpaid_from'],
                            fn (Builder $query, $date): Builder => $query->where('amount_overpaid', '>=', $date * 100),
                        )
                        ->when(
                            $data['amount_overpaid_after'],
                            fn (Builder $query, $date): Builder => $query->where('amount_overpaid', '<=', $date * 100),
                        )),
                Filter::make('amount_paid')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextInput::make('amount_paid_from')
                            ->numeric()
                            ->label('Amount Paid Greater Than'),
                        TextInput::make('amount_paid_after')
                            ->numeric()
                            ->label('Amount Paid Less Than'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['amount_paid_from'],
                            fn (Builder $query, $date): Builder => $query->where('amount_paid', '>=', $date * 100),
                        )
                        ->when(
                            $data['amount_paid_after'],
                            fn (Builder $query, $date): Builder => $query->where('amount_paid', '<=', $date * 100),
                        )),
                Filter::make('amount_remaining')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextInput::make('amount_remaining_from')
                            ->numeric()
                            ->label('Amount Remaining Greater Than'),
                        TextInput::make('amount_remaining_after')
                            ->numeric()
                            ->label('Amount Remaining Less Than'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['amount_remaining_from'],
                            fn (Builder $query, $date): Builder => $query->where('amount_remaining', '>=', $date * 100),
                        )
                        ->when(
                            $data['amount_remaining_after'],
                            fn (Builder $query, $date): Builder => $query->where('amount_remaining', '<=', $date * 100),
                        )),
                Filter::make('created_at')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        DatePicker::make('created_at_from')
                            ->label('Created At After'),
                        DatePicker::make('created_at_until')
                            ->label('Created At Before'),
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
            ])
            ->filtersFormColumns(2)
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormWidth(Width::FiveExtraLarge)
            ->recordActions([
                CheckoutAction::make(),
                ViewAction::make(),
                EditAction::make(),
                RefundAction::make(),
                CancelAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->deferLoading()
            ->defaultSort('created_at', 'desc');
    }
}
