<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Topics;

use App\Filament\Admin\Resources\Topics\Pages\CreateTopic;
use App\Filament\Admin\Resources\Topics\Pages\EditTopic;
use App\Filament\Admin\Resources\Topics\Pages\ListTopics;
use App\Models\Topic;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Override;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Topic Information')
                            ->columnSpanFull()
                            ->schema([
                                Select::make('forum_id')
                                    ->label('Forum')
                                    ->relationship('forum', 'name')
                                    ->preload()
                                    ->required()
                                    ->searchable(),
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->maxLength(500)
                                    ->rows(3),
                            ]),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Publishing')
                            ->columns(1)
                            ->schema([
                                Toggle::make('is_pinned')
                                    ->helperText('Toggle to pin/unpin this topic.')
                                    ->label('Pinned'),
                                Toggle::make('is_locked')
                                    ->helperText('Toggle to lock/unlock this topic.')
                                    ->label('Locked'),
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
                    ]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->icons(function (Topic $record): array {
                        $icons = [];

                        if ($record->is_pinned) {
                            $icons[] = Heroicon::OutlinedPaperClip;
                        }
                        if ($record->is_locked) {
                            $icons[] = Heroicon::OutlinedLockClosed;
                        }

                        return $icons;
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('posts_count')
                    ->label('Posts')
                    ->numeric()
                    ->sortable(),
                ToggleColumn::make('is_pinned')
                    ->sortable()
                    ->label('Pinned'),
                ToggleColumn::make('is_locked')
                    ->sortable()
                    ->label('Locked'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('author')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->relationship('author', 'name'),
                SelectFilter::make('forum')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->relationship('forum', 'name'),
                TernaryFilter::make('is_locked')
                    ->label('Locked'),
                TernaryFilter::make('is_pinned')
                    ->label('Pinned'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultPaginationPageOption('all')
            ->defaultGroup('forum.name')
            ->defaultSort('created_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTopics::route('/'),
            'create' => CreateTopic::route('/create'),
            'edit' => EditTopic::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
