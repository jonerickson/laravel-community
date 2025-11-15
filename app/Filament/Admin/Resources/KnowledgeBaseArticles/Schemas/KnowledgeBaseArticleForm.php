<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeBaseArticles\Schemas;

use App\Enums\KnowledgeBaseArticleType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class KnowledgeBaseArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Article content')
                            ->columns()
                            ->schema([
                                TextInput::make('title')
                                    ->helperText('The title of the article.')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $context, $state, Set $set): mixed => $context === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->disabledOn('edit')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('A SEO friendly title.')
                                    ->unique(ignoreRecord: true)
                                    ->rules(['alpha_dash']),
                                Select::make('category_id')
                                    ->columnSpanFull()
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->helperText('Assign this article to a category.'),
                                Textarea::make('excerpt')
                                    ->columnSpanFull()
                                    ->maxLength(500)
                                    ->helperText('Brief description of the article (optional). If none is provided, the beginning of the article will be used as the excerpt.'),
                                RichEditor::make('content')
                                    ->required()
                                    ->columnSpanFull()
                                    ->helperText('The main article content.')
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'strike',
                                        'link',
                                        'bulletList',
                                        'orderedList',
                                        'h2',
                                        'h3',
                                        'blockquote',
                                        'codeBlock',
                                    ]),
                            ]),
                        Section::make('Media')
                            ->schema([
                                FileUpload::make('featured_image')
                                    ->label('Featured Image')
                                    ->directory('knowledge-base/articles')
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
                                    ])
                                    ->helperText('Upload a featured image for the article.'),
                            ]),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Publishing')
                            ->columns(1)
                            ->schema([
                                Radio::make('type')
                                    ->options(KnowledgeBaseArticleType::class)
                                    ->required()
                                    ->default(KnowledgeBaseArticleType::Guide)
                                    ->helperText('Select the type of article.'),
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(false)
                                    ->live()
                                    ->helperText('Publish this article immediately.'),
                                DateTimePicker::make('published_at')
                                    ->label('Publish Date')
                                    ->columnSpanFull()
                                    ->native(false)
                                    ->helperText('Schedule when this article should be published. Leave blank to keep the article in a draft state.')
                                    ->default(now()),
                                Hidden::make('created_by')
                                    ->default(Auth::id()),
                            ]),
                        Section::make('Author')
                            ->columnSpanFull()
                            ->collapsed()
                            ->schema([
                                Select::make('created_by')
                                    ->relationship('author', 'name')
                                    ->required()
                                    ->default(Auth::id())
                                    ->preload()
                                    ->searchable(),
                            ]),
                        Section::make('SEO & Meta')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                KeyValue::make('metadata')
                                    ->keyLabel('Meta key')
                                    ->valueLabel('Meta value')
                                    ->helperText('Additional metadata for the article (SEO, tags, etc.).'),
                            ]),
                    ]),
            ]);
    }
}
