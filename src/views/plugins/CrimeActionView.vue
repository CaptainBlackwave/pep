<template>
  <div class="crime-action-view">
    <button class="back-button" @click="goBack">
      ◀ GO BACK
    </button>

    <div class="location-header">
      <h2 class="location-name">STREET CORNER</h2>
      <span class="energy-badge">☢️ 1</span>
    </div>

    <div class="stats-panel">
      <div class="stat">
        <div class="stat-label">ATTEMPTS</div>
        <div class="stat-value">{{ stats.attempts }}</div>
      </div>
      <div class="stat">
        <div class="stat-label">SUCCESS</div>
        <div class="stat-value">{{ stats.success }}</div>
      </div>
      <div class="stat">
        <div class="stat-label">FAILS</div>
        <div class="stat-value">{{ stats.fails }}</div>
      </div>
    </div>

    <div class="progress-section">
      <div class="progress-label">
        <span>COMMITTING {{ currentAction }}</span>
        <span class="progress-text">{{ progress.current }}/{{ progress.max }}</span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
      </div>
      <div class="xp-text">{{ xpGained }} XP</div>
    </div>

    <div class="loot-section">
      <h3 class="section-title">LOOT FOUND RECENTLY</h3>
      <div class="loot-items">
        <div v-for="item in recentLoot" :key="item.id" class="loot-item">
          <img :src="item.icon" :alt="item.name" class="item-icon">
          <span class="item-quantity">x{{ item.quantity }}</span>
        </div>
        <div v-if="recentLoot.length === 0" class="no-loot">No recent loot</div>
      </div>
    </div>

    <div class="loot-section">
      <h3 class="section-title">TOTAL LOOT DISCOVERED</h3>
      <div class="loot-items">
        <div v-for="item in totalLoot" :key="item.id" class="loot-item">
          <img :src="item.icon" :alt="item.name" class="item-icon">
          <span class="item-quantity">x{{ item.quantity }}</span>
        </div>
      </div>
    </div>

    <button class="commit-button" @click="commitCrime" :disabled="isCommitting">
      {{ isCommitting ? 'COMMITTING...' : 'COMMIT CRIME' }}
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const isCommitting = ref(false)
const currentAction = ref(22)
const stats = ref({
  attempts: 158,
  success: 157,
  fails: 1
})

const progress = ref({
  current: 22,
  max: 99
})

const xpGained = ref('7,005.55XP / 7,579')

const progressPercent = computed(() => {
  return (progress.value.current / progress.value.max) * 100
})

// Mock data - these would come from API
const recentLoot = ref([
  { id: 1, name: 'Cash', icon: '/icons/cash.png', quantity: 245 }
])

const totalLoot = ref([
  { id: 1, name: 'Cash', icon: '/icons/cash.png', quantity: 1483 },
  { id: 2, name: 'Weapon', icon: '/icons/weapon.png', quantity: 12 },
  { id: 3, name: 'Item', icon: '/icons/item.png', quantity: 8 }
])

const goBack = () => {
  router.push('/crimes')
}

const commitCrime = async () => {
  if (isCommitting.value) return

  isCommitting.value = true

  // TODO: Call API to commit crime
  // const result = await crimeStore.commitCrime(route.params.id)

  setTimeout(() => {
    isCommitting.value = false

    // Simulate success/fail (85% success rate)
    const success = Math.random() > 0.15

    if (success) {
      stats.value.success++
      const cash = Math.floor(Math.random() * 500) + 100
      toast.success(`Crime successful! You earned $${cash}`, 4000)
    } else {
      stats.value.fails++
      toast.error('Crime failed! You got caught!', 4000)
    }

    stats.value.attempts++
    progress.value.current++
  }, 1000)
}
</script>

<style scoped>
.crime-action-view {
  max-width: 1000px;
  margin: 0 auto;
}

.back-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.375rem;
  color: #cbd5e1;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 1.5rem;
}

.back-button:hover {
  background: rgba(30, 41, 59, 0.8);
  border-color: #00bcd4;
  color: #00bcd4;
}

.location-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.5rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.location-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
}

.energy-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background: rgba(15, 20, 25, 0.8);
  border-radius: 0.25rem;
  font-weight: 600;
  font-size: 1rem;
  color: #10b981;
}

.stats-panel {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1px;
  background: rgba(148, 163, 184, 0.2);
  border-radius: 0.5rem;
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.stat {
  background: rgba(15, 20, 25, 0.8);
  padding: 1.5rem;
  text-align: center;
}

.stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #94a3b8;
  margin-bottom: 0.5rem;
  letter-spacing: 0.05em;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #ffffff;
}

.progress-section {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.5rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #cbd5e1;
}

.progress-text {
  color: #94a3b8;
}

.progress-bar {
  height: 1.5rem;
  background: rgba(15, 20, 25, 0.6);
  border-radius: 0.25rem;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #00bcd4 0%, #0891b2 100%);
  transition: width 0.3s;
}

.xp-text {
  text-align: right;
  font-size: 0.875rem;
  color: #94a3b8;
}

.loot-section {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.5rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #cbd5e1;
  margin: 0 0 1rem 0;
  letter-spacing: 0.05em;
}

.loot-items {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.loot-item {
  position: relative;
  width: 60px;
  height: 60px;
  background: rgba(15, 20, 25, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.375rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-icon {
  width: 40px;
  height: 40px;
  object-fit: contain;
}

.item-quantity {
  position: absolute;
  bottom: 0.25rem;
  right: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #ffffff;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
}

.no-loot {
  color: #64748b;
  font-style: italic;
}

.commit-button {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border: none;
  border-radius: 0.5rem;
  color: #ffffff;
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: all 0.3s;
}

.commit-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
}

.commit-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
