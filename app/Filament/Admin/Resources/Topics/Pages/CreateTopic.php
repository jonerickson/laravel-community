<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Topics\Pages;

use App\Filament\Admin\Resources\Topics\TopicResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTopic extends CreateRecord
{
    protected static string $resource = TopicResource::class;
}
