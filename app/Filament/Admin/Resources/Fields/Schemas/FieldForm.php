<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Fields\Schemas;

use App\Enums\FieldType;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class FieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Field Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('The internal field name.'),
                                Forms\Components\TextInput::make('label')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('The public display label shown to users.'),
                                Forms\Components\Select::make('type')
                                    ->columnSpanFull()
                                    ->required()
                                    ->options(FieldType::class)
                                    ->default(FieldType::Text)
                                    ->live()
                                    ->helperText('The field input type.'),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->helperText('An optional description or help text.'),
                        Forms\Components\KeyValue::make('options')
                            ->visible(fn (Get $get): bool => $get('type') === FieldType::Select)
                            ->helperText('Options for select fields (key => label)'),
                        Grid::make()
                            ->schema([
                                Forms\Components\Toggle::make('is_required')
                                    ->default(false)
                                    ->helperText('Is this field required for users?'),
                                Forms\Components\Toggle::make('is_public')
                                    ->default(true)
                                    ->helperText('Show this field on public profiles?'),
                            ]),
                    ]),
            ]);
    }
}
