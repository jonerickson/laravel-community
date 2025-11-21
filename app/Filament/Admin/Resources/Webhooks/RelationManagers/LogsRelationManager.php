<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Webhooks\RelationManagers;

use App\Filament\Admin\Resources\Logs\LogResource;
use Filament\Resources\RelationManagers\RelationManager;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $relatedResource = LogResource::class;
}
