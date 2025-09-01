<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('guard_name')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Add permission')
                    ->modalHeading('Add permission')
                    ->modalDescription('Attach a new permission to this role.')
                    ->modalSubmitActionLabel('Add')
                    ->preloadRecordSelect()
                    ->multiple(),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove permission?')
                    ->modalDescription('Are you sure you want to remove this permission from this role?')
                    ->modalSubmitActionLabel('Remove'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->label('Remove selected')
                        ->modalHeading('Remove selected permissions?')
                        ->modalDescription('Are you sure you want to remove these permissions from this role?')
                        ->modalSubmitActionLabel('Remove'),
                ]),
            ]);
    }
}
