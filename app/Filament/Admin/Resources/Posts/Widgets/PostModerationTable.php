<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\Widgets;

use App\Models\Post;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PostModerationTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Post::query()
                    ->where(function (Builder $query): void {
                        $query
                            ->unpublished()
                            ->orWhereHas('pendingReports');
                    })
                    ->with(['author', 'pendingReports'])
                    ->withCount('pendingReports')
                    ->latest()
                    ->limit(20)
            )
            ->heading('Posts Needing Moderation')
            ->description('Unpublished posts and posts with pending reports.')
            ->columns([
                TextColumn::make('title')
                    ->sortable()
                    ->label('Post Title')
                    ->searchable()
                    ->limit(50)
                    ->url(fn (Post $record): ?string => $record->getUrl(), shouldOpenInNewTab: true),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable(),
                TextColumn::make('is_published')
                    ->sortable()
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Published' : 'Unpublished')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                TextColumn::make('pending_reports_count')
                    ->label('Pending Reports')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state : '-')
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->sortable()
                    ->label('Created')
                    ->dateTimeTooltip()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
