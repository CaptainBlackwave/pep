<template>
  <div class="game-layout">
    <AppHeader @toggle-sidebar="toggleSidebar" />
    <AppNav />
    <main class="main-content">
      <router-view />
    </main>
    <ChatPanel />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { usePlayerStore } from '@/stores/player'
import { useNotificationsStore } from '@/stores/notifications'
import { useChatStore } from '@/stores/chat'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppNav from '@/components/layout/AppNav.vue'
import ChatPanel from '@/components/layout/ChatPanel.vue'

const playerStore = usePlayerStore()
const notificationsStore = useNotificationsStore()
const chatStore = useChatStore()

const toggleSidebar = () => {
  // Sidebar toggle handled by AppHeader
}

onMounted(async () => {
  try {
    await playerStore.fetchPlayer()
    if (playerStore.player?.id) {
      await Promise.all([
        playerStore.connect(),
        notificationsStore.connect(playerStore.player.id),
        chatStore.connect(playerStore.player.id),
      ])
    }
  } catch {
    // Initialization failed - stores will handle their own error states
  }
})
</script>

<style scoped>
.game-layout {
  min-height: 100vh;
  background: linear-gradient(180deg, #1a2332 0%, #0f1419 100%);
  color: #e2e8f0;
}

.main-content {
  padding: 2rem;
  min-height: calc(100vh - 180px);
  padding-bottom: 120px;
}

@media (max-width: 768px) {
  .main-content {
    padding: 1rem;
  }
}
</style>
