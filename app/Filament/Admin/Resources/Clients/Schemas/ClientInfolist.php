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
                        IconEntry::make('revoked')
                            ->columnSpanFull()
                            ->boolean(),
                        TextEntry::make('grant_types')
                            ->label('Grant Types')
                            ->badge(),
                    ]),
                Section::make('Endpoints')
                    ->description('The OAuth endpoints your application can use to interact with the API.')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('authorize')
                            ->badge()
                            ->copyable()
                            ->state(fn (): string => route('passport.authorizations.authorize')),
                        TextEntry::make('token')
                            ->badge()
                            ->copyable()
                            ->state(fn (): string => route('passport.token')),
                        TextEntry::make('refresh')
                            ->badge()
                            ->copyable()
                            ->state(fn (): string => route('passport.token.refresh')),
                    ]),
            ]);
    }
}
