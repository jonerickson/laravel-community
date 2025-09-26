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
                    ->columns(1)
                    ->schema([
                        TextInput::make('name')
                            ->maxLength(255)
                            ->required(),
                        Select::make('user_id')
                            ->preload()
                            ->searchable()
                            ->relationship('user', 'name')
                            ->required(),
                        TextInput::make('invoice_number')
                            ->maxLength(255)
                            ->nullable(),
                        Select::make('status')
                            ->default(OrderStatus::Pending)
                            ->searchable()
                            ->options(OrderStatus::class)
                            ->required(),
                    ]),
            ]);
    }
}
