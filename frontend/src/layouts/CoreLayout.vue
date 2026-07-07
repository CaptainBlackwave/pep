<template>
  <div class="pv-shell" :class="{ 'pv-shell--open': sidebarOpen }">
    <header class="pv-topbar">
      <router-link to="/home" class="pv-brand" aria-label="Peptide Vendors home">
        <span class="pv-brand-mark">PV</span>
        <span class="pv-brand-text">
          <strong>Peptide</strong>
          <span>Vendors</span>
        </span>
      </router-link>

      <button class="pv-menu-button" type="button" aria-label="Toggle navigation" @click="sidebarOpen = !sidebarOpen">
        <PvIcon name="menu" />
      </button>

      <form class="pv-search" @submit.prevent="submitTopbarSearch">
        <PvIcon name="search" />
        <input v-model="topbarSearch" :placeholder="searchPlaceholder" type="search">
        <kbd>/</kbd>
      </form>

      <div v-if="authStore.isAuthenticated" class="pv-account">
        <router-link to="/notifications" class="pv-icon-button" aria-label="Notifications">
          <PvIcon name="bell" />
          <span v-if="notificationCount > 0" class="pv-badge">{{ notificationCount }}</span>
        </router-link>
        <router-link :to="{ path: '/messages', query: { inbox: '1' } }" class="pv-icon-button" aria-label="Messages">
          <PvIcon name="mail" />
        </router-link>
        <div class="pv-account-menu">
          <button
            type="button"
            class="pv-user-chip"
            aria-label="Account menu"
            :aria-expanded="accountMenuOpen"
            @click.stop="accountMenuOpen = !accountMenuOpen"
          >
            <span class="pv-avatar pv-avatar--small">
              <img v-if="accountAvatar" :src="accountAvatar" :alt="accountLabel">
              <template v-else>{{ accountInitial }}</template>
            </span>
            <span>{{ accountLabel }}</span>
            <PvIcon name="chevron" />
          </button>
          <div
            v-if="accountMenuOpen"
            class="pv-account-dropdown"
          >
            <router-link
              to="/settings"
              @click="accountMenuOpen = false"
            >
              Account Settings
            </router-link>
            <a
              v-if="authStore.user?.roles?.includes('admin') || authStore.user?.roles?.includes('moderator')"
              href="/admin/dashboard"
              @click="accountMenuOpen = false"
            >
              Admin Panel
            </a>
            <button
              type="button"
              @click="handleLogout"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
      <div v-else class="pv-account pv-account--guest">
        <router-link to="/login" class="pv-small-button">Sign In</router-link>
        <router-link to="/register" class="pv-primary-button">Register</router-link>
      </div>
    </header>

    <aside class="pv-sidebar">
      <nav class="pv-nav" aria-label="Main navigation">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="pv-nav-item"
          :class="{ active: isActive(item) }"
          @click="sidebarOpen = false"
        >
          <PvIcon :name="item.icon" />
          <span>{{ item.label }}</span>
        </router-link>
      </nav>

      <button v-if="authStore.isAuthenticated" type="button" class="pv-nav-item pv-logout-mobile" @click="handleLogout">
        <PvIcon name="close" />
        <span>Logout</span>
      </button>

      <section v-if="showUpgradePrompt" class="pv-upgrade-card">
        <PvIcon name="star" />
        <h2>Upgrade to Premium</h2>
        <p>Unlock vendor reviews, lab results, messaging and more.</p>
        <router-link to="/pricing" class="pv-primary-button" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none">View Plans</router-link>
      </section>
      <section class="pv-telegram-card">
        <PvIcon name="send" />
        <h2>Join our Telegram</h2>
        <p>Get instant updates, alerts and community chat on Telegram.</p>
        <a :href="telegramUrl" target="_blank" rel="noopener noreferrer" class="pv-primary-button" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none">Join Telegram</a>
      </section>
      <footer class="pv-sidebar-footer">
        <p>&copy; {{ currentYear }} Peptide Vendors.<br>All rights reserved.</p>
        <div>
          <router-link to="/terms">Terms of Service</router-link>
          <router-link to="/privacy">Privacy Policy</router-link>
          <router-link to="/community-rules">Community Rules</router-link>
          <a href="#" @click.prevent="resetCookieConsent" style="cursor:pointer">Cookie Settings</a>
        </div>
      </footer>
    </aside>

    <main class="pv-main">
      <router-view />
    </main>
    <div class="pv-scrim" @click="sidebarOpen = false"></div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PvIcon from '@/components/peptide/PvIcon.vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()
const sidebarOpen = ref(false)
const accountMenuOpen = ref(false)
const topbarSearch = ref('')
const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()
const currentYear = new Date().getFullYear()
const telegramUrl = ref('https://t.me/peptidevendors')
const membershipDisabled = ref(true)
const showUpgradePrompt = computed(() => authStore.isAuthenticated && authStore.user?.tier === 'free' && !membershipDisabled.value)

function resetCookieConsent(): void {
  localStorage.removeItem('cookie_consent')
  window.location.reload()
}
const navItems = computed(() => {
  const isAuth = authStore.isAuthenticated
  const isFree = !isAuth || (authStore.user?.tier === 'free' && !membershipDisabled.value)
  const allItems = [
    { to: '/home', label: 'Home', icon: 'home', match: ['/home', '/dashboard'] },
    { to: '/discussions', label: 'Discussions', icon: 'discussions', match: ['/discussions'] },
    { to: '/lab-results', label: 'Lab Results', icon: 'flask', match: ['/lab-results'] },
    { to: '/vendor-reviews', label: 'Vendors', icon: 'star', match: ['/vendor-reviews'] },
    { to: '/vendor-portal', label: 'Vendor Portal', icon: 'box', match: ['/vendor-portal'] },
    { to: '/research-library', label: 'Research Library', icon: 'library', match: ['/research-library'] },
    { to: '/guides', label: 'Guides & FAQ', icon: 'question', match: ['/guides'] },
    { to: '/members', label: 'Members', icon: 'users', match: ['/members'] },
    { to: '/announcements', label: 'Announcements', icon: 'megaphone', match: ['/announcements'] },
    { to: '/notifications', label: 'Notifications', icon: 'bell', match: ['/notifications'] },
  ]
  return allItems.filter(item => {
    if (isFree && ['/lab-results', '/vendor-reviews', '/vendor-portal', '/members', '/notifications'].includes(item.to)) {
      return false
    }
    return true
  })
})

const isActive = (item: { match: string[] }) => {
  return item.match.some(path => route.path === path || route.path.startsWith(`${path}/`))
}

const searchPlaceholder = computed(() => {
  const path = route.path
  if (path.startsWith('/lab-results')) return 'Search lab results...'
  if (path.startsWith('/vendor-reviews')) return 'Search vendors, products, reviews...'
  if (path.startsWith('/vendor-portal')) return 'Search vendors...'
  if (path.startsWith('/research-library')) return 'Search research library...'
  if (path.startsWith('/guides')) return 'Search guides & FAQ...'
  if (path.startsWith('/members')) return 'Search members...'
  if (path.startsWith('/messages')) return 'Search messages...'
  return 'Search discussions, members, guides...'
})

const accountLabel = computed(() => authStore.user?.username || authStore.user?.name || 'Account')
const accountInitial = computed(() => accountLabel.value.charAt(0).toUpperCase())
const accountAvatar = computed(() => {
  const user = authStore.user as {
    avatar?: string | null
    profile_photo_path?: string | null
    profile_picture?: string | null
  } | null

  return assetUrl(String(user?.avatar || user?.profile_photo_path || user?.profile_picture || ''))
})
const notificationCount = computed(() => notificationsStore.unreadCount)

const backendAssetOrigin = () => {
  const configured = String(import.meta.env.VITE_API_URL || '').replace(/\/$/, '')

  if (configured) {
    return configured
  }

  if (window.location.port.startsWith('517')) {
    return `${window.location.protocol}//${window.location.hostname}:8001`
  }

  return window.location.origin
}

const assetUrl = (value: string) => {
  if (!value || /^(https?:|data:|blob:)/i.test(value)) {
    return value
  }

  if (value.startsWith('/storage/')) {
    return `${backendAssetOrigin()}${value}`
  }

  return value
}

const topbarSearchTarget = computed(() => '/search')

const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement

  if (!target.closest('.pv-account-menu')) {
    accountMenuOpen.value = false
  }
}

const handleLogout = async () => {
  accountMenuOpen.value = false
  await authStore.logout()
  notificationsStore.clearAll()
  await router.push('/login')
}

const submitTopbarSearch = async () => {
  const q = topbarSearch.value.trim()
  await router.push({
    path: topbarSearchTarget.value,
    query: q ? { q } : {},
  })
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)

  if (authStore.isAuthenticated) {
    void notificationsStore.fetchUnreadCount()
  }
  api.get<{ telegram_url?: string; membership_enabled?: boolean }>('/api/v1/settings/public').then(r => {
    if (r.data?.telegram_url) telegramUrl.value = r.data.telegram_url
    membershipDisabled.value = r.data?.membership_enabled === false
  }).catch(() => {})
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

watch(() => authStore.isAuthenticated, (isAuthenticated) => {
  if (isAuthenticated) {
    void notificationsStore.fetchUnreadCount()
  } else {
    notificationsStore.clearAll()
  }
})

watch(() => route.query.q, (value) => {
  topbarSearch.value = typeof value === 'string' ? value : ''
}, { immediate: true })
</script>

<style scoped>
.pv-account-menu {
  position: relative;
}

.pv-account-menu .pv-user-chip {
  border: 0;
  background: transparent;
}

.pv-account-dropdown {
  position: absolute;
  right: 0;
  top: calc(100% + 10px);
  z-index: 60;
  min-width: 190px;
  overflow: hidden;
  border: 1px solid var(--pv-border);
  border-radius: 8px;
  background: rgba(13, 17, 27, 0.98);
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.42);
}

.pv-account-dropdown a,
.pv-account-dropdown button {
  display: block;
  width: 100%;
  padding: 12px 14px;
  background: transparent;
  color: var(--pv-text);
  text-align: left;
}

.pv-account-dropdown a:hover,
.pv-account-dropdown button:hover {
  background: var(--pv-purple-soft);
}
</style>
