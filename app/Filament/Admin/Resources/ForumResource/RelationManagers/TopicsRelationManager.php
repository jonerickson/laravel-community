<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumResource\RelationManagers;

use App\Filament\Admin\Resources\TopicResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TopicsRelationManager extends RelationManager
{
    protected static string $relationship = 'topics';

    protected static ?string $icon = 'heroicon-o-pencil-square';

    public function table(Table $table): Table
    {
        return TopicResource::table($table)
            ->description('The forum topics.')
            ->defaultGroup(null);
    }
}
