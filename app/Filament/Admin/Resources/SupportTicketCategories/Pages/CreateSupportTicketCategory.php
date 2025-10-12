<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTicketCategories\Pages;

use App\Filament\Admin\Resources\SupportTicketCategories\SupportTicketCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportTicketCategory extends CreateRecord
{
    protected static string $resource = SupportTicketCategoryResource::class;
}
