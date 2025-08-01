<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ForumResource\Pages;
use App\Filament\Admin\Resources\ForumResource\RelationManagers\TopicsRelationManager;
use App\Models\Forum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ForumResource extends Resource
{
    protected static ?string $model = Forum::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Forums';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Forum Information')
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->helperText('The name of the forum.')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, Forms\Set $set) => $context === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(Forum::class, 'slug', ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('URL-friendly version of the name'),
                        Forms\Components\Textarea::make('description')
                            ->helperText('A helpful description on what the forum is about.')
                            ->columnSpanFull()
                            ->maxLength(500)
                            ->rows(3),
                        Forms\Components\RichEditor::make('rules')
                            ->columnSpanFull()
                            ->nullable()
                            ->helperText('Optional rules to display at the top of the forum.'),
                        Forms\Components\TextInput::make('icon')
                            ->maxLength(255)
                            ->helperText('Icon class or emoji'),
                        Forms\Components\ColorPicker::make('color')
                            ->required()
                            ->default('#3b82f6'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\ColorColumn::make('color')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                Tables\Columns\TextColumn::make('topics_count')
                    ->label('Topics')
                    ->counts('topics')
                    ->sortable(),
                Tables\Columns\TextColumn::make('posts_count')
                    ->label('Posts')
                    ->getStateUsing(fn (Forum $record): int => $record->posts_count)
                    ->sortable(),
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active forums only')
                    ->falseLabel('Inactive forums only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Forum $record): string => route('forums.show', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),
                ]),
            ])
            ->reorderable()
            ->defaultSort('order');
    }

    public static function getRelations(): array
    {
        return [
            TopicsRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListForums::route('/'),
            'create' => Pages\CreateForum::route('/create'),
            'edit' => Pages\EditForum::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
