<template>
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
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePlayerStore } from '@/stores/player'

const playerStore = usePlayerStore()

// Stats from player store
const energy = computed(() => playerStore.energy)
const maxEnergy = computed(() => playerStore.maxEnergy)
const energyPercent = computed(() => playerStore.energyPercent)
const energyTimer = computed(() => playerStore.energyTimer)

const health = computed(() => playerStore.health)
const maxHealth = computed(() => playerStore.maxHealth)
const healthPercent = computed(() => playerStore.healthPercent)
const healthTimer = computed(() => playerStore.healthTimer)

const stamina = computed(() => playerStore.stamina)
const maxStamina = computed(() => playerStore.maxStamina)
const staminaPercent = computed(() => playerStore.staminaPercent)
const staminaTimer = computed(() => playerStore.staminaTimer)

const nerve = computed(() => playerStore.nerve)
const maxNerve = computed(() => playerStore.maxNerve)
const nervePercent = computed(() => playerStore.nervePercent)
const nerveTimer = computed(() => playerStore.nerveTimer)
</script>

<style scoped>
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

@media (max-width: 1200px) {
  .stat-bars {
    flex-wrap: wrap;
  }

  .stat-bar {
    min-width: calc(50% - 0.5rem);
  }
}

@media (max-width: 768px) {
  .stat-bars {
    display: none;
  }
}
</style>
