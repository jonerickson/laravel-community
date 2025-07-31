<?php

declare(strict_types=1);

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_product","view_any_product","create_product","update_product","restore_product","restore_any_product","replicate_product","reorder_product","delete_product","delete_any_product","force_delete_product","force_delete_any_product","view_product::category","view_any_product::category","create_product::category","update_product::category","restore_product::category","restore_any_product::category","replicate_product::category","reorder_product::category","delete_product::category","delete_any_product::category","force_delete_product::category","force_delete_any_product::category","view_announcement","view_any_announcement","create_announcement","update_announcement","restore_announcement","restore_any_announcement","replicate_announcement","reorder_announcement","delete_announcement","delete_any_announcement","force_delete_announcement","force_delete_any_announcement","view_post","view_any_post","create_post","update_post","restore_post","restore_any_post","replicate_post","reorder_post","delete_post","delete_any_post","force_delete_post","force_delete_any_post","view_topic","view_any_topic","create_topic","update_topic","restore_topic","restore_any_topic","replicate_topic","reorder_topic","delete_topic","delete_any_topic","force_delete_topic","force_delete_any_topic","view_policy","view_any_policy","create_policy","update_policy","restore_policy","restore_any_policy","replicate_policy","reorder_policy","delete_policy","delete_any_policy","force_delete_policy","force_delete_any_policy","view_policy::category","view_any_policy::category","create_policy::category","update_policy::category","restore_policy::category","restore_any_policy::category","replicate_policy::category","reorder_policy::category","delete_policy::category","delete_any_policy::category","force_delete_policy::category","force_delete_any_policy::category","view_api::token","view_any_api::token","create_api::token","update_api::token","restore_api::token","restore_any_api::token","replicate_api::token","reorder_api::token","delete_api::token","delete_any_api::token","force_delete_api::token","force_delete_any_api::token","view_forum","view_any_forum","create_forum","update_forum","restore_forum","restore_any_forum","replicate_forum","reorder_forum","delete_forum","delete_any_forum","force_delete_forum","force_delete_any_forum","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","view_user::fingerprint","view_any_user::fingerprint","create_user::fingerprint","update_user::fingerprint","restore_user::fingerprint","restore_any_user::fingerprint","replicate_user::fingerprint","reorder_user::fingerprint","delete_user::fingerprint","delete_any_user::fingerprint","force_delete_user::fingerprint","force_delete_any_user::fingerprint"]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }
}
