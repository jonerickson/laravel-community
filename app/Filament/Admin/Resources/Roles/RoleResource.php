<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles;

use App\Enums\Role as RoleEnum;
use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Admin\Resources\Roles\RelationManagers\PermissionsRelationManager;
use App\Filament\Admin\Resources\Roles\Schemas\RoleForm;
use App\Filament\Admin\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Override;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsVertical;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            // PermissionsRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && $record->name !== RoleEnum::Administrator->value;
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && ! in_array($record->name, ['super-admin', 'support-agent', 'guest', 'user', 'moderator']);
    }
}
