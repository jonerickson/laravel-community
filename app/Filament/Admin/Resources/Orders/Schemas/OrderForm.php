<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Schemas;

use App\Enums\BillingReason;
use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Select::make('user_id')
                            ->preload()
                            ->searchable()
                            ->default(fn () => request()->query('user_id'))
                            ->relationship('user', 'name')
                            ->required(),
                        TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->maxLength(255)
                            ->nullable(),
                        Select::make('status')
                            ->default(OrderStatus::Pending)
                            ->searchable()
                            ->options(OrderStatus::class)
                            ->required(),
                        Radio::make('billing_reason')
                            ->default(BillingReason::Manual)
                            ->label('Billing Reason')
                            ->options(BillingReason::class)
                            ->required(),
                    ]),
                Section::make('Payment Information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('amount_due')
                            ->helperText('The total amount owed for the order.')
                            ->label('Due')
                            ->default(fn (?Order $record) => $record->amount_subtotal ?? 0)
                            ->required()
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->suffix('USD'),
                        TextInput::make('amount_paid')
                            ->helperText('The amount paid-to-date.')
                            ->label('Paid')
                            ->default(0)
                            ->required()
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->suffix('USD'),
                        TextInput::make('amount_overpaid')
                            ->helperText('Any overpaid amount.')
                            ->label('Overpaid')
                            ->default(0)
                            ->required()
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->suffix('USD'),
                        TextInput::make('amount_remaining')
                            ->helperText('Any remaining amount.')
                            ->label('Remaining')
                            ->default(0)
                            ->required()
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->suffix('USD'),
                    ]),
            ]);
    }
}
