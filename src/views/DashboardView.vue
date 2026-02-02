<template>
  <div class="dashboard-container">
    <!-- Header -->
    <header class="dashboard-header">
      <div class="header-content">
        <div class="header-row">
          <div class="logo-container">
            <h1 class="logo-title">OpenPBBG</h1>
          </div>
          
          <!-- User Stats with XP Bar -->
          <div v-if="dashboardData" class="user-stats">
            <!-- Rank & XP Progress -->
            <div class="navbar-rank-xp">
              <div class="rank-info">
                <span class="rank-name">{{ dashboardData.player.current_rank?.name || 'Thug' }}</span>
              </div>
              <div class="navbar-xp-bar">
                <div class="xp-bar" :style="{ width: xpProgressPercent + '%' }"></div>
              </div>
              <span class="xp-label">{{ formatNumber(dashboardData.player.experience) }} XP</span>
            </div>
            
            <div class="stat-item">
              <span>💵</span>
              <span class="stat-value-green">${{ formatNumber(dashboardData.player.cash) }}</span>
            </div>
            <div class="stat-item">
              <span>❤️</span>
              <span class="stat-value-red">{{ dashboardData.player.health }}/{{ dashboardData.player.max_health }}</span>
            </div>
            <div class="stat-item">
              <span>⚡</span>
              <span class="stat-value-yellow">{{ dashboardData.player.energy }}/{{ dashboardData.player.max_energy }}</span>
            </div>
            <div class="stat-item location-item">
              <span>📍</span>
              <span>{{ dashboardData.player.location?.name || 'Unknown' }}</span>
            </div>
          </div>

          <div class="user-actions">
            <span class="username">{{ authStore.user?.username }}</span>
            <button
              @click="handleLogout"
              class="logout-button"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <div v-if="loading" class="loading-container">
        <div class="spinner"></div>
        <p class="loading-text">Loading...</p>
      </div>

      <div v-else-if="error" class="error-message">
        {{ error }}
      </div>

      <div v-else-if="dashboardData" class="dashboard-content">
        <!-- Announcements Banner -->
        <AnnouncementsBanner />
        
        <!-- Quick Navigation -->
        <div class="quick-nav">
          <router-link to="/profile" class="quick-nav-item">
            <span class="quick-icon">👤</span>
            <span>Profile</span>
          </router-link>
          <router-link to="/chat" class="quick-nav-item">
            <span class="quick-icon">💬</span>
            <span>Chat</span>
          </router-link>
          <router-link to="/activity" class="quick-nav-item">
            <span class="quick-icon">📰</span>
            <span>Activity</span>
          </router-link>
          <router-link to="/wiki" class="quick-nav-item">
            <span class="quick-icon">📚</span>
            <span>Wiki</span>
          </router-link>
        </div>
        
        <!-- Game Features Grid -->
        <div>
          <h3 class="features-title">Game Features</h3>
          <div class="features-grid">
            <!-- Unlocked Modules -->
            <router-link
              v-for="module in unlockedModules"
              :key="module.name"
              :to="getModuleRoute(module.name)"
              class="module-card"
            >
              <div class="module-icon">{{ getModuleIcon(module.name) }}</div>
              <div class="module-name">{{ module.display_name || module.name }}</div>
              <div class="module-description">{{ module.description || 'Play now' }}</div>
            </router-link>
            
            <!-- Locked Modules -->
            <div
              v-for="module in lockedModules"
              :key="module.name"
              class="module-card locked"
            >
              <div class="lock-overlay">
                <span class="lock-icon">🔒</span>
              </div>
              <div class="module-icon locked-icon">{{ getModuleIcon(module.name) }}</div>
              <div class="module-name">{{ module.display_name || module.name }}</div>
              <div class="module-unlock">Level {{ module.required_level }}</div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import AnnouncementsBanner from '@/components/AnnouncementsBanner.vue'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const error = ref(null)
const dashboardData = ref(null)

// Filter modules by lock status
const unlockedModules = computed(() => {
  if (!dashboardData.value?.modules) return []
  return dashboardData.value.modules.filter(m => !m.locked)
})

const lockedModules = computed(() => {
  if (!dashboardData.value?.modules) return []
  return dashboardData.value.modules.filter(m => m.locked)
})

// XP progress calculations
const nextRankXp = computed(() => {
  if (!dashboardData.value?.player?.current_rank) return 1000
  // Estimate next rank XP requirement (can be adjusted based on actual rank data)
  const currentRankXp = dashboardData.value.player.current_rank.experience_required || 0
  return currentRankXp + 500 // Simple progression
})

const xpProgressPercent = computed(() => {
  if (!dashboardData.value?.player) return 0
  const currentXp = dashboardData.value.player.experience || 0
  const prevRankXp = dashboardData.value.player.current_rank?.experience_required || 0
  const nextXp = nextRankXp.value
  
  if (nextXp <= prevRankXp) return 100
  const progress = ((currentXp - prevRankXp) / (nextXp - prevRankXp)) * 100
  return Math.min(Math.max(progress, 0), 100)
})

const moduleIcons = {
  'crimes': '🔫',
  'gym': '💪',
  'hospital': '🏥',
  'jail': '⛓️',
  'bank': '🏦',
  'combat': '⚔️',
  'bounties': '🎯',
  'detective': '🔍',
  'theft': '🚗',
  'travel': '✈️',
  'achievements': '🏆',
  'missions': '📋',
  'organized-crime': '🎭',
  'gangs': '👥',
  'forum': '💬',
  'inventory': '🎒',
  'shop': '🛒',
  'leaderboards': '📊',
  'racing': '🏁',
  'drugs': '💊',
  'properties': '🏠',
  'chat': '💬',
  'profile': '👤',
  'activity': '📰',
  'wiki': '📚',
  'employment': '💼',
  'education': '🎓',
  'stocks': '📈',
  'casino': '🎰'
}

function getModuleIcon(name) {
  return moduleIcons[name.toLowerCase()] || '🎮'
}

function getModuleRoute(name) {
  const routes = {
    'crimes': '/crimes',
    'gym': '/gym',
    'hospital': '/hospital',
    'jail': '/jail',
    'bank': '/bank',
    'combat': '/combat',
    'bounty': '/bounty',
    'bounties': '/bounty',
    'drugs': '/drugs',
    'theft': '/theft',
    'racing': '/racing',
    'travel': '/travel',
    'inventory': '/inventory',
    'shop': '/shop',
    'properties': '/properties',
    'gang': '/gang',
    'gangs': '/gang',
    'missions': '/missions',
    'achievements': '/achievements',
    'leaderboards': '/leaderboards',
    'forum': '/forum',
    'organized-crime': '/organized-crime',
    'organized_crimes': '/organized-crime',
    'detective': '/detective',
    'bullets': '/bullets',
    'chat': '/chat',
    'profile': '/profile',
    'activity': '/activity',
    'wiki': '/wiki',
    'employment': '/employment',
    'education': '/education',
    'stocks': '/stocks',
    'casino': '/casino'
  }
  return routes[name.toLowerCase()] || '/dashboard'
}

function formatNumber(num) {
  return new Intl.NumberFormat().format(num)
}

async function loadDashboard() {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/dashboard')
    dashboardData.value = response.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load dashboard'
    console.error('Dashboard error:', err)
  } finally {
    loading.value = false
  }
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  loadDashboard()
})
</script>

<style scoped>
/* Dashboard Container */
.dashboard-container {
  min-height: 100vh;
  background-color: #111827;
}

/* Header Styles */
.dashboard-header {
  background-color: #1f2937;
  border-bottom: 1px solid #374151;
}

.header-content {
  max-width: 80rem;
  margin-left: auto;
  margin-right: auto;
  padding-left: 1rem;
  padding-right: 1rem;
}

@media (min-width: 640px) {
  .header-content {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .header-content {
    padding-left: 2rem;
    padding-right: 2rem;
  }
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 3rem;
}

.logo-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.logo-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #22d3ee;
}

/* User Stats */
.user-stats {
  display: none;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.8125rem;
}

@media (min-width: 768px) {
  .user-stats {
    display: flex;
  }
}

/* Navbar Rank & XP */
.navbar-rank-xp {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding-right: 0.75rem;
  border-right: 1px solid #374151;
}

.rank-info {
  display: flex;
  align-items: center;
}

.rank-name {
  color: #22d3ee;
  font-weight: 700;
}

.navbar-xp-bar {
  width: 70px;
  height: 5px;
  background-color: #374151;
  border-radius: 9999px;
  overflow: hidden;
}

.navbar-xp-bar .xp-bar {
  height: 100%;
  background: linear-gradient(90deg, #22d3ee, #06b6d4);
  border-radius: 9999px;
  transition: width 0.3s ease;
}

.xp-label {
  color: #9ca3af;
  font-size: 0.6875rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.location-item {
  padding-left: 0.75rem;
  border-left: 1px solid #374151;
  color: #d1d5db;
}

.stat-label {
  color: #9ca3af;
}

.stat-value {
  color: #ffffff;
  font-weight: 700;
}

.stat-value-green {
  color: #4ade80;
  font-weight: 700;
}

.stat-value-red {
  color: #f87171;
  font-weight: 700;
}

.stat-value-yellow {
  color: #facc15;
  font-weight: 700;
}

/* User Actions */
.user-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.username {
  color: #ffffff;
  font-size: 0.875rem;
}

.logout-button {
  padding: 0.375rem 0.875rem;
  background-color: #dc2626;
  color: #ffffff;
  border-radius: 0.25rem;
  border: none;
  cursor: pointer;
  font-size: 0.875rem;
  transition: background-color 0.15s;
}

.logout-button:hover {
  background-color: #b91c1c;
}

/* Main Content */
.main-content {
  max-width: 80rem;
  margin-left: auto;
  margin-right: auto;
  padding: 2rem 1rem;
}

@media (min-width: 640px) {
  .main-content {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .main-content {
    padding-left: 2rem;
    padding-right: 2rem;
  }
}

/* Loading */
.loading-container {
  text-align: center;
  color: #ffffff;
  padding: 3rem 0;
}

.spinner {
  display: inline-block;
  width: 3rem;
  height: 3rem;
  border-radius: 9999px;
  border-bottom: 2px solid #22d3ee;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.loading-text {
  margin-top: 1rem;
}

/* Error Message */
.error-message {
  background-color: rgba(127, 29, 29, 0.5);
  border: 1px solid #ef4444;
  color: #fecaca;
  padding: 0.75rem 1rem;
  border-radius: 0.25rem;
}

/* Dashboard Content */
.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Quick Navigation */
.quick-nav {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.quick-nav-item {
  flex: 1;
  min-width: 150px;
  padding: 1rem;
  background: linear-gradient(135deg, #16213e 0%, #0f3460 100%);
  border-radius: 0.5rem;
  border: 1px solid #0f3460;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  color: white;
  transition: all 0.3s ease;
}

.quick-nav-item:hover {
  transform: translateY(-3px);
  border-color: #e94560;
  box-shadow: 0 4px 8px rgba(233, 69, 96, 0.2);
}

.quick-icon {
  font-size: 1.5rem;
}

/* Player Info Card */
.player-info-card {
  background-color: #1f2937;
  border-radius: 0.5rem;
  padding: 1.5rem;
  border: 1px solid #374151;
}

.player-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.welcome-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
}

.location-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background-color: #374151;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  color: #d1d5db;
  font-size: 0.875rem;
}

.location-icon {
  font-size: 1rem;
}

/* Rank & XP Section */
.rank-xp-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

@media (min-width: 640px) {
  .rank-xp-section {
    flex-direction: row;
    align-items: center;
    gap: 1.5rem;
  }
}

.rank-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  white-space: nowrap;
}

.rank-label {
  color: #9ca3af;
  font-size: 0.875rem;
}

.rank-name {
  color: #22d3ee;
  font-weight: 700;
  font-size: 1.125rem;
}

.xp-progress-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.xp-bar-wrapper {
  height: 0.75rem;
  background-color: #374151;
  border-radius: 9999px;
  overflow: hidden;
}

.xp-bar {
  height: 100%;
  background: linear-gradient(90deg, #22d3ee, #06b6d4);
  border-radius: 9999px;
  transition: width 0.5s ease;
}

.xp-text {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #9ca3af;
}

.xp-next {
  color: #6b7280;
}

.player-info-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 1rem;
}

@media (min-width: 768px) {
  .player-info-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.info-label {
  color: #9ca3af;
  font-size: 0.875rem;
}

.info-value {
  color: #ffffff;
  font-weight: 700;
  font-size: 1.125rem;
}

/* Features Section */
.features-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 1rem;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

@media (min-width: 768px) {
  .features-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

/* Module Card */
.module-card {
  background: linear-gradient(to bottom right, #0891b2, #1e40af);
  padding: 1.5rem;
  border-radius: 0.5rem;
  transition: all 0.3s;
  transform: scale(1);
  text-decoration: none;
  display: block;
  position: relative;
  overflow: hidden;
}

.module-card:hover:not(.locked) {
  background: linear-gradient(to bottom right, #06b6d4, #1d4ed8);
  transform: scale(1.05);
}

/* Locked Module Styles */
.module-card.locked {
  background: linear-gradient(to bottom right, #374151, #1f2937);
  cursor: not-allowed;
  opacity: 0.85;
}

.module-card.locked:hover {
  transform: scale(1.02);
}

.lock-overlay {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 50%;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lock-icon {
  font-size: 1rem;
}

.locked-icon {
  filter: grayscale(70%);
  opacity: 0.6;
}

.module-unlock {
  color: #fbbf24;
  font-size: 0.75rem;
  font-weight: 600;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.module-icon {
  font-size: 2.25rem;
  margin-bottom: 0.5rem;
}

.module-name {
  color: #ffffff;
  font-weight: 700;
}

.module-description {
  color: #a5f3fc;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}
</style>
