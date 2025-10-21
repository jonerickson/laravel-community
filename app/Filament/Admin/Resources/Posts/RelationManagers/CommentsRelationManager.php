<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\RelationManagers;

use App\Models\Comment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $recordTitleAttribute = 'content';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Textarea::make('content')
                    ->required()
                    ->maxLength(1000)
                    ->rows(4)
                    ->helperText('The content of the comment.'),
                Select::make('parent_id')
                    ->label('Reply to Comment')
                    ->options(fn (RelationManager $livewire) => Comment::query()
                        ->where('commentable_type', $livewire->getOwnerRecord()::class)
                        ->where('commentable_id', $livewire->getOwnerRecord()->id)
                        ->whereNull('parent_id')
                        ->pluck('content', 'id')
                        ->map(fn (string $content): string => strlen($content) > 50 ? substr($content, 0, 50).'...' : $content))
                    ->searchable()
                    ->nullable()
                    ->helperText('Select a parent comment if this is a reply.'),
                Toggle::make('is_approved')
                    ->label('Approved')
                    ->default(false)
                    ->helperText('Approve this comment to make it visible to users.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Comments and replies for this blog post.')
            ->emptyStateDescription('No comments have been posted for this blog post yet.')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['author', 'parent']))
            ->columns([
                TextColumn::make('content')
                    ->limit(100)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= 100) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved')
                    ->sortable(),
                TextColumn::make('parent.content')
                    ->label('Reply To')
                    ->limit(30)
                    ->placeholder('Top-Level Comment')
                    ->toggleable(),
                TextColumn::make('replies_count')
                    ->label('Replies')
                    ->counts('replies')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->trueLabel('Approved only')
                    ->falseLabel('Pending approval')
                    ->native(false),
                Filter::make('top_level')
                    ->label('Top-level comments only')
                    ->query(fn (Builder $query): Builder => $query->whereNull('parent_id')),
                Filter::make('replies')
                    ->label('Replies only')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('parent_id')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Comment $record): Comment => $record->approve())
                    ->visible(fn (Comment $record): bool => ! $record->is_approved)
                    ->requiresConfirmation()
                    ->modalHeading('Approve Comment')
                    ->modalDescription('Are you sure you want to approve this comment? It will be visible to all users.')
                    ->modalSubmitActionLabel('Approve'),
                Action::make('unapprove')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(fn (Comment $record): Comment => $record->unapprove())
                    ->visible(fn (Comment $record): bool => $record->is_approved)
                    ->requiresConfirmation()
                    ->modalHeading('Unapprove Comment')
                    ->modalDescription('Are you sure you want to unapprove this comment? It will be hidden from users.')
                    ->modalSubmitActionLabel('Unapprove'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(function ($record): void {
                                $record->approve();
                            });
                        }),
                    BulkAction::make('unapprove')
                        ->label('Unapprove Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(function ($record): void {
                                $record->unapprove();
                            });
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
