<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Schemas;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Page Content')
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $context, $state, Set $set): mixed => $context === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->disabledOn('edit')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->rules(['alpha_dash']),
                                Textarea::make('description')
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('HTML Content')
                            ->columnSpanFull()
                            ->schema([
                                CodeEditor::make('html_content')
                                    ->hiddenLabel()
                                    ->required()
                                    ->columnSpanFull()
                                    ->helperText('The HTML content of the page.')
                                    ->language(CodeEditor\Enums\Language::Html),
                            ]),
                        Section::make('Custom Styles & Scripts')
                            ->columnSpanFull()
                            ->schema([
                                CodeEditor::make('css_content')
                                    ->label('CSS')
                                    ->columnSpanFull()
                                    ->helperText('Custom CSS styles for this page.')
                                    ->language(CodeEditor\Enums\Language::Css),
                                CodeEditor::make('js_content')
                                    ->label('JavaScript')
                                    ->columnSpanFull()
                                    ->helperText('Custom JavaScript for this page.')
                                    ->language(CodeEditor\Enums\Language::JavaScript),
                            ]),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Publishing')
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(false),
                                DateTimePicker::make('published_at')
                                    ->label('Publish Date'),
                            ]),
                        Section::make('Navigation')
                            ->schema([
                                Toggle::make('show_in_navigation')
                                    ->label('Show in Navigation')
                                    ->default(false)
                                    ->helperText('Display this page in the site navigation.'),
                                TextInput::make('navigation_label')
                                    ->label('Label')
                                    ->maxLength(255)
                                    ->helperText('Optional custom label for navigation (uses title if empty).'),
                                TextInput::make('navigation_order')
                                    ->label('Order')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Order in which this page appears in navigation.'),
                            ]),
                    ]),
            ]);
    }
}
