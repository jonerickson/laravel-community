<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PostResource\RelationManagers;

use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $recordTitleAttribute = 'content';

    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->maxLength(1000)
                    ->rows(4)
                    ->helperText('The content of the comment.'),
                Forms\Components\Select::make('parent_id')
                    ->label('Reply to Comment')
                    ->options(function (RelationManager $livewire) {
                        return Comment::query()
                            ->where('commentable_type', get_class($livewire->getOwnerRecord()))
                            ->where('commentable_id', $livewire->getOwnerRecord()->id)
                            ->whereNull('parent_id')
                            ->pluck('content', 'id')
                            ->map(fn (string $content) => strlen($content) > 50 ? substr($content, 0, 50).'...' : $content);
                    })
                    ->searchable()
                    ->nullable()
                    ->helperText('Select a parent comment if this is a reply.'),
                Forms\Components\Toggle::make('is_approved')
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
                Tables\Columns\TextColumn::make('content')
                    ->limit(100)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= 100) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.content')
                    ->label('Reply To')
                    ->limit(30)
                    ->placeholder('Top-level comment')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('replies_count')
                    ->label('Replies')
                    ->counts('replies')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->trueLabel('Approved only')
                    ->falseLabel('Pending approval')
                    ->native(false),
                Tables\Filters\Filter::make('top_level')
                    ->label('Top-level comments only')
                    ->query(fn (Builder $query): Builder => $query->whereNull('parent_id')),
                Tables\Filters\Filter::make('replies')
                    ->label('Replies only')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('parent_id')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Comment $record) => $record->update(['is_approved' => true]))
                    ->visible(fn (Comment $record): bool => ! $record->is_approved)
                    ->requiresConfirmation()
                    ->modalHeading('Approve Comment')
                    ->modalDescription('Are you sure you want to approve this comment? It will be visible to all users.')
                    ->modalSubmitActionLabel('Approve'),
                Tables\Actions\Action::make('unapprove')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(fn (Comment $record) => $record->update(['is_approved' => false]))
                    ->visible(fn (Comment $record): bool => $record->is_approved)
                    ->requiresConfirmation()
                    ->modalHeading('Unapprove Comment')
                    ->modalDescription('Are you sure you want to unapprove this comment? It will be hidden from users.')
                    ->modalSubmitActionLabel('Unapprove'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_approved' => true]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('unapprove')
                        ->label('Unapprove Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_approved' => false]);
                            });
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
