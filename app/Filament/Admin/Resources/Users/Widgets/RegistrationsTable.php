<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Widgets;

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RegistrationsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query()->whereToday('created_at'))
            ->description('New user registrations today.')
            ->emptyStateHeading('No registrations')
            ->emptyStateDescription('There have been no new registrations today.')
            ->deferLoading()
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email')
                    ->label('Email Address'),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->alignEnd()
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (User $record): string => EditUser::getUrl(['record' => $record])),
                DeleteAction::make(),
            ]);
    }
}
