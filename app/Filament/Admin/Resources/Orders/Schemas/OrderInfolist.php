<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reference_id'),
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('invoice_number')
                    ->placeholder('-'),
                TextEntry::make('invoice_url')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('external_invoice_id')
                    ->placeholder('-'),
                TextEntry::make('external_checkout_id')
                    ->placeholder('-'),
                TextEntry::make('external_order_id')
                    ->placeholder('-'),
                TextEntry::make('external_payment_id')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
