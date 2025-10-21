<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\Widgets;

use App\Filament\Admin\Resources\Posts\Actions\ApproveAction;
use App\Filament\Admin\Resources\Posts\Actions\PublishAction;
use App\Filament\Admin\Resources\Reports\Pages\ListReports;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PostModerationTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(Post::query()->needingModeration())
            ->heading('Posts Needing Moderation')
            ->description('Unpublished, pending approval or posts with pending reports.')
            ->deferLoading()
            ->columns([
                TextColumn::make('title')
                    ->sortable()
                    ->label('Post Title')
                    ->limit(50)
                    ->url(fn (Post $record): ?string => $record->getUrl(), shouldOpenInNewTab: true),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->url(fn (Post $record): ?string => $record->author ? EditUser::getUrl(['record' => $record->author]) : null),
                TextColumn::make('is_approved')
                    ->sortable()
                    ->label('Approval Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Approved' : 'Pending Approval')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                TextColumn::make('is_published')
                    ->sortable()
                    ->label('Published Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Published' : 'Unpublished')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                TextColumn::make('pending_reports_count')
                    ->label('Pending Reports')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state : '-')
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->sortable()
                    ->dateTimeTooltip()
                    ->since(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Post $record) => $record->url, shouldOpenInNewTab: true),
                ApproveAction::make(),
                PublishAction::make(),
                Action::make('reports')
                    ->label('Reports')
                    ->color('warning')
                    ->icon(Heroicon::OutlinedFlag)
                    ->visible(fn (Post $record): bool => $record->reports->count() > 0)
                    ->url(fn (Post $record): string => ListReports::getUrl(), shouldOpenInNewTab: true),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
