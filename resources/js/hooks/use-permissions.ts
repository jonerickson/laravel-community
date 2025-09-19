import { usePage } from '@inertiajs/react';

interface UsePermissionsReturn {
    can: (permission: string) => boolean;
    cannot: (permission: string) => boolean;
    hasRole: (role: string) => boolean;
    hasAnyRole: (roles: string[]) => boolean;
    hasAllPermissions: (permissions: string[]) => boolean;
    hasAnyPermission: (permissions: string[]) => boolean;
    permissions: string[];
    roles: string[];
}

export function usePermissions(): UsePermissionsReturn {
    const { auth } = usePage<App.Data.SharedData>().props;
    const can = (permission: string): boolean => {
        return Array.isArray(auth?.can) && auth.can.includes(permission);
    };

    const cannot = (permission: string): boolean => {
        return !can(permission);
    };

    const hasRole = (role: string): boolean => {
        return Array.isArray(auth?.roles) && auth?.roles?.includes(role);
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
        permissions: auth?.can ?? [],
        roles: auth?.roles ?? [],
    };
}

export default usePermissions;
