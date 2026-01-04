<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Forums\RelationManagers;

use BackedEnum;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class GroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'groups';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedUserGroup;

    protected static ?string $title = 'Permissions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->description('The groups that have access to the forums.')
            ->columns([
                TextColumn::make('name')
                    ->label('Group')
                    ->sortable(),
                ToggleColumn::make('create')
                    ->onIcon(Heroicon::OutlinedLockOpen)
                    ->offIcon(Heroicon::OutlinedLockClosed)
                    ->onColor('success')
                    ->offColor('danger')
                    ->headerTooltip('Can create a new topic in the forum.')
                    ->label('Can Create'),
                ToggleColumn::make('read')
                    ->onIcon(Heroicon::OutlinedLockOpen)
                    ->offIcon(Heroicon::OutlinedLockClosed)
                    ->onColor('success')
                    ->offColor('danger')
                    ->headerTooltip('Can view the forum.')
                    ->label('Can Read'),
                ToggleColumn::make('update')
                    ->onIcon(Heroicon::OutlinedLockOpen)
                    ->offIcon(Heroicon::OutlinedLockClosed)
                    ->onColor('success')
                    ->offColor('danger')
                    ->headerTooltip('Can update topics/posts in the forum. Authors will be able to update their own topics/posts.')
                    ->label('Can Update'),
                ToggleColumn::make('delete')
                    ->onIcon(Heroicon::OutlinedLockOpen)
                    ->offIcon(Heroicon::OutlinedLockClosed)
                    ->onColor('success')
                    ->offColor('danger')
                    ->headerTooltip('Can delete topics/posts in the forum. Authors will be able to delete their own topics/posts.')
                    ->label('Can Delete'),
                ToggleColumn::make('moderate')
                    ->onIcon(Heroicon::OutlinedLockOpen)
                    ->offIcon(Heroicon::OutlinedLockClosed)
                    ->onColor('success')
                    ->offColor('danger')
                    ->headerTooltip('Can moderate topics/posts in the forum.')
                    ->label('Can Moderate'),
                ToggleColumn::make('reply')
                    ->onIcon(Heroicon::OutlinedLockOpen)
                    ->offIcon(Heroicon::OutlinedLockClosed)
                    ->onColor('success')
                    ->offColor('danger')
                    ->headerTooltip('Can reply to topics in the forum.')
                    ->label('Can Reply'),
                ToggleColumn::make('report')
                    ->onIcon(Heroicon::OutlinedLockOpen)
                    ->offIcon(Heroicon::OutlinedLockClosed)
                    ->onColor('success')
                    ->offColor('danger')
                    ->headerTooltip('Can report topics/posts in the forum.')
                    ->label('Can Report'),
                ToggleColumn::make('pin')
                    ->onIcon(Heroicon::OutlinedLockOpen)
                    ->offIcon(Heroicon::OutlinedLockClosed)
                    ->onColor('success')
                    ->offColor('danger')
                    ->headerTooltip('Can pin topics/posts in the forum.')
                    ->label('Can Pin'),
                ToggleColumn::make('lock')
                    ->onIcon(Heroicon::OutlinedLockOpen)
                    ->offIcon(Heroicon::OutlinedLockClosed)
                    ->onColor('success')
                    ->offColor('danger')
                    ->headerTooltip('Can lock topics in the forum.')
                    ->label('Can Lock'),
                ToggleColumn::make('move')
                    ->onIcon(Heroicon::OutlinedLockOpen)
                    ->offIcon(Heroicon::OutlinedLockClosed)
                    ->onColor('success')
                    ->offColor('danger')
                    ->headerTooltip('Can move topics/posts in the forum.')
                    ->label('Can Move'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Add group')
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->modalHeading('Add Group')
                    ->modalDescription('Add a group to this forum.')
                    ->modalSubmitActionLabel('Add')
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Remove'),
            ])
            ->defaultSort('groups.order');
    }
}
