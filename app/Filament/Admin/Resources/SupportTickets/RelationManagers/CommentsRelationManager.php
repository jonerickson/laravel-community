<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\RelationManagers;

use App\Filament\Admin\Resources\SupportTickets\Actions\AddCommentAction;
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

    protected static ?string $title = 'Ticket Replies';

    protected static ?string $recordTitleAttribute = 'content';

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\RichEditor::make('content')
                    ->required()
                    ->label('Reply')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_approved')
                    ->label('Approved')
                    ->default(true)
                    ->helperText('Unapproved comments will not be visible to the customer'),
            ]);
    }

    #[Override]
    public function table(Table $table): Table
    {
        return $table
            ->emptyStateDescription('No replies yet.')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('content')
                    ->label('Reply')
                    ->html()
                    ->limit(100)
                    ->searchable()
                    ->tooltip(fn ($record): string => strip_tags((string) $record->content)),
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
                AddCommentAction::make()
                    ->supportTicket(fn (): \Illuminate\Database\Eloquent\Model => $this->getOwnerRecord()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
