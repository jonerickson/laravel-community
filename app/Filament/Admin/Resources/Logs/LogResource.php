<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Logs;

use App\Filament\Admin\Resources\Logs\Pages\ListLogs;
use App\Filament\Admin\Resources\Logs\Pages\ViewLog;
use App\Filament\Admin\Resources\Logs\Schemas\LogInfolist;
use App\Filament\Admin\Resources\Logs\Tables\LogsTable;
use App\Models\Log;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

class LogResource extends Resource
{
    protected static ?string $model = Log::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'endpoint';

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return LogInfolist::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return LogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLogs::route('/'),
            'view' => ViewLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
