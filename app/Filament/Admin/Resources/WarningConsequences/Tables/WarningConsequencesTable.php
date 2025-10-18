<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WarningConsequences\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarningConsequencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Consequence Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('threshold')
                    ->label('Point Threshold')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label('Duration (Days)')
                    ->numeric(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('threshold');
    }
}
