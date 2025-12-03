<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeBaseArticles\Tables;

use App\Enums\KnowledgeBaseArticleType;
use App\Models\KnowledgeBaseArticle;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class KnowledgeBaseArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateDescription('There are no articles to display. Create one to get started.')
            ->columns([
                ImageColumn::make('featured_image')
                    ->grow(false)
                    ->alignCenter()
                    ->label('')
                    ->imageSize(60)
                    ->square(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->placeholder('No Category')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_published')
                    ->boolean()
                    ->sortable()
                    ->label('Published'),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('Publish Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('reading_time')
                    ->label('Read Time')
                    ->suffix(' min')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(KnowledgeBaseArticleType::class)
                    ->multiple()
                    ->label('Type'),
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Category'),
                TernaryFilter::make('is_published')
                    ->default()
                    ->label('Published'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (KnowledgeBaseArticle $record): string => $record->url ?? '#'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn (KnowledgeBaseArticle $record) => $record->update(['is_published' => true]));
                        }),
                    BulkAction::make('unpublish')
                        ->icon('heroicon-o-x-mark')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn (KnowledgeBaseArticle $record) => $record->update(['is_published' => false]));
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->groups(['type'])
            ->defaultGroup('type')
            ->defaultSort('created_at', 'desc');
    }
}
