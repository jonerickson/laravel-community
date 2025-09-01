<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role')
                    ->columnSpanFull()
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The name of the role.'),
                    ]),
            ]);
    }
}
