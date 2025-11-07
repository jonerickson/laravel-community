<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use BackedEnum;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedRectangleStack;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('value')
                ->helperText('The field value.')
                ->columnSpanFull()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->description('The users custom profile data.')
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('pivot.value')
                    ->label('Value')
                    ->sortable()
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make()
                    ->modalHeading('Remove Field')
                    ->modalDescription('Are you sure you want to remove this field?')
                    ->modalSubmitActionLabel('Remove')
                    ->label('Remove'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->modalHeading('Add field')
                    ->modalDescription('Set a custom field for this user.')
                    ->modalSubmitActionLabel('Save')
                    ->attachAnother(false)
                    ->label('Add field')
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->required()
                            ->label('Field'),
                        Textarea::make('value')
                            ->helperText('The field value.')
                            ->columnSpanFull()
                            ->required(),
                    ]),

            ]);
    }
}
