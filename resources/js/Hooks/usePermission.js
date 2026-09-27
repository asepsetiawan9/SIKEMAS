import { usePage } from '@inertiajs/react';

/**
 * Custom hook to check user permissions and roles
 */
export function usePermission() {
    const { auth } = usePage().props;
    const permissions = auth?.user?.permissions || [];
    const role = auth?.user?.role || '';

    const hasPermission = (permissionName) => {
        return permissions.includes(permissionName);
    };

    const hasAnyPermission = (permissionNames = []) => {
        return permissionNames.some((perm) => permissions.includes(perm));
    };

    const hasRole = (roleName) => {
        return role === roleName;
    };

    return {
        user: auth?.user,
        role,
        permissions,
        hasPermission,
        hasAnyPermission,
        hasRole,
    };
}
