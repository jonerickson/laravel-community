<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Whitelists\Schemas;

use App\Enums\FilterType;
use App\Models\Fingerprint;
use App\Models\User;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class WhitelistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Whitelist Information')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Radio::make('filter')
                            ->required()
                            ->live()
                            ->default(FilterType::String)
                            ->options(FilterType::class),
                        TextInput::make('content')
                            ->visible(fn (Get $get): bool => $get('filter') === FilterType::String)
                            ->maxLength(255)
                            ->live()
                            ->helperText('The content that should be whitelisted.')
                            ->required(),
                        TextInput::make('content')
                            ->label('IP Address')
                            ->validationAttribute('IP address')
                            ->visible(fn (Get $get): bool => $get('filter') === FilterType::IpAddress)
                            ->maxLength(255)
                            ->helperText('Whitelist a specific IP address.')
                            ->ip()
                            ->required(),
                        MorphToSelect::make('resource')
                            ->label(fn (Get $get): string => $get('filter')->getLabel())
                            ->visible(fn (Get $get): bool => in_array($get('filter'), [FilterType::Fingerprint, FilterType::User]))
                            ->contained(false)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->types([
                                MorphToSelect\Type::make(User::class)
                                    ->titleAttribute('name'),
                                MorphToSelect\Type::make(Fingerprint::class)
                                    ->titleAttribute('fingerprint_id'),
                            ]),
                        Textarea::make('description')
                            ->maxLength(65535)
                            ->helperText('An optional description of the whitelist entry.')
                            ->nullable(),
                    ]),
            ]);
    }
}
