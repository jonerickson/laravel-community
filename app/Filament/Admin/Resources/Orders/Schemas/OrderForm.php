<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference_id')
                    ->label('Order Number')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->required(),
                TextInput::make('amount')
                    ->numeric(),
                TextInput::make('invoice_number'),
                Textarea::make('invoice_url')
                    ->columnSpanFull(),
                TextInput::make('external_invoice_id'),
                TextInput::make('external_checkout_id'),
                TextInput::make('external_order_id'),
                TextInput::make('external_payment_id'),
            ]);
    }
}
