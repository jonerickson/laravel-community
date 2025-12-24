<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTicketCategories\Schemas;

use Filament\Forms;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupportTicketCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Category Information')
                            ->columnSpanFull()
                            ->columns()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true),
                                Forms\Components\TextInput::make('slug')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(65535)
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\ColorPicker::make('color')
                                    ->columnSpanFull()
                                    ->helperText('Used for badge colors in the UI.'),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Details')
                            ->visibleOn('edit')
                            ->components([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->since()
                                    ->dateTimeTooltip(),
                                TextEntry::make('updated_at')
                                    ->label('Updated')
                                    ->since()
                                    ->dateTimeTooltip(),
                            ]),
                    ]),
            ]);
    }
}
