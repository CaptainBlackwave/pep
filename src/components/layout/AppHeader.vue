<template>
  <header class="game-header">
    <div class="header-left">
      <button class="hamburger-menu" @click="emit('toggle-sidebar')">
        <span class="hamburger-icon">Menu</span>
      </button>
      <router-link to="/dashboard" class="logo">
        <span class="logo-text">Open</span>
        <span class="logo-accent">PBBG</span>
      </router-link>
    </div>

    <div class="header-center">
      <StatBars />
      <div class="currency-display">
        <div class="currency-item">
          <span class="currency-icon">$</span>
          <span class="currency-value">{{ formatNumber(cash) }}</span>
        </div>
        <div class="currency-item">
          <span class="currency-icon">P</span>
          <span class="currency-value">{{ points }}</span>
        </div>
        <div class="currency-item">
          <span class="currency-icon">D</span>
          <span class="currency-value">{{ diamonds }}</span>
        </div>
      </div>
    </div>

    <div class="header-right">
      <router-link to="/chat" class="icon-btn" title="Messages">
        <span>Mail</span>
        <span v-if="chatUnread > 0" class="badge">{{ chatUnread > 99 ? '99+' : chatUnread }}</span>
      </router-link>
      <NotificationsDropdown />
      <UserMenu />
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePlayerStore } from '@/stores/player'
import { useChatStore } from '@/stores/chat'
import StatBars from './StatBars.vue'
import NotificationsDropdown from './NotificationsDropdown.vue'
import UserMenu from './UserMenu.vue'

const emit = defineEmits<{
  'toggle-sidebar': []
}>()

const playerStore = usePlayerStore()
const chatStore = useChatStore()

const cash = computed(() => playerStore.cash)
const points = computed(() => playerStore.points)
const diamonds = computed(() => playerStore.diamonds)
const chatUnread = computed(() => chatStore.totalUnread)

const formatNumber = (num: number): string => {
  return num.toLocaleString()
}
</script>

<style scoped>
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
  font-size: 1rem;
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
  font-size: 1rem;
  cursor: pointer;
  padding: 0.5rem;
  transition: color 0.2s;
  position: relative;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}

.icon-btn:hover {
  color: #00bcd4;
}

.badge {
  position: absolute;
  top: 0;
  right: 0;
  background: #ef4444;
  color: white;
  font-size: 0.625rem;
  font-weight: 700;
  padding: 0.125rem 0.375rem;
  border-radius: 9999px;
  min-width: 1rem;
  text-align: center;
}

@media (max-width: 768px) {
  .hamburger-menu {
    display: block;
  }

  .header-center {
    display: none;
  }
}
</style>
