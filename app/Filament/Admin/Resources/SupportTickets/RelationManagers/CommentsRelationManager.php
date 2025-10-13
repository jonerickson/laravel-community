<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Override;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Replies';

    protected static ?string $recordTitleAttribute = 'content';

    #[Override]
    public function isReadOnly(): bool
    {
        return false;
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\RichEditor::make('content')
                    ->required()
                    ->label('Reply')
                    ->hiddenLabel()
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    #[Override]
    public function table(Table $table): Table
    {
        return $table
            ->description('The replies belonging to this support ticket.')
            ->emptyStateDescription('No replies yet.')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('content')
                    ->label('Reply')
                    ->html()
                    ->searchable(),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Posted')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('M d, Y g:i A')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('author')
                    ->relationship('author', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalHeading('Add Reply')
                    ->modalDescription('Add a new reply to this support ticket.'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
