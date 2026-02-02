<template>
  <div class="game-layout">
    <!-- Header -->
    <header class="game-header">
      <div class="header-left">
        <button class="hamburger-menu" @click="toggleSidebar">
          <span class="hamburger-icon">☰</span>
        </button>
        <router-link to="/dashboard" class="logo">
          <span class="logo-text">Open</span>
          <span class="logo-accent">PBBG</span>
        </router-link>
      </div>

      <div class="header-center">
        <!-- Stat Bars -->
        <div class="stat-bars">
          <div class="stat-bar">
            <span class="stat-icon">⚡</span>
            <div class="stat-info">
              <div class="stat-progress">
                <div class="stat-fill energy" :style="{ width: energyPercent + '%' }"></div>
              </div>
              <span class="stat-text">{{ energy }}/{{ maxEnergy }}</span>
            </div>
            <span class="stat-timer">{{ energyTimer }}</span>
          </div>

          <div class="stat-bar">
            <span class="stat-icon">🧬</span>
            <div class="stat-info">
              <div class="stat-progress">
                <div class="stat-fill health" :style="{ width: healthPercent + '%' }"></div>
              </div>
              <span class="stat-text">{{ health }}/{{ maxHealth }}</span>
            </div>
            <span class="stat-timer">{{ healthTimer }}</span>
          </div>

          <div class="stat-bar">
            <span class="stat-icon">💧</span>
            <div class="stat-info">
              <div class="stat-progress">
                <div class="stat-fill stamina" :style="{ width: staminaPercent + '%' }"></div>
              </div>
              <span class="stat-text">{{ stamina }}/{{ maxStamina }}</span>
            </div>
            <span class="stat-timer">{{ staminaTimer }}</span>
          </div>

          <div class="stat-bar">
            <span class="stat-icon">❤️</span>
            <div class="stat-info">
              <div class="stat-progress">
                <div class="stat-fill nerve" :style="{ width: nervePercent + '%' }"></div>
              </div>
              <span class="stat-text">{{ nerve }}/{{ maxNerve }}</span>
            </div>
            <span class="stat-timer">{{ nerveTimer }}</span>
          </div>
        </div>

        <!-- Currency Display -->
        <div class="currency-display">
          <div class="currency-item">
            <span class="currency-icon">💲</span>
            <span class="currency-value">{{ formatNumber(cash) }}</span>
          </div>
          <div class="currency-item">
            <span class="currency-icon">🪙</span>
            <span class="currency-value">{{ points }}</span>
          </div>
          <div class="currency-item">
            <span class="currency-icon">💎</span>
            <span class="currency-value">{{ diamonds }}</span>
          </div>
        </div>
      </div>

      <div class="header-right">
        <button class="icon-btn" title="Messages">
          <span>✉️</span>
        </button>
        <button class="icon-btn" title="Notifications">
          <span>🔔</span>
        </button>
        <div class="user-menu">
          <button class="user-btn" @click="toggleUserMenu">
            <span class="username">{{ username }}</span>
            <span class="dropdown-icon">▼</span>
          </button>
          <div v-if="showUserMenu" class="user-dropdown">
            <router-link to="/profile" class="dropdown-item">Profile</router-link>
            <router-link to="/settings" class="dropdown-item">Settings</router-link>
            <button @click="logout" class="dropdown-item">Logout</button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Navigation -->
    <nav class="main-nav">
      <router-link to="/home" class="nav-item" active-class="active">
        <span class="nav-icon">🏠</span>
        <span class="nav-label">HOME</span>
      </router-link>
      <router-link to="/city" class="nav-item" active-class="active">
        <span class="nav-icon">🏙️</span>
        <span class="nav-label">CITY</span>
      </router-link>
      <router-link to="/inventory" class="nav-item" active-class="active">
        <span class="nav-icon">🎒</span>
        <span class="nav-label">INVENTORY</span>
      </router-link>
      <router-link to="/missions" class="nav-item" active-class="active">
        <span class="nav-icon">📜</span>
        <span class="nav-label">MISSIONS</span>
      </router-link>
      <router-link to="/combat" class="nav-item" active-class="active">
        <span class="nav-icon">⚔️</span>
        <span class="nav-label">COMBAT</span>
      </router-link>
      <router-link to="/crimes" class="nav-item" active-class="active">
        <span class="nav-icon">🔫</span>
        <span class="nav-label">CRIMES</span>
      </router-link>
      <router-link to="/travel" class="nav-item" active-class="active">
        <span class="nav-icon">✈️</span>
        <span class="nav-label">TRAVEL</span>
      </router-link>
      <router-link to="/skills" class="nav-item" active-class="active">
        <span class="nav-icon">📊</span>
        <span class="nav-label">SKILLS</span>
      </router-link>
      <router-link to="/forums" class="nav-item" active-class="active">
        <span class="nav-icon">👥</span>
        <span class="nav-label">FORUMS</span>
      </router-link>
    </nav>

    <!-- Main Content Area -->
    <main class="main-content">
      <router-view />
    </main>

    <!-- Global Chat Panel -->
    <div class="global-chat" :class="{ collapsed: chatCollapsed }">
      <div class="chat-header" @click="toggleChat">
        <span class="chat-title">GLOBAL ({{ chatCount }})</span>
        <button class="chat-toggle">{{ chatCollapsed ? '▲' : '▼' }}</button>
      </div>
      <div v-if="!chatCollapsed" class="chat-body">
        <div class="chat-messages">
          <div v-for="msg in chatMessages" :key="msg.id" class="chat-message">
            <span class="chat-user">{{ msg.user }}</span>
            <span class="chat-time">{{ msg.time }}</span>
            <div class="chat-text">{{ msg.text }}</div>
          </div>
        </div>
        <div class="chat-input">
          <input
            v-model="chatInput"
            type="text"
            placeholder="Write your message..."
            @keyup.enter="sendMessage"
          />
          <button @click="sendMessage" class="send-btn">📤</button>
        </div>
        <div class="chat-footer">
          <router-link to="/chat" class="full-chat-link">Open Full Chat</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// User data
const username = ref('LF')
const showUserMenu = ref(false)

// Stats
const energy = ref(20)
const maxEnergy = ref(100)
const health = ref(25)
const maxHealth = ref(25)
const stamina = ref(193)
const maxStamina = ref(200)
const nerve = ref(200)
const maxNerve = ref(200)

// Timers
const energyTimer = ref('08:01')
const healthTimer = ref('04:19')
const staminaTimer = ref('02:34')
const nerveTimer = ref('03:01')

// Currency
const cash = ref(4025)
const points = ref(0)
const diamonds = ref(14)

// Chat
const chatCollapsed = ref(false)
const chatCount = ref(13)
const chatInput = ref('')
const chatMessages = ref([
  { id: 1, user: 'TartacusPrime', time: '15:24', text: 'Carrot cake? Lol 😂' },
  { id: 2, user: 'ButcherPete', time: '15:27', text: 'lol' },
  { id: 3, user: 'ButcherPete', time: '15:25', text: 'the furniture skill requirements are crazy. 75 scavenging for one will take years' }
])

// Computed
const energyPercent = computed(() => (energy.value / maxEnergy.value) * 100)
const healthPercent = computed(() => (health.value / maxHealth.value) * 100)
const staminaPercent = computed(() => (stamina.value / maxStamina.value) * 100)
const nervePercent = computed(() => (nerve.value / maxNerve.value) * 100)

// Methods
const formatNumber = (num) => {
  return num.toLocaleString()
}

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
}

const toggleChat = () => {
  chatCollapsed.value = !chatCollapsed.value
}

const toggleSidebar = () => {
  // Mobile sidebar toggle
}

const sendMessage = () => {
  if (chatInput.value.trim()) {
    chatMessages.value.push({
      id: Date.now(),
      user: username.value,
      time: new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
      text: chatInput.value
    })
    chatInput.value = ''
  }
}

const logout = () => {
  router.push('/login')
}
</script>

<style scoped>
.game-layout {
  min-height: 100vh;
  background: linear-gradient(180deg, #1a2332 0%, #0f1419 100%);
  color: #e2e8f0;
}

/* Header */
.game-header {
  background: rgba(15, 20, 25, 0.95);
  border-bottom: 1px solid rgba(0, 188, 212, 0.1);
  padding: 0.75rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.hamburger-menu {
  display: none;
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.5rem;
}

.logo {
  text-decoration: none;
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  gap: 0.25rem;
}

.logo-text {
  color: #ffffff;
}

.logo-accent {
  color: #00bcd4;
}

.header-center {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-bars {
  display: flex;
  gap: 1rem;
}

.stat-bar {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.stat-icon {
  font-size: 1.125rem;
}

.stat-info {
  flex: 1;
  min-width: 0;
}

.stat-progress {
  height: 6px;
  background: rgba(30, 41, 59, 0.6);
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 2px;
}

.stat-fill {
  height: 100%;
  transition: width 0.3s;
}

.stat-fill.energy {
  background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
}

.stat-fill.health {
  background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
}

.stat-fill.stamina {
  background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
}

.stat-fill.nerve {
  background: linear-gradient(90deg, #ef4444 0%, #f87171 100%);
}

.stat-text {
  color: #cbd5e1;
  font-size: 0.75rem;
  font-weight: 600;
}

.stat-timer {
  color: #64748b;
  font-size: 0.75rem;
  white-space: nowrap;
}

.currency-display {
  display: flex;
  gap: 1.5rem;
  justify-content: center;
}

.currency-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-weight: 600;
}

.currency-icon {
  font-size: 1rem;
}

.currency-value {
  color: #10b981;
  font-size: 0.875rem;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.icon-btn {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.25rem;
  cursor: pointer;
  padding: 0.5rem;
  transition: color 0.2s;
}

.icon-btn:hover {
  color: #00bcd4;
}

.user-menu {
  position: relative;
}

.user-btn {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.375rem;
  color: #e2e8f0;
  padding: 0.5rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s;
}

.user-btn:hover {
  background: rgba(30, 41, 59, 0.8);
  border-color: #00bcd4;
}

.dropdown-icon {
  font-size: 0.625rem;
  color: #94a3b8;
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  background: #1e293b;
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 0.5rem;
  min-width: 150px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
  overflow: hidden;
}

.dropdown-item {
  display: block;
  width: 100%;
  padding: 0.75rem 1rem;
  background: none;
  border: none;
  color: #cbd5e1;
  text-decoration: none;
  font-size: 0.875rem;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s;
}

.dropdown-item:hover {
  background: rgba(0, 188, 212, 0.1);
  color: #00bcd4;
}

/* Main Navigation */
.main-nav {
  background: rgba(30, 41, 59, 0.4);
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  padding: 0 1.5rem;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 1rem 1.25rem;
  text-decoration: none;
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  transition: all 0.2s;
  position: relative;
}

.nav-item:hover {
  color: #e2e8f0;
  background: rgba(30, 41, 59, 0.6);
}

.nav-item.active {
  color: #00bcd4;
  background: rgba(0, 188, 212, 0.1);
}

.nav-item.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: #00bcd4;
}

.nav-icon {
  font-size: 1.5rem;
  filter: grayscale(100%);
  transition: filter 0.2s;
}

.nav-item:hover .nav-icon,
.nav-item.active .nav-icon {
  filter: grayscale(0%);
}

/* Main Content */
.main-content {
  padding: 2rem;
  min-height: calc(100vh - 180px);
  padding-bottom: 120px;
}

/* Global Chat */
.global-chat {
  position: fixed;
  bottom: 1rem;
  right: 1rem;
  width: 350px;
  background: rgba(15, 20, 25, 0.95);
  border: 1px solid rgba(0, 188, 212, 0.2);
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
  transition: all 0.3s;
  z-index: 90;
}

.global-chat.collapsed {
  height: auto;
}

.chat-header {
  padding: 0.75rem 1rem;
  background: rgba(30, 41, 59, 0.8);
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  border-radius: 0.5rem 0.5rem 0 0;
}

.chat-title {
  font-weight: 700;
  font-size: 0.875rem;
  color: #e2e8f0;
}

.chat-toggle {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 0.75rem;
  cursor: pointer;
}

.chat-body {
  display: flex;
  flex-direction: column;
  height: 300px;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.chat-message {
  font-size: 0.8125rem;
}

.chat-user {
  font-weight: 700;
  color: #00bcd4;
  margin-right: 0.5rem;
}

.chat-time {
  color: #64748b;
  font-size: 0.75rem;
}

.chat-text {
  color: #cbd5e1;
  margin-top: 0.125rem;
}

.chat-input {
  padding: 0.75rem;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
  display: flex;
  gap: 0.5rem;
}

.chat-input input {
  flex: 1;
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.375rem;
  padding: 0.5rem 0.75rem;
  color: #e2e8f0;
  font-size: 0.875rem;
}

.chat-input input::placeholder {
  color: #64748b;
}

.send-btn {
  background: rgba(0, 188, 212, 0.2);
  border: 1px solid rgba(0, 188, 212, 0.3);
  border-radius: 0.375rem;
  padding: 0.5rem 0.75rem;
  color: #00bcd4;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.2s;
}

.send-btn:hover {
  background: rgba(0, 188, 212, 0.3);
}

.chat-footer {
  padding: 0.5rem 1rem;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
  text-align: center;
}

.full-chat-link {
  color: #00bcd4;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 600;
  transition: color 0.2s;
}

.full-chat-link:hover {
  color: #0891b2;
}

/* Scrollbar */
.chat-messages::-webkit-scrollbar {
  width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
  background: rgba(30, 41, 59, 0.3);
}

.chat-messages::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.3);
  border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.5);
}

/* Responsive */
@media (max-width: 1200px) {
  .stat-bars {
    flex-wrap: wrap;
  }

  .stat-bar {
    min-width: calc(50% - 0.5rem);
  }
}

@media (max-width: 768px) {
  .hamburger-menu {
    display: block;
  }

  .header-center {
    display: none;
  }

  .main-nav {
    overflow-x: auto;
    justify-content: flex-start;
  }

  .nav-item {
    flex-shrink: 0;
  }

  .global-chat {
    width: calc(100% - 2rem);
    right: 1rem;
    left: 1rem;
  }

  .main-content {
    padding: 1rem;
  }
}
</style>
