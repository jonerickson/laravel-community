<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumCategories\Schemas;

use App\Models\ForumCategory;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
                                ->maxLength(65535)
                                ->rows(3),
                            TextInput::make('icon')
                                ->maxLength(255)
                                ->helperText('Icon class or emoji.'),
                            ColorPicker::make('color')
                                ->required()
                                ->default('#3b82f6'),
                        ]),
                        Section::make('Media')
                            ->columnSpanFull()
                            ->schema([
                                FileUpload::make('featured_image')
                                    ->label('Featured Image')
                                    ->helperText('Add a category image to be displayed on the forum index.')
                                    ->directory('forums/categories')
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
                                TextEntry::make('url')
                                    ->label('URL')
                                    ->getStateUsing(fn (ForumCategory $record): string => route('forums.categories.show', $record->slug))
                                    ->copyable()
                                    ->suffixAction(fn (ForumCategory $record): Action => Action::make('open')
                                        ->url(route('forums.categories.show', $record->slug), shouldOpenInNewTab: true)
                                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                                    ),
                            ]),
                        Section::make('Publishing')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->helperText('Allow the category to be accessed.')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }
}
