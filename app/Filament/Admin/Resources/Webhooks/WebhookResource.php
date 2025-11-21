<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Webhooks;

use App\Filament\Admin\Resources\Webhooks\Pages\CreateWebhook;
use App\Filament\Admin\Resources\Webhooks\Pages\EditWebhook;
use App\Filament\Admin\Resources\Webhooks\Pages\ListWebhooks;
use App\Filament\Admin\Resources\Webhooks\RelationManagers\LogsRelationManager;
use App\Filament\Admin\Resources\Webhooks\Schemas\WebhookForm;
use App\Filament\Admin\Resources\Webhooks\Tables\WebhooksTable;
use App\Models\Webhook;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class WebhookResource extends Resource
{
    protected static ?string $model = Webhook::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloudArrowUp;

    protected static ?string $recordTitleAttribute = 'url';

    protected static string|UnitEnum|null $navigationGroup = 'Integrations';

    protected static ?int $navigationSort = -4;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return WebhookForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return WebhooksTable::configure($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            LogsRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebhooks::route('/'),
            'create' => CreateWebhook::route('/create'),
            'edit' => EditWebhook::route('/{record}/edit'),
        ];
    }
}
