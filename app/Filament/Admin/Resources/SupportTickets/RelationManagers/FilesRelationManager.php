<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Override;

class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $label = 'attachment';

    #[Override]
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('file')
                    ->columnSpanFull()
                    ->label('File')
                    ->required()
                    ->visibility('private')
                    ->directory('support')
                    ->storeFileNamesIn('filename')
                    ->downloadable()
                    ->previewable()
                    ->openable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('The files attatched to this support ticket.')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('mime')
                    ->sortable()
                    ->label('MIME Type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('size')
                    ->sortable()
                    ->formatStateUsing(fn (int|float $state) => Number::fileSize($state))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
