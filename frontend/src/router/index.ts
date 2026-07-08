import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { RouteMeta } from '@/types/router'
import '@/types/router'

export type { RouteMeta } from '@/types/router'

const peptidePage = () => import('@/views/PeptideCommunityView.vue')
const calculatorPage = () => import('@/views/CalculatorView.vue')

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/home',
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { title: 'Login', requiresGuest: true } satisfies RouteMeta,
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { title: 'Register', requiresGuest: true } satisfies RouteMeta,
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/ForgotPasswordView.vue'),
    meta: { title: 'Forgot Password' } satisfies RouteMeta,
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/views/ResetPasswordView.vue'),
    meta: { title: 'Reset Password' } satisfies RouteMeta,
  },
  {
    path: '/terms',
    name: 'terms',
    component: () => import('@/views/TermsOfService.vue'),
    meta: { title: 'Terms of Service' } satisfies RouteMeta,
  },
  {
    path: '/privacy',
    name: 'privacy',
    component: () => import('@/views/PrivacyPolicy.vue'),
    meta: { title: 'Privacy Policy' } satisfies RouteMeta,
  },
  {
    path: '/community-rules',
    name: 'community-rules',
    component: () => import('@/views/CommunityRules.vue'),
    meta: { title: 'Community Rules' } satisfies RouteMeta,
  },
  {
    path: '/cookie-settings',
    name: 'cookie-settings',
    component: () => import('@/views/CookieSettingsView.vue'),
    meta: { title: 'Cookie Settings' } satisfies RouteMeta,
  },
  {
    path: '/dmca',
    name: 'dmca',
    component: () => import('@/views/DmcaView.vue'),
    meta: { title: 'DMCA Policy' } satisfies RouteMeta,
  },
  {
    path: '/data-deletion',
    name: 'data-deletion',
    component: () => import('@/views/DataDeletionView.vue'),
    meta: { title: 'Data Deletion Request' } satisfies RouteMeta,
  },
  {
    path: '/',
    component: () => import('@/layouts/CoreLayout.vue'),
    meta: {} satisfies RouteMeta,
    children: [
      { path: 'dashboard', name: 'dashboard', component: peptidePage, meta: { title: 'Home', page: 'home' } satisfies RouteMeta },
      { path: 'calculator', name: 'calculator', component: calculatorPage, meta: { title: 'Peptide Calculator', page: 'calculator', description: 'Calculate peptide dosages, reconstitution volumes and injection amounts.' } satisfies RouteMeta },
      { path: 'home', name: 'home', component: peptidePage, meta: { title: 'Home', page: 'home', description: 'Peptide research community with independent vendor reviews, lab results, and educational discussions.' } satisfies RouteMeta },
      { path: 'pricing', name: 'pricing', component: peptidePage, meta: { title: 'Pricing', page: 'pricing' } satisfies RouteMeta },
      { path: 'discussions', name: 'discussions', component: peptidePage, meta: { title: 'Discussions', page: 'discussions', description: 'Community discussions about peptide research, harm reduction, and educational topics.' } satisfies RouteMeta },
      { path: 'discussions/:slug', name: 'discussion-detail', component: peptidePage, meta: { title: 'Discussion', page: 'discussionDetail', description: 'Community discussion about peptide research.' } satisfies RouteMeta },
      { path: 'lab-results', name: 'lab-results', component: peptidePage, meta: { title: 'Lab Results', page: 'labResults' } satisfies RouteMeta },
      { path: 'lab-results/:slug', name: 'lab-report', component: peptidePage, meta: { title: 'Lab Result Report', page: 'labReport' } satisfies RouteMeta },
      { path: 'vendor-reviews', name: 'vendor-reviews', component: peptidePage, meta: { title: 'Vendor Reviews', page: 'vendorReviews' } satisfies RouteMeta },
      { path: 'vendor-reviews/:slug', name: 'vendor-detail', component: peptidePage, meta: { title: 'Vendor Reviews', page: 'vendorDetail' } satisfies RouteMeta },
      { path: 'vendor-reviews/:slug/reviews', redirect: to => `/vendor-reviews/${String(to.params.slug)}` },
      { path: 'vendor-reviews/:slug/review', name: 'vendor-review-modal', component: peptidePage, meta: { title: 'Write a Review', page: 'reviewModal' } satisfies RouteMeta },
      { path: 'vendor-portal', name: 'vendor-portal', component: peptidePage, meta: { title: 'Vendor Portal', page: 'vendorPortal' } satisfies RouteMeta },
      { path: 'research-library', name: 'research-library', component: peptidePage, meta: { title: 'Research Library', page: 'researchLibrary' } satisfies RouteMeta },
      { path: 'research-library/new', name: 'research-new', component: peptidePage, meta: { title: 'New Research Article', page: 'contentStudio', contentType: 'research' } satisfies RouteMeta },
      { path: 'research-library/:slug', name: 'research-article', component: peptidePage, meta: { title: 'Research Article', page: 'researchArticle' } satisfies RouteMeta },
      { path: 'guides', name: 'guides', component: peptidePage, meta: { title: 'Guides & FAQ', page: 'guides' } satisfies RouteMeta },
      { path: 'guides/new', name: 'guide-new', component: peptidePage, meta: { title: 'New Guide', page: 'contentStudio', contentType: 'guide' } satisfies RouteMeta },
      { path: 'guides/faqs/new', name: 'faq-new', component: peptidePage, meta: { title: 'New FAQ', page: 'contentStudio', contentType: 'faq' } satisfies RouteMeta },
      { path: 'guides/:slug', name: 'guide-detail', component: peptidePage, meta: { title: 'Guide', page: 'guideDetail' } satisfies RouteMeta },
      { path: 'content-studio', name: 'content-studio', component: peptidePage, meta: { title: 'Content Studio', page: 'contentStudio' } satisfies RouteMeta },
      { path: 'members', name: 'members', component: peptidePage, meta: { title: 'Members', page: 'members' } satisfies RouteMeta },
      { path: 'members/:slug', name: 'member-detail', component: peptidePage, meta: { title: 'Member Profile', page: 'memberDetail' } satisfies RouteMeta },
      { path: 'messages', name: 'messages', component: peptidePage, meta: { title: 'Messages', page: 'messages' } satisfies RouteMeta },
      { path: 'announcements', name: 'announcements', component: peptidePage, meta: { title: 'Announcements', page: 'announcements' } satisfies RouteMeta },
      { path: 'announcements/new', name: 'announcement-new', component: peptidePage, meta: { title: 'Create New Announcement', page: 'announcementNew' } satisfies RouteMeta },
      { path: 'announcements/:slug', name: 'announcement-detail', component: peptidePage, meta: { title: 'Announcement', page: 'announcementDetail' } satisfies RouteMeta },
      { path: 'notifications', name: 'notifications', component: peptidePage, meta: { title: 'Notifications', page: 'notifications' } satisfies RouteMeta },
      { path: 'notifications/:slug', name: 'notification-detail', component: peptidePage, meta: { title: 'Notification', page: 'notificationDetail' } satisfies RouteMeta },
      { path: 'settings', name: 'settings', component: peptidePage, meta: { title: 'Account Settings', page: 'settingsProfile' } satisfies RouteMeta },
      { path: 'settings/account', name: 'settings-account', component: peptidePage, meta: { title: 'Account Settings', page: 'settingsAccount' } satisfies RouteMeta },
      { path: 'settings/security', name: 'settings-security', component: peptidePage, meta: { title: 'Security', page: 'settingsSecurity' } satisfies RouteMeta },
      { path: 'settings/privacy', name: 'settings-privacy', component: peptidePage, meta: { title: 'Privacy', page: 'settingsPrivacy' } satisfies RouteMeta },
      { path: 'settings/notifications', name: 'settings-notifications', component: peptidePage, meta: { title: 'Notification Settings', page: 'settingsNotifications' } satisfies RouteMeta },
      { path: 'settings/preferences', name: 'settings-preferences', component: peptidePage, meta: { title: 'Preferences', page: 'settingsPreferences' } satisfies RouteMeta },
      { path: 'settings/blocked-users', name: 'settings-blocked-users', component: peptidePage, meta: { title: 'Blocked Users', page: 'settingsBlocked' } satisfies RouteMeta },
      { path: 'settings/api-tokens', name: 'settings-api-tokens', component: peptidePage, meta: { title: 'API Tokens', page: 'settingsApi' } satisfies RouteMeta },
      { path: 'settings/sessions', name: 'settings-sessions', component: peptidePage, meta: { title: 'Sessions', page: 'settingsSessions' } satisfies RouteMeta },
      { path: 'settings/billing', name: 'settings-billing', component: peptidePage, meta: { title: 'Billing', page: 'settingsBilling' } satisfies RouteMeta },
      { path: 'settings/danger-zone', name: 'settings-danger-zone', component: peptidePage, meta: { title: 'Danger Zone', page: 'settingsDanger' } satisfies RouteMeta },
      { path: 'settings/:section', name: 'settings-placeholder', component: peptidePage, meta: { title: 'Account Settings', page: 'settingsProfile' } satisfies RouteMeta },
      { path: 'saved', name: 'saved', component: peptidePage, meta: { title: 'Saved', page: 'saved' } satisfies RouteMeta },
      { path: 'search', name: 'search', component: peptidePage, meta: { title: 'Search', page: 'search' } satisfies RouteMeta },
      { path: 'telegram-updates', name: 'telegram-updates', component: peptidePage, meta: { title: 'Telegram Updates', page: 'telegramUpdates' } satisfies RouteMeta },
      { path: 'profile', redirect: '/settings' },
      { path: 'activity', redirect: '/members' },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { title: 'Page Not Found' } satisfies RouteMeta,
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    return savedPosition ?? { top: 0 }
  },
})

router.beforeEach((to) => {
  const authStore = useAuthStore()
  const hasStoredToken = Boolean(localStorage.getItem('auth_token'))
  const title = to.meta?.title as string | undefined
  const description = to.meta?.description as string | undefined
  const ogImage = to.meta?.ogImage as string | undefined
  const siteName = 'Peptide Vendors'

  if (title) {
    document.title = `${title} | ${siteName}`
  }

  // Update meta description
  let descEl = document.querySelector('meta[name="description"]')
  if (description) {
    if (!descEl) {
      descEl = document.createElement('meta')
      descEl.setAttribute('name', 'description')
      document.head.appendChild(descEl)
    }
    descEl.setAttribute('content', description)
  } else if (descEl) {
    descEl.remove()
  }

  // Update OG title
  let ogTitle = document.querySelector('meta[property="og:title"]')
  if (title) {
    if (!ogTitle) {
      ogTitle = document.createElement('meta')
      ogTitle.setAttribute('property', 'og:title')
      document.head.appendChild(ogTitle)
    }
    ogTitle.setAttribute('content', `${title} | ${siteName}`)
  }

  // Update OG description
  let ogDesc = document.querySelector('meta[property="og:description"]')
  if (description) {
    if (!ogDesc) {
      ogDesc = document.createElement('meta')
      ogDesc.setAttribute('property', 'og:description')
      document.head.appendChild(ogDesc)
    }
    ogDesc.setAttribute('content', description)
  }

  // Update OG image
  let ogImg = document.querySelector('meta[property="og:image"]')
  if (ogImage) {
    if (!ogImg) {
      ogImg = document.createElement('meta')
      ogImg.setAttribute('property', 'og:image')
      document.head.appendChild(ogImg)
    }
    ogImg.setAttribute('content', ogImage)
  } else if (ogImg) {
    ogImg.remove()
  }

  // Set OG url
  let ogUrl = document.querySelector('meta[property="og:url"]')
  if (!ogUrl) {
    ogUrl = document.createElement('meta')
    ogUrl.setAttribute('property', 'og:url')
    document.head.appendChild(ogUrl)
  }
  ogUrl.setAttribute('content', window.location.href)

  // Ensure og:type
  let ogType = document.querySelector('meta[property="og:type"]')
  if (!ogType) {
    ogType = document.createElement('meta')
    ogType.setAttribute('property', 'og:type')
    ogType.setAttribute('content', 'website')
    document.head.appendChild(ogType)
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated && !hasStoredToken) {
    return {
      path: '/login',
      query: { redirect: to.fullPath },
    }
  }

  if (to.meta.requiresGuest && (authStore.isAuthenticated || hasStoredToken)) {
    return '/home'
  }
})

export async function initializePluginRoutes(): Promise<void> {
  // Kept as a no-op compatibility hook for code that imports the old initializer.
}

export default router
