<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Warnings\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarningForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Warning Information')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('points')
                            ->label('Warning Points')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Number of points to assign when this warning is issued.'),
                        TextInput::make('days_applied')
                            ->label('Duration (Days)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Number of days before the warning points expire.'),
                        Toggle::make('is_active')
                            ->helperText('Allow this warning to be issued.')
                            ->label('Active')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
