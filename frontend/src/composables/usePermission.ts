import { useAuthStore } from '@/stores/auth'

/**
 * Check if the current user has a specific permission.
 * Returns false if not authenticated.
 */
export function can(permission: string): boolean {
  const auth = useAuthStore()
  return auth.user?.permissions?.includes(permission) ?? false
}

/**
 * Check if the current user has any of the given permissions.
 */
export function canAny(permissions: string[]): boolean {
  return permissions.some(can)
}

/**
 * Check if the current user has all of the given permissions.
 */
export function canAll(permissions: string[]): boolean {
  return permissions.every(can)
}

/**
 * Check if the current user has a specific role.
 */
export function hasRole(role: string): boolean {
  const auth = useAuthStore()
  return auth.user?.roles?.includes(role) ?? false
}

/**
 * Check if the current user has any of the given roles.
 */
export function hasAnyRole(roles: string[]): boolean {
  return roles.some(hasRole)
}
