<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Schemas;

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
                    ->columns()
                    ->schema([
                        TextEntry::make('reference_id')
                            ->label('Order Number')
                            ->copyable(),
                        TextEntry::make('invoice_number')
                            ->default(new HtmlString('&mdash;')),
                        TextEntry::make('user.name')
                            ->label('User'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('amount')
                            ->money(),
                    ]),
                Section::make('Payment Processor')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextEntry::make('external_invoice_id')
                            ->copyable()
                            ->label('External Invoice ID')
                            ->default(new HtmlString('&mdash;')),
                        TextEntry::make('external_checkout_id')
                            ->copyable()
                            ->label('External Checkout ID')
                            ->default(new HtmlString('&mdash;')),
                        TextEntry::make('external_order_id')
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
