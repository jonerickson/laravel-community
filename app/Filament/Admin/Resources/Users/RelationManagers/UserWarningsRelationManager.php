<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Admin\Resources\Warnings\Actions\IssueAction;
use App\Models\UserWarning;
use App\Models\Warning;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

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
                    ->searchable(),
                Textarea::make('reason')
                    ->label('Specific Reason')
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('Optional: provide specific details about this warning instance'),
                DateTimePicker::make('consequence_expires_at')
                    ->label('Consequence Expires')
                    ->helperText('The date at which the issue consequence expires.')
                    ->nullable(),
                DateTimePicker::make('points_expire_at')
                    ->label('Points Expire')
                    ->helperText('The date at which the issued points expire.')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('warning.name')
            ->description("The user's warning history.")
            ->emptyStateHeading('No warnings issued')
            ->emptyStateDescription('This user has no warning history.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('warning.name')
                    ->label('Warning Type')
                    ->sortable(),
                TextColumn::make('warning.points')
                    ->label('Points Issued')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('points_at_issue')
                    ->label('Points Total At Issue')
                    ->badge()
                    ->color('info'),
                TextColumn::make('warningConsequence.type')
                    ->placeholder('No Consequence Issued')
                    ->label('Consequence Issued')
                    ->badge(),
                TextColumn::make('consequence_expires_at')
                    ->placeholder('No Consequence Expiration')
                    ->label('Consequence Expire')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->badge()
                    ->color(fn (UserWarning $record): string => $record->hasActiveConsequence() ? 'danger' : 'success'),
                TextColumn::make('author.name')
                    ->label('Issued By'),
                TextColumn::make('created_at')
                    ->label('Issued')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('points_expire_at')
                    ->label('Points Expire')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->badge()
                    ->color(fn (UserWarning $record): string => $record->isActive() ? 'danger' : 'success'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                IssueAction::make()
                    ->user(fn (): Model => $this->getOwnerRecord()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
