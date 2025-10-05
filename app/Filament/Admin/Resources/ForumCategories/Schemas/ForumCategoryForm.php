<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumCategories\Schemas;

use App\Models\Group as GroupModel;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ForumCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([Section::make('Category Information')
                        ->columnSpanFull()
                        ->columns()
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->helperText('The name of the forum category.')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (string $context, $state, Set $set): mixed => $context === 'create' ? $set('slug', Str::slug($state)) : null),                            TextInput::make('slug')
                                ->disabledOn('edit')
                                ->required()
                                ->maxLength(255)
                                ->helperText('A SEO friendly title.')
                                ->unique(ignoreRecord: true)
                                ->rules(['alpha_dash']),
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
                        Section::make('Image')
                            ->columnSpanFull()
                            ->relationship('image')
                            ->schema([
                                FileUpload::make('path')
                                    ->helperText('Add a category image to be displayed on the forum index.')
                                    ->hiddenLabel()
                                    ->disk('public')
                                    ->directory('forum-category-images')
                                    ->visibility('public')
                                    ->downloadable()
                                    ->previewable()
                                    ->openable()
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ]),
                            ]),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Publishing')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->helperText('Enable the category for viewing.')
                                    ->required()
                                    ->default(true),
                            ]),
                        Section::make('Permissions')
                            ->columnSpanFull()
                            ->schema([
                                Select::make('groups')
                                    ->default(fn () => collect([
                                        ...GroupModel::query()->defaultGuestGroups()->pluck('name', 'id'),
                                        ...GroupModel::query()->defaultMemberGroups()->pluck('name', 'id'),
                                    ]))
                                    ->relationship('groups', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->multiple()
                                    ->helperText('The groups that are allowed to view this forum category.'),
                            ]),
                    ]),
            ]);
    }
}
