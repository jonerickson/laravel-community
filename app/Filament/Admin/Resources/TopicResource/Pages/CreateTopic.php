<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TopicResource\Pages;

use App\Filament\Admin\Resources\TopicResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTopic extends CreateRecord
{
    protected static string $resource = TopicResource::class;
}
