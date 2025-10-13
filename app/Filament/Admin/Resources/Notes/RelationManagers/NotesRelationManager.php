<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Notes\RelationManagers;

use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedDocument;

    protected static ?string $badgeColor = 'gray';

    #[Override]
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->notes->count();
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                RichEditor::make('content')
                    ->hiddenLabel()
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->description('Notes belonging to this resource.')
            ->columns([
                TextColumn::make('content')
                    ->label('Note')
                    ->wrap()
                    ->searchable()
                    ->html(),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->modalDescription('Add a new note to this resource.'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
