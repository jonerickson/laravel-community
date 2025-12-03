<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('stripe_id')
                    ->required(),
                TextInput::make('stripe_status')
                    ->required(),
                TextInput::make('stripe_price'),
                TextInput::make('quantity')
                    ->numeric(),
                DateTimePicker::make('trial_ends_at'),
                DateTimePicker::make('ends_at'),
            ]);
    }
}
