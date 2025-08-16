<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Forums\RelationManagers;

use App\Filament\Admin\Resources\Topics\TopicResource;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TopicsRelationManager extends RelationManager
{
    protected static string $relationship = 'topics';

    protected static string|BackedEnum|null $icon = 'heroicon-o-pencil-square';

    public function table(Table $table): Table
    {
        return TopicResource::table($table)
            ->description('The forum topics.')
            ->defaultGroup(null);
    }
}
