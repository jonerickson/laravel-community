<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SocialsRelationManager extends RelationManager
{
    protected static string $relationship = 'socials';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Integrations')
            ->description('The user\'s connected accounts.')
            ->recordTitleAttribute('provider')
            ->emptyStateHeading('No connected accounts')
            ->columns([
                TextColumn::make('provider')
                    ->badge()
                    ->copyable()
                    ->formatStateUsing(fn ($state) => Str::ucfirst($state))
                    ->searchable(),
                TextColumn::make('provider_id')
                    ->copyable()
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('provider_name')
                    ->copyable()
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('provider_email')
                    ->copyable()
                    ->label('Email')
                    ->searchable(),
                ImageColumn::make('provider_avatar')
                    ->label('Avatar')
                    ->circular()
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
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
