<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Admin\Resources\Warnings\Actions\IssueAction;
use App\Models\UserWarning;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserWarningsRelationManager extends RelationManager
{
    protected static string $relationship = 'userWarnings';

    protected static ?string $title = 'Warning History';

    protected static string|null|BackedEnum $icon = 'heroicon-o-exclamation-triangle';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('warning.name')
            ->columns([
                TextColumn::make('warning.name')
                    ->label('Warning Type')
                    ->sortable(),
                TextColumn::make('warning.points')
                    ->label('Points')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(50)
                    ->placeholder('No specific reason provided')
                    ->wrap(),
                TextColumn::make('author.name')
                    ->label('Issued By'),
                TextColumn::make('created_at')
                    ->label('Issued At')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->badge()
                    ->color(fn (UserWarning $record) => $record->isActive() ? 'danger' : 'success'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                IssueAction::make()
                    ->user(fn () => $this->getOwnerRecord()),
            ])
            ->emptyStateHeading('No warnings issued')
            ->emptyStateDescription('This user has no warning history.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
