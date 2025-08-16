<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Admin\Resources\UserFingerprints\UserFingerprintResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class FingerprintsRelationManager extends RelationManager
{
    protected static string $relationship = 'fingerprints';

    public function table(Table $table): Table
    {
        return UserFingerprintResource::table($table)
            ->description('The user\'s fingerprints.')
            ->searchable(false)
            ->filters([]);
    }
}
