import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import DashboardLayout from '../views/DashboardLayout.vue'
import DashboardHome from '../views/DashboardHome.vue'
import UsersView from '../views/UsersView.vue'

const router = createRouter({
  history: createWebHistory('/admin'),
  routes: [
    {
      path: '/',
      redirect: '/login'
    },
    // Installer Routes
    {
      path: '/install',
      name: 'installer-welcome',
      component: () => import('../views/Installer/Welcome.vue'),
      meta: { requiresGuest: true, isInstaller: true }
    },
    {
      path: '/install/requirements',
      name: 'installer-requirements',
      component: () => import('../views/Installer/Requirements.vue'),
      meta: { requiresGuest: true, isInstaller: true }
    },
    {
      path: '/install/database',
      name: 'installer-database',
      component: () => import('../views/Installer/Database.vue'),
      meta: { requiresGuest: true, isInstaller: true }
    },
    {
      path: '/install/settings',
      name: 'installer-settings',
      component: () => import('../views/Installer/Settings.vue'),
      meta: { requiresGuest: true, isInstaller: true }
    },
    {
      path: '/install/setup-admin',
      name: 'installer-admin',
      component: () => import('../views/Installer/Admin.vue'),
      meta: { requiresGuest: true, isInstaller: true }
    },
    {
      path: '/install/install',
      name: 'installer-install',
      component: () => import('../views/Installer/Install.vue'),
      meta: { requiresGuest: true, isInstaller: true }
    },
    {
      path: '/install/complete',
      name: 'installer-complete',
      component: () => import('../views/Installer/Complete.vue'),
      meta: { requiresGuest: true, isInstaller: true }
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { requiresGuest: true }
    },
    {
      path: '/license-required',
      name: 'license-required',
      component: () => import('../views/LicenseGateView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/dashboard',
      component: DashboardLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: DashboardHome
        },
        {
          path: '/notifications',
          name: 'notifications',
          component: () => import('../views/NotificationsView.vue')
        },
        {
          path: '/calendar',
          name: 'calendar',
          component: () => import('../views/CalendarView.vue')
        },
        {
          path: '/tasks',
          name: 'tasks',
          component: () => import('../views/TasksView.vue')
        },
        // User Management
        {
          path: '/users',
          name: 'users',
          component: UsersView
        },
        {
          path: '/user-tools',
          name: 'user-tools',
          component: () => import('../views/UserToolsView.vue')
        },
        {
          path: '/roles',
          name: 'roles',
          component: () => import('../views/RolesView.vue')
        },
        // Configuration
        {
          path: '/settings',
          name: 'settings',
          component: () => import('../views/SettingsView.vue')
        },
        {
          path: '/license',
          name: 'license',
          component: () => import('../views/LicenseView.vue')
        },
        {
          path: '/email-settings',
          name: 'email-settings',
          component: () => import('../views/EmailSettingsView.vue')
        },
        {
          path: '/plugin-settings',
          name: 'plugin-settings',
          component: () => import('../views/PluginsView.vue')
        },
        // Communication
        {
          path: '/announcements',
          name: 'announcements',
          component: () => import('../views/AnnouncementsView.vue')
        },
        {
          path: '/faq',
          name: 'faq',
          component: () => import('../views/FaqView.vue')
        },
        {
          path: '/wiki',
          name: 'wiki',
          component: () => import('../views/WikiView.vue')
        },
        {
          path: '/forum-categories',
          name: 'forum-categories',
          component: () => import('../views/ForumCategoriesView.vue')
        },
        {
          path: '/events',
          name: 'events',
          component: () => import('../views/EventsView.vue')
        },
        {
          path: '/chat-channels',
          name: 'chat-channels',
          component: () => import('../views/ChatChannelsView.vue')
        },
        // Support
        {
          path: '/tickets/:id',
          name: 'ticket-detail',
          component: () => import('../views/TicketDetailView.vue')
        },
        {
          path: '/tickets',
          name: 'tickets',
          component: () => import('../views/TicketListView.vue')
        },
        {
          path: '/ticket-categories',
          name: 'ticket-categories',
          component: () => import('../views/TicketCategoriesView.vue')
        },
        // System
        {
          path: '/ip-bans',
          name: 'ip-bans',
          component: () => import('../views/IpBansView.vue')
        },
        {
          path: '/error-logs',
          name: 'error-logs',
          component: () => import('../views/ErrorLogsView.vue')
        },
        {
          path: '/activity-logs',
          name: 'activity-logs',
          component: () => import('../views/ActivityLogsView.vue')
        },
        {
          path: '/webhooks',
          name: 'webhooks',
          component: () => import('../views/WebhooksView.vue')
        },
        {
          path: '/security',
          name: 'security',
          component: () => import('../views/SecuritySettingsView.vue')
        },
        {
          path: '/backups',
          name: 'backups',
          component: () => import('../views/BackupManagerView.vue')
        },
        {
          path: '/system-health',
          name: 'system-health',
          component: () => import('../views/SystemHealthView.vue')
        },
        {
          path: '/api-keys',
          name: 'api-keys',
          component: () => import('../views/ApiKeysView.vue')
        }
      ]
    }
  ]
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('admin_token')
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)

  if (requiresAuth && !token) {
    next('/login')
  } else if (requiresGuest && token) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
