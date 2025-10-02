<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use ReflectionClass;
use ReflectionMethod;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissionsFromPolicies();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'super-admin']);
        $role->givePermissionTo(Permission::all());

        $guestRole = Role::firstOrCreate(['name' => 'guest']);
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $this->seedGuestPermissions($guestRole);
        $this->seedModeratorPermissions($moderatorRole);
        $this->seedUserPermissions($userRole);
    }

    private function createPermissionsFromPolicies(): void
    {
        $policyFiles = glob(base_path('vendor/jonerickson/laravel-community/src/Policies/*.php'));

        foreach ($policyFiles as $policyFile) {
            $className = 'App\\Policies\\'.basename($policyFile, '.php');

            if (class_exists($className)) {
                $this->extractPermissionsFromPolicy($className);
            }
        }
    }

    private function extractPermissionsFromPolicy(string $policyClass): void
    {
        $reflection = new ReflectionClass($policyClass);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->class === $policyClass && ! $method->isConstructor() && ! $method->isDestructor() && $method->getReturnType()?->getName() === 'bool') {
                $permissionName = $this->extractPermissionFromMethod($method);

                if ($permissionName) {
                    Permission::firstOrCreate(['name' => $permissionName]);
                }
            }
        }
    }

    private function extractPermissionFromMethod(ReflectionMethod $method): ?string
    {
        $source = file_get_contents($method->getFileName());

        $startLine = $method->getStartLine() - 1;
        $endLine = $method->getEndLine() - 1;
        $lines = array_slice(explode("\n", $source), $startLine, $endLine - $startLine + 1);
        $methodContent = implode("\n", $lines);

        if (preg_match('/Gate::forUser\(\$user\)->check\([\'"]([^\'"]+)[\'"]/', $methodContent, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function seedGuestPermissions(Role $role): void
    {
        $role->givePermissionTo([
            'view_any_comments',
            'view_any_forums',
            'view_any_forums_categories',
            'view_any_posts',
            'view_any_topics',
            'view_comments',
            'view_forums',
            'view_forums_category',
            'view_posts',
            'view_topics',
        ]);
    }

    private function seedModeratorPermissions(Role $role): void
    {
        $role->givePermissionTo([
            'delete_comments',
            'delete_posts',
            'delete_topics',
            'update_comments',
            'update_posts',
            'update_topics',
            'pin_posts',
            'publish_posts',
            'report_posts',
            'pin_topics',
            'lock_topics',
            'report_topics',
        ]);
    }

    private function seedUserPermissions(Role $role): void
    {
        $role->givePermissionTo([
            'create_comments',
            'create_posts',
            'create_topics',
            'view_any_comments',
            'view_any_forums',
            'view_any_forums_categories',
            'view_any_posts',
            'view_any_topics',
            'view_comments',
            'view_forums',
            'view_forums_category',
            'view_posts',
            'view_topics',
            'like_comments',
            'like_posts',
            'reply_topics',
        ]);
    }
}
