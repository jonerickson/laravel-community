<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Admin\Resources\Warnings\Actions\IssueAction;
use App\Models\UserWarning;
use App\Models\Warning;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserWarningsRelationManager extends RelationManager
{
    protected static string $relationship = 'userWarnings';

    protected static ?string $title = 'Warning History';

    protected static string|null|BackedEnum $icon = 'heroicon-o-exclamation-triangle';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('warning_id')
                    ->label('Warning Type')
                    ->options(Warning::active()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        $warning = Warning::find($state);
                        if ($warning) {
                            $set('points_preview', $warning->points);
                            $set('days_preview', $warning->days_applied);
                        }
                    }),
                Textarea::make('reason')
                    ->label('Specific Reason')
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('Optional: provide specific details about this warning instance'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('warning.name')
            ->description('The user\'s warning history.')
            ->emptyStateHeading('No warnings issued')
            ->emptyStateDescription('This user has no warning history.')
            ->emptyStateIcon('heroicon-o-check-circle')
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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
