<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles\Tables;

use App\Enums\Role as RoleEnum;
use App\Models\Role;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
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
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Role $record): bool => $record->name !== RoleEnum::Administrator->value),
                DeleteAction::make()
                    ->visible(fn (Role $record): bool => ! in_array($record->name, ['super-admin', 'support-agent', 'guest', 'user', 'moderator'])),
            ]);
    }
}
