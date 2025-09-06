<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumCategories\Schemas;

use App\Models\ForumCategory;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ForumCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Information')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The name of the forum category.'),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ForumCategory::class, 'slug', ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('URL-friendly version of the name.'),
                        Textarea::make('description')
                            ->helperText('A helpful description on what the forum is about.')
                            ->columnSpanFull()
                            ->maxLength(500)
                            ->rows(3),
                        TextInput::make('icon')
                            ->maxLength(255)
                            ->helperText('Icon class or emoji.'),
                        ColorPicker::make('color')
                            ->required()
                            ->default('#3b82f6'),
                    ]),
                Section::make('Permissions')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('groups')
                            ->relationship('groups', 'name')
                            ->preload()
                            ->searchable()
                            ->multiple()
                            ->helperText('The groups that are allowed to view this forum category.'),
                    ]),
            ]);
    }
}
