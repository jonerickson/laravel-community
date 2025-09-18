<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextInput::make('reference_id')
                            ->copyable()
                            ->disabled()
                            ->label('Order Number'),
                        TextInput::make('invoice_number')
                            ->copyable()
                            ->disabled(),
                        Select::make('user_id')
                            ->preload()
                            ->searchable()
                            ->relationship('user', 'name')
                            ->required(),
                        Select::make('status')
                            ->default(OrderStatus::Pending)
                            ->searchable()
                            ->options(OrderStatus::class)
                            ->required(),
                        TextInput::make('amount')
                            ->numeric()
                            ->columnSpanFull()
                            ->suffix('USD')
                            ->prefix('$')
                            ->helperText('The amount in cents.'),

                    ]),
                Section::make('Payment Processor')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextInput::make('external_invoice_id')
                            ->disabled(fn ($operation) => $operation === 'edit')
                            ->label('External Invoice ID'),
                        TextInput::make('external_checkout_id')
                            ->disabled(fn ($operation) => $operation === 'edit')
                            ->label('External Checkout ID'),
                        TextInput::make('external_order_id')
                            ->disabled(fn ($operation) => $operation === 'edit')
                            ->label('External Order ID'),
                        TextInput::make('external_payment_id')
                            ->disabled(fn ($operation) => $operation === 'edit')
                            ->label('External Payment ID'),
                        TextInput::make('invoice_url')
                            ->label('Invoice URL')
                            ->url()
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
