<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Clients\Schemas;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('OAuth Client Information')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->helperText('The name of the client.')
                            ->maxLength(255)
                            ->required(),
                        Repeater::make('redirect_uris')
                            ->required()
                            ->helperText('Authorized URLs the application redirect the user back to after successful authentication.')
                            ->label('Redirect URLs')
                            ->addActionLabel('Add redirect URL')
                            ->simple(TextInput::make('redirect_uri')
                                ->label('Redirect URL')
                                ->maxLength(255)
                                ->required()
                                ->url()),
                        Radio::make('grant_types')
                            ->visible(fn ($operation): bool => $operation === 'create')
                            ->helperText('The grant type authorized by the client.')
                            ->label('Grant Type')
                            ->required()
                            ->options([
                                'authorization_code' => 'Authorization Code',
                                'password' => 'Password Grant',
                                'client_credentials' => 'Client Credentials',
                                'implicit' => 'Implicit Grant',
                            ])
                            ->columnSpanFull(),
                        Toggle::make('revoked')
                            ->helperText('Revoke access to the client.')
                            ->required(),
                    ]),
            ]);
    }
}
