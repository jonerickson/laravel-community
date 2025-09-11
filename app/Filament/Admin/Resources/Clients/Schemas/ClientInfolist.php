<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Clients\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('OAuth Client Information')
                    ->columns()
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')
                            ->columnSpanFull(),
                        TextEntry::make('id')
                            ->label('Client ID')
                            ->copyable(),
                        TextEntry::make('secret')
                            ->label('Client Secret')
                            ->copyable(),
                        IconEntry::make('revoked')
                            ->columnSpanFull()
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
                Section::make('Endpoints')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('authorize')
                            ->copyable()
                            ->getStateUsing(fn () => route('passport.authorizations.authorize')),
                        TextEntry::make('token')
                            ->copyable()
                            ->getStateUsing(fn () => route('passport.token')),
                        TextEntry::make('refresh')
                            ->copyable()
                            ->getStateUsing(fn () => route('passport.token.refresh')),
                    ]),
            ]);
    }
}
