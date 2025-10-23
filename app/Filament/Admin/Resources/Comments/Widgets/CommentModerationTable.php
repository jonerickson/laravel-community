<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Comments\Widgets;

use App\Filament\Admin\Resources\Comments\Actions\ApproveAction;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\Comment;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class CommentModerationTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(Comment::query()->unapproved())
            ->heading('Comments Needing Moderation')
            ->description('Unpublished, pending approval or comments with pending reports.')
            ->deferLoading()
            ->columns([
                TextColumn::make('content')
                    ->sortable()
                    ->html()
                    ->label('Comment')
                    ->limit(50),
                TextColumn::make('commentable.name')
                    ->getStateUsing(fn (Comment $record) => $record->commentable && method_exists($record->commentable, 'getLabel') ? $record->commentable->getLabel() : null)
                    ->placeholder('Unknown')
                    ->label('Parent Item')
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->url(fn (Comment $record): ?string => $record->author ? EditUser::getUrl(['record' => $record->author]) : null),
                TextColumn::make('is_approved')
                    ->sortable()
                    ->label('Approval Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Approved' : 'Pending Approval')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->sortable()
                    ->dateTimeTooltip()
                    ->since(),
            ])
            ->recordActions([
                ApproveAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
