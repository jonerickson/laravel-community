<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Forums;

use App\Filament\Admin\Resources\Forums\Pages\CreateForum;
use App\Filament\Admin\Resources\Forums\Pages\EditForum;
use App\Filament\Admin\Resources\Forums\Pages\ListForums;
use App\Filament\Admin\Resources\Forums\RelationManagers\TopicsRelationManager;
use App\Models\Forum;
use App\Models\Group as GroupModel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Override;

class ForumResource extends Resource
{
    protected static ?string $model = Forum::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Forum Information')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextInput::make('name')
                            ->helperText('The name of the forum.')
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
                            ->required()
                            ->searchable()
                            ->columnSpanFull()
                            ->preload()
                            ->relationship('category', 'name'),
                        Textarea::make('description')
                            ->helperText('A helpful description on what the forum is about.')
                            ->columnSpanFull()
                            ->maxLength(500)
                            ->rows(3),
                        RichEditor::make('rules')
                            ->columnSpanFull()
                            ->nullable()
                            ->helperText('Optional rules to display at the top of the forum.'),
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
                            ->default(fn (): Collection => collect([
                                ...GroupModel::query()->defaultGuestGroups()->pluck('id'),
                                ...GroupModel::query()->defaultMemberGroups()->pluck('id'),
                            ]))
                            ->relationship('groups', 'name')
                            ->preload()
                            ->searchable()
                            ->multiple()
                            ->helperText('The groups that are allowed to view this forum.'),
                    ]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('groups.name')
                    ->badge(),
                TextColumn::make('category.name')
                    ->sortable()
                    ->searchable(),
                ColorColumn::make('color')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('topics_count')
                    ->label('Topics')
                    ->counts('topics')
                    ->sortable(),
                TextColumn::make('posts_count')
                    ->label('Posts')
                    ->getStateUsing(fn (Forum $record): int => $record->posts_count)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->trueLabel('Active forums only')
                    ->falseLabel('Inactive forums only')
                    ->native(false),
            ])
            ->groups([
                Group::make('category.name')
                    ->titlePrefixedWithLabel(false),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Forum $record): string => route('forums.show', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),
                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),
                ]),
            ])
            ->reorderable('order')
            ->defaultSort('order')
            ->defaultGroup('category.name');
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            TopicsRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForums::route('/'),
            'create' => CreateForum::route('/create'),
            'edit' => EditForum::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
