<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts;

use App\Enums\PostType;
use App\Filament\Admin\Resources\Posts\Pages\CreatePost;
use App\Filament\Admin\Resources\Posts\Pages\EditPost;
use App\Filament\Admin\Resources\Posts\Pages\ListPosts;
use App\Filament\Admin\Resources\Posts\RelationManagers\CommentsRelationManager;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Override;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Post Content')
                            ->columns()
                            ->schema([
                                Hidden::make('type')
                                    ->default(PostType::Blog),
                                TextInput::make('title')
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
                                Textarea::make('excerpt')
                                    ->columnSpanFull()
                                    ->maxLength(500)
                                    ->helperText('Brief description of the post (optional). If none is provided, the beginning of the post will be used as the excerpt.'),
                                RichEditor::make('content')
                                    ->required()
                                    ->columnSpanFull()
                                    ->helperText('The main post content.')
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
                                    ->directory('blog')
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
                                    ->helperText('Upload a featured image for the post.'),
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
                                    ->getStateUsing(fn (Post $record): string => route('blog.show', $record->slug))
                                    ->copyable()
                                    ->suffixAction(fn (Post $record): Action => Action::make('open')
                                        ->url(route('blog.show', $record->slug), shouldOpenInNewTab: true)
                                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                                    ),
                            ]),
                        Section::make('Publishing')
                            ->columns(1)
                            ->schema([
                                Toggle::make('is_featured')
                                    ->label('Featured')
                                    ->default(false)
                                    ->helperText('Feature this post on the homepage.'),
                                Toggle::make('comments_enabled')
                                    ->label('Comments')
                                    ->default(true)
                                    ->helperText('Allow users to comment on this post.'),
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(true)
                                    ->live()
                                    ->helperText('Publish this post immediately.'),
                                DateTimePicker::make('published_at')
                                    ->label('Publish Date')
                                    ->columnSpanFull()
                                    ->native(false)
                                    ->helperText('Schedule when this post should be published. Leave blank to keep the post in a draft state.')
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
                                    ->keyLabel('Meta Key')
                                    ->valueLabel('Meta Value')
                                    ->helperText('Additional metadata for the post (SEO, tags, etc.).'),
                            ]),
                    ]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateDescription('There are no posts to display. Create one to get started.')
            ->columns([
                ImageColumn::make('featured_image')
                    ->grow(false)
                    ->alignCenter()
                    ->label('')
                    ->imageSize(60)
                    ->square(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                IconColumn::make('is_published')
                    ->boolean()
                    ->sortable()
                    ->label('Published'),
                IconColumn::make('is_featured')
                    ->boolean()
                    ->sortable()
                    ->label('Featured'),
                ToggleColumn::make('comments_enabled')
                    ->sortable()
                    ->label('Comments'),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('Publish Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('reading_time')
                    ->label('Read Time')
                    ->suffix(' min')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('author')
                    ->relationship('author', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                TernaryFilter::make('comments_enabled')
                    ->label('Comments'),
                TernaryFilter::make('is_featured')
                    ->label('Featured'),
                TernaryFilter::make('is_published')
                    ->label('Published'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Post $record) => $record->url, shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-eye')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(function ($record): void {
                                $record->publish();
                            });
                        }),
                    BulkAction::make('unpublish')
                        ->label('Unpublish Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(function ($record): void {
                                $record->unpublish();
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->blog();
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_published', false)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getNavigationBadge();

        return match (true) {
            $count > 10 => 'warning',
            $count > 0 => 'primary',
            default => null,
        };
    }
}
