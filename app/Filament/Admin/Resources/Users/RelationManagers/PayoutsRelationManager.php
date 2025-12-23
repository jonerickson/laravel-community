<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Admin\Resources\Payouts\PayoutResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class PayoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'payouts';

    protected static ?string $relatedResource = PayoutResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->description("The user's payout history.");
    }
}
