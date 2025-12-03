<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SubscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('type'),
                TextEntry::make('stripe_id'),
                TextEntry::make('stripe_status'),
                TextEntry::make('stripe_price')
                    ->placeholder('-'),
                TextEntry::make('quantity')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('trial_ends_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('ends_at')
                    ->dateTime()
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
