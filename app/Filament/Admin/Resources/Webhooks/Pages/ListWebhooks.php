<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Webhooks\Pages;

use App\Filament\Admin\Resources\Logs\LogResource;
use App\Filament\Admin\Resources\Webhooks\WebhookResource;
use App\Filament\Admin\Resources\Webhooks\Widgets\WebhookLogActivity;
use App\Models\Webhook;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListWebhooks extends ListRecords
{
    protected static string $resource = WebhookResource::class;

    protected ?string $subheading = 'Webhooks provide a way for the platform to send instant, real-time notifications to third party services.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('logs')
                ->url(LogResource::getIndexUrl([
                    'filters[type][type]' => Webhook::class,
                ]))
                ->color('gray'),
            CreateAction::make(),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            WebhookLogActivity::class,
        ];
    }
}
