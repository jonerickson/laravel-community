<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PostResource\Pages;
use App\Filament\Admin\Resources\PostResource\RelationManagers;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationGroup = 'Blog';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Forms\Components\Section::make('Post Content')
                            ->columns()
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($operation, $state, Forms\Set $set): void {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                        }
                                    }),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Forms\Components\Textarea::make('excerpt')
                                    ->columnSpanFull()
                                    ->maxLength(500)
                                    ->helperText('Brief description of the post (optional).'),
                                Forms\Components\RichEditor::make('content')
                                    ->required()
                                    ->columnSpanFull()
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
                        Forms\Components\Section::make('Media')
                            ->schema([
                                Forms\Components\FileUpload::make('featured_image')
                                    ->label('Featured Image')
                                    ->disk('public')
                                    ->directory('posts/featured-images')
                                    ->visibility('public')
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
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Publishing')
                            ->columns()
                            ->schema([
                                Forms\Components\Toggle::make('is_published')
                                    ->default(false)
                                    ->helperText('Publish this post immediately.'),
                                Forms\Components\Toggle::make('is_featured')
                                    ->default(false)
                                    ->helperText('Feature this post on the homepage.'),
                                Forms\Components\Toggle::make('comments_enabled')
                                    ->default(true)
                                    ->helperText('Allow users to comment on this post.'),
                                Forms\Components\DateTimePicker::make('published_at')
                                    ->columnSpanFull()
                                    ->native(false)
                                    ->helperText('Schedule when this post should be published.')
                                    ->default(now()),
                                Forms\Components\Hidden::make('created_by')
                                    ->default(Auth::id()),
                            ]),
                        Forms\Components\Section::make('SEO & Meta')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Forms\Components\KeyValue::make('metadata')
                                    ->keyLabel('Meta Key')
                                    ->valueLabel('Meta Value')
                                    ->helperText('Additional metadata for the post (SEO, tags, etc.).'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('Image')
                    ->disk('public')
                    ->size(60)
                    ->square(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
                Tables\Columns\IconColumn::make('comments_enabled')
                    ->boolean()
                    ->label('Comments'),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reading_time')
                    ->label('Read Time')
                    ->suffix(' min')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Publication Status'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured Status'),
                Tables\Filters\TernaryFilter::make('comments_enabled')
                    ->label('Comments Status'),
                Tables\Filters\Filter::make('published')
                    ->query(fn (Builder $query): Builder => $query->published()),
                Tables\Filters\Filter::make('drafts')
                    ->query(fn (Builder $query): Builder => $query->where('is_published', false)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Post $record) => $record->url, shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-eye')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'is_published' => true,
                                    'published_at' => $record->published_at ?? now(),
                                ]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('Unpublish Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_published' => false]);
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

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
