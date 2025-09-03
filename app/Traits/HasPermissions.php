<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin Model
 */
trait HasPermissions
{
    use HasRoles {
        roles as parentRoles;
        permissions as parentPermissions;
        getAllPermissions as parentGetAllPermissions;
    }

    public function roles(): BelongsToMany
    {
        return $this->parentRoles();
    }

    /**
     * Get all roles including those from assigned groups
     */
    public function getAllRoles(): Collection
    {
        // Get direct roles
        $directRoles = $this->parentRoles()->get();

        // Get roles from groups
        $groupRoles = Role::query()
            ->whereHas('groups.users', function ($query) {
                $query->where('users.id', $this->getKey());
            })
            ->get();

        return $directRoles->merge($groupRoles)->unique('id');
    }

    /**
     * Check if user has role (including through groups)
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        if (is_string($roles) && str_contains($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            return $this->getAllRoles()->contains('name', $roles);
        }

        if (is_int($roles)) {
            return $this->getAllRoles()->contains('id', $roles);
        }

        if ($roles instanceof Role) {
            return $this->getAllRoles()->contains('id', $roles->id);
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role, $guard)) {
                    return true;
                }
            }

            return false;
        }

        return $roles->intersect($this->getAllRoles())->isNotEmpty();
    }

    /**
     * Check if user has permission (including through groups)
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // Check direct permissions first
        if ($this->parentPermissions()->where('name', $permission)->exists()) {
            return true;
        }

        // Check permissions through all roles (direct + group)
        foreach ($this->getAllRoles() as $role) {
            if ($role->hasPermissionTo($permission, $guardName)) {
                return true;
            }
        }

        // Check direct group permissions
        foreach ($this->groups as $group) {
            foreach ($group->permissions as $groupPermission) {
                if ($groupPermission->name === $permission) {
                    return true;
                }
            }
        }

        return false;
    }
}
