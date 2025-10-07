<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Widgets;

use App\Models\User;
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
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email')
                    ->label('Email address'),
                TextColumn::make('created_at')
                    ->label('Registered At')
                    ->alignEnd()
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
