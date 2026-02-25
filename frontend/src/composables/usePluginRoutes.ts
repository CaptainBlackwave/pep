import { type Component, defineAsyncComponent } from 'vue'
import type { RouteRecordRaw, Router } from 'vue-router'
import { usePluginsStore, type PluginRoute } from '@/stores/plugins'

/**
 * Plugin route metadata extension
 */
declare module 'vue-router' {
  interface RouteMeta {
    plugin?: string
    title?: string
    requiresAuth?: boolean
    requiresGuest?: boolean
  }
}

/**
 * View components mapping - maps component names to their file paths
 */
const componentMap: Record<string, () => Promise<Component>> = {
  // Combat & Activities
  CombatView: () => import('@/views/plugins/CombatView.vue'),
  OrganizedCrimeView: () => import('@/views/plugins/OrganizedCrimeView.vue'),
  TheftView: () => import('@/views/plugins/TheftView.vue'),
  RacingView: () => import('@/views/plugins/RacingView.vue'),
  BountyView: () => import('@/views/plugins/BountyView.vue'),
  MissionsView: () => import('@/views/plugins/MissionsView.vue'),
  CrimesView: () => import('@/views/plugins/CrimesView.vue'),
  CrimeActionView: () => import('@/views/plugins/CrimeActionView.vue'),

  // Economy & Trading
  MarketView: () => import('@/views/plugins/MarketView.vue'),
  DrugsView: () => import('@/views/plugins/DrugsView.vue'),
  StocksView: () => import('@/views/plugins/StocksView.vue'),
  PropertiesView: () => import('@/views/plugins/PropertiesView.vue'),
  CasinoView: () => import('@/views/plugins/CasinoView.vue'),
  EmploymentView: () => import('@/views/plugins/EmploymentView.vue'),
  BulletsView: () => import('@/views/plugins/BulletsView.vue'),
  BankView: () => import('@/views/plugins/BankView.vue'),
  ShopView: () => import('@/views/plugins/ShopView.vue'),

  // Social & Community
  GangView: () => import('@/views/plugins/GangView.vue'),
  ForumView: () => import('@/views/plugins/ForumView.vue'),
  ChatView: () => import('@/views/plugins/ChatView.vue'),
  LeaderboardsView: () => import('@/views/plugins/LeaderboardsView.vue'),
  AchievementsView: () => import('@/views/plugins/AchievementsView.vue'),
  ProfileView: () => import('@/views/plugins/ProfileView.vue'),
  TicketsView: () => import('@/views/TicketsView.vue'),
  WikiView: () => import('@/views/plugins/WikiView.vue'),
  MessagingView: () => import('@/views/plugins/MessagingView.vue'),

  // Utilities
  HospitalView: () => import('@/views/plugins/HospitalView.vue'),
  TravelView: () => import('@/views/plugins/TravelView.vue'),
  JailView: () => import('@/views/plugins/JailView.vue'),
  DetectiveView: () => import('@/views/plugins/DetectiveView.vue'),
  InventoryView: () => import('@/views/plugins/InventoryView.vue'),
  EducationView: () => import('@/views/plugins/EducationView.vue'),
  ActivityView: () => import('@/views/plugins/ActivityView.vue'),
  AnnouncementsView: () => import('@/views/AnnouncementsView.vue'),
  DailyRewardsView: () => import('@/views/DailyRewardsView.vue'),

  // Game features
  CityView: () => import('@/views/plugins/CityView.vue'),
  GymView: () => import('@/views/plugins/GymView.vue'),
  SkillsView: () => import('@/views/plugins/SkillsView.vue'),
  ScavengeView: () => import('@/views/plugins/ScavengeView.vue'),
  ExploreView: () => import('@/views/plugins/ExploreView.vue'),
  HuntingView: () => import('@/views/plugins/HuntingView.vue'),
  EventsView: () => import('@/views/plugins/EventsView.vue'),
  TournamentView: () => import('@/views/plugins/TournamentView.vue'),
  QuestsView: () => import('@/views/plugins/QuestsView.vue'),
  AlliancesView: () => import('@/views/plugins/AlliancesView.vue'),

  // Note: Additional plugin views should be added as they are created
}

/**
 * Get the component loader for a given component name
 */
function getComponentLoader(componentName: string | null): (() => Promise<Component>) | null {
  if (!componentName) return null

  // Check if we have a mapping for this component
  if (componentMap[componentName]) {
    return componentMap[componentName]
  }

  // Try to dynamically import based on component name
  // This allows plugins to define their own components
  try {
    return defineAsyncComponent(() =>
      import(`@/views/plugins/${componentName}.vue`)
    )
  } catch {
    console.warn(`Component not found: ${componentName}`)
    return null
  }
}

/**
 * Convert a plugin route to a Vue Router route
 */
function pluginRouteToRouterRoute(route: PluginRoute): RouteRecordRaw | null {
  const componentLoader = getComponentLoader(route.component)

  if (!componentLoader) {
    console.warn(`Cannot create route for ${route.path}: component ${route.component} not found`)
    return null
  }

  return {
    path: route.path,
    name: route.name || undefined,
    component: defineAsyncComponent(componentLoader),
    meta: {
      plugin: route.plugin,
      ...route.meta,
    },
  }
}

/**
 * Register plugin routes with the router
 */
export function registerPluginRoutes(
  router: Router,
  pluginRoutes: PluginRoute[]
): void {
  // Get the existing routes
  const existingRoutes = router.getRoutes()
  const existingPaths = new Set(existingRoutes.map(r => r.path))

  // Convert plugin routes to router routes
  const newRoutes: RouteRecordRaw[] = []

  for (const pluginRoute of pluginRoutes) {
    // Skip if route already exists
    if (existingPaths.has(pluginRoute.path)) {
      continue
    }

    const routerRoute = pluginRouteToRouterRoute(pluginRoute)
    if (routerRoute) {
      newRoutes.push(routerRoute)
    }
  }

  // Find the GameLayout route to add plugin routes as children
  const gameLayoutRoute = existingRoutes.find(r => r.path === '/' && r.children?.length)

  if (gameLayoutRoute) {
    // Add routes as children of GameLayout
    for (const route of newRoutes) {
      try {
        router.addRoute(gameLayoutRoute.name as string, route)
      } catch (error) {
        console.warn(`Failed to register route ${route.path}:`, error)
      }
    }
  } else {
    // Fallback: add routes directly
    for (const route of newRoutes) {
      try {
        router.addRoute(route)
      } catch (error) {
        console.warn(`Failed to register route ${route.path}:`, error)
      }
    }
  }
}

/**
 * Unregister plugin routes from the router
 */
export function unregisterPluginRoutes(router: Router, pluginSlug: string): void {
  const routes = router.getRoutes()

  for (const route of routes) {
    if (route.meta?.plugin === pluginSlug && route.name) {
      router.removeRoute(route.name)
    }
  }
}

/**
 * Composable for plugin route management
 */
export function usePluginRoutes() {
  const pluginsStore = usePluginsStore()

  /**
   * Check if a route is provided by an enabled plugin
   */
  function isPluginRoute(routeName: string): boolean {
    return pluginsStore.getPluginByRoute(routeName) !== undefined
  }

  /**
   * Check if a plugin route is accessible (plugin is enabled)
   */
  function isRouteAccessible(routeName: string): boolean {
    const plugin = pluginsStore.getPluginByRoute(routeName)
    return plugin ? pluginsStore.isEnabled(plugin.slug) : true
  }

  /**
   * Get all routes for a specific plugin
   */
  function getRoutesForPlugin(slug: string): PluginRoute[] {
    return pluginsStore.routes.filter(r => r.plugin === slug)
  }

  /**
   * Get the component name for a route
   */
  function getComponentForRoute(routeName: string): string | null {
    const route = pluginsStore.routes.find(r => r.name === routeName)
    return route?.component || null
  }

  return {
    isPluginRoute,
    isRouteAccessible,
    getRoutesForPlugin,
    getComponentForRoute,
    registerPluginRoutes,
    unregisterPluginRoutes,
  }
}
