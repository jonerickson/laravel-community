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

        Role::firstOrCreate(['name' => 'moderator']);
        Role::firstOrCreate(['name' => 'user']);
    }

    private function createPermissionsFromPolicies(): void
    {
        $policyFiles = glob(app_path('Policies/*.php'));

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

        if (preg_match('/hasPermissionTo\([\'"]([^\'"]+)[\'"]\)/', $methodContent, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
