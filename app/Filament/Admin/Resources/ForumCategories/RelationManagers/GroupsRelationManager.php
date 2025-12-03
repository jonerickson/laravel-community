<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumCategories\RelationManagers;

use BackedEnum;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
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
            ->description('The groups that have access to the forum categories.')
            ->columns([
                TextColumn::make('name')
                    ->sortable(),
                ToggleColumn::make('read')
                    ->label('Can Read'),
                ToggleColumn::make('write')
                    ->label('Can Write'),
                ToggleColumn::make('delete')
                    ->label('Can Delete'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Add group')
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->modalHeading('Add Group')
                    ->modalDescription('Add a group to this forum category.')
                    ->modalSubmitActionLabel('Add')
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect()->preload()->searchable(),
                        Section::make('Permissions')
                            ->contained(false)
                            ->columns(3)
                            ->schema([
                                Toggle::make('read')->required(),
                                Toggle::make('write')->required(),
                                Toggle::make('delete')->required(),
                            ]),
                    ]),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Remove'),
            ])
            ->defaultSort('groups.order');
    }
}
