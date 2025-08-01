<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use App\Filament\Admin\Resources\UserFingerprintResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class FingerprintsRelationManager extends RelationManager
{
    protected static string $relationship = 'fingerprints';

    public function table(Table $table): Table
    {
        return UserFingerprintResource::table($table)
            ->description('The users fingerprints.')
            ->searchable(false)
            ->filters([]);
    }
}
