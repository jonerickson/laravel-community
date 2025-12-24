<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\RelationManagers;

use App\Filament\Admin\Resources\Comments\Actions\ReplyAction;
use App\Models\Comment;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    protected static string|null|BackedEnum $icon = Heroicon::OutlinedMegaphone;

    protected static ?string $badgeColor = 'gray';

    #[Override]
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Product $ownerRecord */
        return (string) $ownerRecord->reviews->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('content')
                    ->label('Comment')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                RepeatableEntry::make('replies')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->columns()
                    ->placeholder('No Replies')
                    ->table([
                        TableColumn::make('Author'),
                        TableColumn::make('Reply'),
                        TableColumn::make('Replied'),
                        TableColumn::make('Actions'),
                    ])
                    ->schema([
                        TextEntry::make('author.name'),
                        TextEntry::make('content'),
                        TextEntry::make('created_at')
                            ->since()
                            ->dateTimeTooltip(),
                        TextEntry::make('delete')
                            ->state('Delete')
                            ->suffixAction(Action::make('delete')
                                ->requiresConfirmation()
                                ->hiddenLabel()
                                ->color('danger')
                                ->icon(Heroicon::OutlinedTrash)
                                ->action(fn (Comment $record) => $record->delete())
                            ),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('author.name')
            ->emptyStateHeading('No reviews')
            ->description('The reviews for this product.')
            ->columns([
                TextColumn::make('author.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('content')
                    ->label('Comment')
                    ->html()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('replies_count')
                    ->sortable()
                    ->label('Replies')
                    ->counts('replies'),
                ToggleColumn::make('is_approved')
                    ->sortable()
                    ->label('Approved'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ReplyAction::make(),
                ViewAction::make()
                    ->modalHeading('Replies')
                    ->modalDescription('View the comment replies.'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
