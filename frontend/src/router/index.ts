import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import type { RouteMeta } from '@/types/router'
import '@/types/router' // Import for route meta type augmentation
import { usePluginsStore } from '@/stores/plugins'
import { registerPluginRoutes } from '@/composables/usePluginRoutes'

// Re-export RouteMeta for convenience
export type { RouteMeta } from '@/types/router'

// Plugin slug to route mapping - used for static route definitions
// Dynamic routes are loaded from backend via PluginManifestService
const pluginRoutes: Record<string, string[]> = {
  achievements: ['achievements'],
  advancedcrimes: ['advanced-crimes'],
  alliances: ['alliances'],
  announcements: ['announcements'],
  bounty: ['bounty'],
  bullets: ['bullets'],
  casino: ['casino', 'tournament'],
  chat: ['chat'],
  combat: ['combat'],
  dailyrewards: ['daily-rewards'],
  detective: ['detective'],
  drugs: ['drugs'],
  education: ['education'],
  employment: ['employment'],
  events: ['events'],
  forum: ['forums'],
  gang: ['gang'],
  hospital: ['hospital'],
  inventory: ['inventory'],
  jail: ['jail'],
  leaderboards: ['leaderboards'],
  market: ['market'],
  messaging: ['messaging'],
  minirpg: ['mini-rpg'],
  missions: ['missions'],
  organizedcrime: ['organized-crime'],
  progression: ['progression'],
  properties: ['properties'],
  quests: ['quests'],
  racing: ['racing'],
  stocks: ['stocks'],
  theft: ['theft'],
  tickets: ['tickets'],
  tournament: ['tournament'],
  travel: ['travel'],
  wiki: ['wiki'],
}

// Reverse mapping: route name -> plugin slug
const routeToPlugin: Record<string, string> = {}
Object.entries(pluginRoutes).forEach(([plugin, routes]) => {
  routes.forEach(route => {
    routeToPlugin[route] = plugin
  })
})

/**
 * Route definitions with lazy loading for optimal bundle size
 */
const routes: RouteRecordRaw[] = [
  // Root redirect
  {
    path: '/',
    redirect: '/dashboard'
  },

  // Guest-only routes (authentication)
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresGuest: true, title: 'Login' } satisfies RouteMeta
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { requiresGuest: true, title: 'Register' } satisfies RouteMeta
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/ForgotPasswordView.vue'),
    meta: { requiresGuest: true, title: 'Forgot Password' } satisfies RouteMeta
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/views/ResetPasswordView.vue'),
    meta: { requiresGuest: true, title: 'Reset Password' } satisfies RouteMeta
  },

  // Authenticated routes with GameLayout
  {
    path: '/',
    component: () => import('@/layouts/GameLayout.vue'),
    meta: { requiresAuth: true } satisfies RouteMeta,
    children: [
      // Dashboard & Home
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/views/HomeView.vue'),
        meta: { title: 'Dashboard' } satisfies RouteMeta
      },
      {
        path: 'home',
        name: 'home',
        component: () => import('@/views/HomeView.vue'),
        meta: { title: 'Home' } satisfies RouteMeta
      },

      // Core Game Routes
      {
        path: 'city',
        name: 'city',
        component: () => import('@/views/plugins/CityView.vue'),
        meta: { title: 'City' } satisfies RouteMeta
      },
      {
        path: 'inventory',
        name: 'inventory',
        component: () => import('@/views/plugins/InventoryView.vue'),
        meta: { title: 'Inventory' } satisfies RouteMeta
      },
      {
        path: 'missions',
        name: 'missions',
        component: () => import('@/views/plugins/MissionsView.vue'),
        meta: { title: 'Missions' } satisfies RouteMeta
      },
      {
        path: 'combat',
        name: 'combat',
        component: () => import('@/views/plugins/CombatView.vue'),
        meta: { title: 'Combat' } satisfies RouteMeta
      },
      {
        path: 'scavenge',
        name: 'scavenge',
        component: () => import('@/views/plugins/ScavengeView.vue'),
        meta: { title: 'Scavenge' } satisfies RouteMeta
      },
      {
        path: 'travel',
        name: 'travel',
        component: () => import('@/views/plugins/TravelView.vue'),
        meta: { title: 'Travel' } satisfies RouteMeta
      },
      {
        path: 'skills',
        name: 'skills',
        component: () => import('@/views/plugins/SkillsView.vue'),
        meta: { title: 'Skills' } satisfies RouteMeta
      },
      {
        path: 'forums',
        name: 'forums',
        component: () => import('@/views/plugins/ForumView.vue'),
        meta: { title: 'Forums' } satisfies RouteMeta
      },
      {
        path: 'profile',
        name: 'profile',
        component: () => import('@/views/plugins/ProfileView.vue'),
        meta: { title: 'Profile' } satisfies RouteMeta
      },

      // Crime & Action Routes
      {
        path: 'crimes',
        name: 'crimes',
        component: () => import('@/views/plugins/CrimesView.vue'),
        meta: { title: 'Crimes' } satisfies RouteMeta
      },
      {
        path: 'crimes/:id',
        name: 'crime-action',
        component: () => import('@/views/plugins/CrimeActionView.vue'),
        meta: { title: 'Crime Action' } satisfies RouteMeta
      },
      {
        path: 'gym',
        name: 'gym',
        component: () => import('@/views/plugins/GymView.vue'),
        meta: { title: 'Gym' } satisfies RouteMeta
      },
      {
        path: 'hospital',
        name: 'hospital',
        component: () => import('@/views/plugins/HospitalView.vue'),
        meta: { title: 'Hospital' } satisfies RouteMeta
      },
      {
        path: 'bank',
        name: 'bank',
        component: () => import('@/views/plugins/BankView.vue'),
        meta: { title: 'Bank' } satisfies RouteMeta
      },
      {
        path: 'drugs',
        name: 'drugs',
        component: () => import('@/views/plugins/DrugsView.vue'),
        meta: { title: 'Drugs' } satisfies RouteMeta
      },
      {
        path: 'theft',
        name: 'theft',
        component: () => import('@/views/plugins/TheftView.vue'),
        meta: { title: 'Theft' } satisfies RouteMeta
      },
      {
        path: 'racing',
        name: 'racing',
        component: () => import('@/views/plugins/RacingView.vue'),
        meta: { title: 'Racing' } satisfies RouteMeta
      },
      {
        path: 'jail',
        name: 'jail',
        component: () => import('@/views/plugins/JailView.vue'),
        meta: { title: 'Jail' } satisfies RouteMeta
      },
      {
        path: 'properties',
        name: 'properties',
        component: () => import('@/views/plugins/PropertiesView.vue'),
        meta: { title: 'Properties' } satisfies RouteMeta
      },
      {
        path: 'bounty',
        name: 'bounty',
        component: () => import('@/views/plugins/BountyView.vue'),
        meta: { title: 'Bounty' } satisfies RouteMeta
      },
      {
        path: 'detective',
        name: 'detective',
        component: () => import('@/views/plugins/DetectiveView.vue'),
        meta: { title: 'Detective' } satisfies RouteMeta
      },
      {
        path: 'bullets',
        name: 'bullets',
        component: () => import('@/views/plugins/BulletsView.vue'),
        meta: { title: 'Bullets' } satisfies RouteMeta
      },
      {
        path: 'gang',
        name: 'gang',
        component: () => import('@/views/plugins/GangView.vue'),
        meta: { title: 'Gang' } satisfies RouteMeta
      },
      {
        path: 'organized-crime',
        name: 'organized-crime',
        component: () => import('@/views/plugins/OrganizedCrimeView.vue'),
        meta: { title: 'Organized Crime' } satisfies RouteMeta
      },

      // Social & Communication
      {
        path: 'chat',
        name: 'chat',
        component: () => import('@/views/plugins/ChatView.vue'),
        meta: { title: 'Chat' } satisfies RouteMeta
      },
      {
        path: 'messaging',
        name: 'messaging',
        component: () => import('@/views/plugins/MessagingView.vue'),
        meta: { title: 'Messages' } satisfies RouteMeta
      },

      // Progression & Stats
      {
        path: 'achievements',
        name: 'achievements',
        component: () => import('@/views/plugins/AchievementsView.vue'),
        meta: { title: 'Achievements' } satisfies RouteMeta
      },
      {
        path: 'leaderboards',
        name: 'leaderboards',
        component: () => import('@/views/plugins/LeaderboardsView.vue'),
        meta: { title: 'Leaderboards' } satisfies RouteMeta
      },
      {
        path: 'activity',
        name: 'activity',
        component: () => import('@/views/plugins/ActivityView.vue'),
        meta: { title: 'Activity' } satisfies RouteMeta
      },
      {
        path: 'employment',
        name: 'employment',
        component: () => import('@/views/plugins/EmploymentView.vue'),
        meta: { title: 'Employment' } satisfies RouteMeta
      },
      {
        path: 'education',
        name: 'education',
        component: () => import('@/views/plugins/EducationView.vue'),
        meta: { title: 'Education' } satisfies RouteMeta
      },
      {
        path: 'quests',
        name: 'quests',
        component: () => import('@/views/plugins/QuestsView.vue'),
        meta: { title: 'Quests' } satisfies RouteMeta
      },
      {
        path: 'alliances',
        name: 'alliances',
        component: () => import('@/views/plugins/AlliancesView.vue'),
        meta: { title: 'Alliances' } satisfies RouteMeta
      },

      // Economy & Trading
      {
        path: 'shop',
        name: 'shop',
        component: () => import('@/views/plugins/ShopView.vue'),
        meta: { title: 'Shop' } satisfies RouteMeta
      },
      {
        path: 'market',
        name: 'market',
        component: () => import('@/views/plugins/MarketView.vue'),
        meta: { title: 'Market' } satisfies RouteMeta
      },
      {
        path: 'stocks',
        name: 'stocks',
        component: () => import('@/views/plugins/StocksView.vue'),
        meta: { title: 'Stocks' } satisfies RouteMeta
      },
      {
        path: 'casino',
        name: 'casino',
        component: () => import('@/views/plugins/CasinoView.vue'),
        meta: { title: 'Casino' } satisfies RouteMeta
      },

      // Exploration & Events
      {
        path: 'explore',
        name: 'explore',
        component: () => import('@/views/plugins/ExploreView.vue'),
        meta: { title: 'Explore' } satisfies RouteMeta
      },
      {
        path: 'hunting',
        name: 'hunting',
        component: () => import('@/views/plugins/HuntingView.vue'),
        meta: { title: 'Hunting' } satisfies RouteMeta
      },
      {
        path: 'events',
        name: 'events',
        component: () => import('@/views/plugins/EventsView.vue'),
        meta: { title: 'Events' } satisfies RouteMeta
      },
      {
        path: 'tournament',
        name: 'tournament',
        component: () => import('@/views/plugins/TournamentView.vue'),
        meta: { title: 'Tournament' } satisfies RouteMeta
      },

      // Information
      {
        path: 'wiki',
        name: 'wiki',
        component: () => import('@/views/plugins/WikiView.vue'),
        meta: { title: 'Wiki' } satisfies RouteMeta
      },
      {
        path: 'tickets',
        name: 'tickets',
        component: () => import('@/views/TicketsView.vue'),
        meta: { title: 'Support Tickets' } satisfies RouteMeta
      },

      // User Settings
      {
        path: 'settings',
        name: 'settings',
        component: () => import('@/views/SettingsView.vue'),
        meta: { title: 'Settings' } satisfies RouteMeta
      },
      {
        path: 'notifications',
        name: 'notifications',
        component: () => import('@/views/NotificationsView.vue'),
        meta: { title: 'Notifications' } satisfies RouteMeta
      },
      {
        path: 'announcements',
        name: 'announcements',
        component: () => import('@/views/AnnouncementsView.vue'),
        meta: { title: 'Announcements' } satisfies RouteMeta
      },
      {
        path: 'daily-rewards',
        name: 'daily-rewards',
        component: () => import('@/views/DailyRewardsView.vue'),
        meta: { title: 'Daily Rewards' } satisfies RouteMeta
      }
    ]
  },

  // 404 Catch-all route
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { title: 'Page Not Found' } satisfies RouteMeta
  }
]

/**
 * Create router instance
 */
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
    return { top: 0 }
  }
})

/**
 * Initialize dynamic plugin routes
 * Called after plugins are loaded from the backend
 */
export async function initializePluginRoutes(): Promise<void> {
  const pluginsStore = usePluginsStore()

  // Fetch enabled plugins if not already loaded
  if (!pluginsStore.loaded) {
    await pluginsStore.fetchPlugins()
  }

  // Register dynamic routes from plugins
  if (pluginsStore.routes.length > 0) {
    registerPluginRoutes(router, pluginsStore.routes)
  }
}

/**
 * Navigation guard for authentication and plugin routes
 */
router.beforeEach(async (to, _from, next) => {
  const user = localStorage.getItem('user')
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)

  // Update document title if route has a title
  const title = to.meta?.title as string | undefined
  if (title) {
    document.title = `${title} | OpenPBBG`
  }

  // Initialize plugin routes on first authenticated navigation
  const pluginsStore = usePluginsStore()
  if (user && !pluginsStore.loaded) {
    await initializePluginRoutes()
  }

  if (requiresAuth && !user) {
    // Redirect to login if auth required but not authenticated
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }

  if (requiresGuest && user) {
    // Redirect to dashboard if guest route but already authenticated
    next({ name: 'dashboard' })
    return
  }

  // Check if route requires an enabled plugin
  const pluginSlug = to.meta?.plugin as string | undefined
  if (pluginSlug && !pluginsStore.isEnabled(pluginSlug)) {
    // Plugin not enabled, redirect to dashboard
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router
