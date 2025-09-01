import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

interface UsePermissionsReturn {
    can: (permission: string) => boolean;
    cannot: (permission: string) => boolean;
    hasRole: (role: string) => boolean;
    hasAnyRole: (roles: string[]) => boolean;
    hasAllPermissions: (permissions: string[]) => boolean;
    hasAnyPermission: (permissions: string[]) => boolean;
    permissions: Record<string, boolean>;
    roles: string[];
}

export function usePermissions(): UsePermissionsReturn {
    const { auth } = usePage<SharedData>().props;

    const can = (permission: string): boolean => {
        return auth?.can?.[permission] === true;
    };

    const cannot = (permission: string): boolean => {
        return !can(permission);
    };

    const hasRole = (role: string): boolean => {
        return auth?.roles?.includes(role) ?? false;
    };

    const hasAnyRole = (roles: string[]): boolean => {
        return roles.some((role) => hasRole(role));
    };

    const hasAllPermissions = (permissions: string[]): boolean => {
        return permissions.every((permission) => can(permission));
    };

    const hasAnyPermission = (permissions: string[]): boolean => {
        return permissions.some((permission) => can(permission));
    };

    return {
        can,
        cannot,
        hasRole,
        hasAnyRole,
        hasAllPermissions,
        hasAnyPermission,
        permissions: auth?.can ?? {},
        roles: auth?.roles ?? [],
    };
}

export default usePermissions;
