<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\RelationManagers;

use App\Managers\SupportTicketManager;
use App\Models\Comment;
use App\Models\SupportTicket;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
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
                    ->columnSpanFull()
                    ->helperText(fn (?Comment $record): ?string => is_null($record)
                        ? null
                        : sprintf('%s replied %s', $record->author->name, $record->created_at->format('M j, Y \a\t g:i A'))),
            ]);
    }

    #[Override]
    public function table(Table $table): Table
    {
        return $table
            ->description('The replies belonging to this support ticket.')
            ->emptyStateHeading('No replies')
            ->emptyStateDescription('No replies yet added for this support ticket.')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('author')
                    ->grow(false)
                    ->width(1)
                    ->alignCenter()
                    ->label('')
                    ->badge()
                    ->getStateUsing(function (Comment $record): string {
                        /** @var SupportTicket $ticket */
                        $ticket = $this->getOwnerRecord();

                        return $record->created_by === $ticket->created_by
                            ? 'Customer'
                            : 'Agent';
                    }),
                Tables\Columns\TextColumn::make('content')
                    ->limit()
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
                    ->tooltip(fn (Comment $record) => $record->created_at->format('M d, Y g:i A')),
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
                    ->label('New reply')
                    ->modalHeading('Add Reply')
                    ->modalDescription('Add a new reply to this support ticket.')
                    ->using(function (array $data) {
                        /** @var SupportTicket $ticket */
                        $ticket = $this->getOwnerRecord();

                        return app(SupportTicketManager::class)->addComment(
                            ticket: $ticket,
                            content: data_get($data, 'content'),
                            userId: Auth::id(),
                        );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('View Reply'),
                EditAction::make()
                    ->modalHeading('Edit Reply'),
                DeleteAction::make()
                    ->modalHeading('Delete Reply'),
            ]);
    }
}
