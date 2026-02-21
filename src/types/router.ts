/**
 * Router Type Definitions for OpenPBBG
 */

import 'vue-router'

/**
 * Route metadata interface for type safety
 */
export interface RouteMeta {
  requiresAuth?: boolean
  requiresGuest?: boolean
  title?: string
  layout?: string
  transition?: string
  keepAlive?: boolean
}

/**
 * Augment Vue Router types for route meta
 */
declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    requiresGuest?: boolean
    title?: string
    layout?: string
    transition?: string
    keepAlive?: boolean
  }
}

/**
 * Named routes for type-safe navigation
 */
export type AppRouteName =
  | 'login'
  | 'register'
  | 'forgot-password'
  | 'reset-password'
  | 'dashboard'
  | 'home'
  | 'city'
  | 'inventory'
  | 'missions'
  | 'combat'
  | 'scavenge'
  | 'travel'
  | 'skills'
  | 'forums'
  | 'profile'
  | 'crimes'
  | 'crime-action'
  | 'gym'
  | 'hospital'
  | 'bank'
  | 'drugs'
  | 'theft'
  | 'racing'
  | 'jail'
  | 'properties'
  | 'bounty'
  | 'detective'
  | 'bullets'
  | 'gang'
  | 'organized-crime'
  | 'chat'
  | 'messaging'
  | 'achievements'
  | 'leaderboards'
  | 'activity'
  | 'employment'
  | 'education'
  | 'quests'
  | 'alliances'
  | 'shop'
  | 'market'
  | 'stocks'
  | 'casino'
  | 'explore'
  | 'hunting'
  | 'events'
  | 'tournament'
  | 'wiki'
  | 'tickets'
  | 'settings'
  | 'notifications'
  | 'announcements'
  | 'daily-rewards'
  | 'not-found'

/**
 * Route location for type-safe navigation
 */
export interface TypedRouteLocation {
  name: AppRouteName
  params?: Record<string, string | number>
  query?: Record<string, string | number | boolean>
}

/**
 * Navigation guard context
 */
export interface NavigationGuardContext {
  to: ReturnType<typeof import('vue-router').useRoute>
  from: ReturnType<typeof import('vue-router').useRoute>
  next: import('vue-router').NavigationGuardNext
}

/**
 * Breadcrumb item
 */
export interface BreadcrumbItem {
  label: string
  to?: string | { name: AppRouteName; params?: Record<string, string | number> }
  icon?: string
}

/**
 * Menu item for navigation
 */
export interface MenuItem {
  label: string
  to?: { name: AppRouteName }
  icon?: string
  badge?: number | string
  children?: MenuItem[]
  divider?: boolean
}
