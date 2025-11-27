<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Admin\Resources\Fingerprints\FingerprintResource;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FingerprintsRelationManager extends RelationManager
{
    protected static string $relationship = 'fingerprints';

    protected static ?string $relatedResource = FingerprintResource::class;

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedDevicePhoneMobile;

    public function table(Table $table): Table
    {
        return $table
            ->description("The user's fingerprints.")
            ->searchable(false)
            ->toolbarActions([])
            ->filters([]);
    }
}
