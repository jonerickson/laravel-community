<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Schemas;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Order;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('reference_id')
                            ->label('Order Number')
                            ->copyable(),
                        TextEntry::make('invoice_number')
                            ->label('Invoice Number')
                            ->default(new HtmlString('&mdash;')),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->since()
                            ->dateTimeTooltip(),
                        TextEntry::make('updated_at')
                            ->label('Updated')
                            ->since()
                            ->dateTimeTooltip(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('billing_reason')
                            ->label('Billing Reason')
                            ->badge(),
                        TextEntry::make('user.name')
                            ->url(fn (Order $record): string => UserResource::getUrl('edit', ['record' => $record->user_id]), shouldOpenInNewTab: true)
                            ->columnStart(1)
                            ->label('User'),
                    ]),
                Section::make('Totals')
                    ->columnSpanFull()
                    ->columns(5)
                    ->schema([
                        TextEntry::make('amount_subtotal')
                            ->placeholder('No Subtotal')
                            ->label('Subtotal')
                            ->money(),
                        TextEntry::make('amount_due')
                            ->placeholder('No Amount Due')
                            ->label('Due')
                            ->money(),
                        TextEntry::make('amount_paid')
                            ->placeholder('No Amount Paid')
                            ->label('Paid')
                            ->money(),
                        TextEntry::make('amount_overpaid')
                            ->placeholder('No Amount Overpaid')
                            ->label('Overpaid')
                            ->money(),
                        TextEntry::make('amount_remaining')
                            ->placeholder('No Amount Remaining')
                            ->label('Remaining')
                            ->money(),
                    ]),
                Section::make('Payment Processor')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('external_invoice_id')
                            ->copyable()
                            ->label('External Invoice ID')
                            ->default(new HtmlString('&mdash;')),
                        TextEntry::make('external_checkout_id')
                            ->copyable()
                            ->label('External Checkout ID')
                            ->default(new HtmlString('&mdash;')),
                        TextEntry::make('external_event_id')
                            ->copyable()
                            ->label('External Event ID')
                            ->default(new HtmlString('&mdash;')),
                        TextEntry::make('external_order_id')
                            ->columnStart(1)
                            ->copyable()
                            ->label('External Order ID')
                            ->default(new HtmlString('&mdash;')),
                        TextEntry::make('external_payment_id')
                            ->copyable()
                            ->label('External Payment ID')
                            ->default(new HtmlString('&mdash;')),
                        TextEntry::make('invoice_url')
                            ->label('Invoice URL')
                            ->copyable()
                            ->default(new HtmlString('&mdash;'))
                            ->columnSpanFull(),
                        TextEntry::make('checkout_url')
                            ->label('Checkout URL')
                            ->copyable()
                            ->default(new HtmlString('&mdash;'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
