<template>
  <div class="travel-view">
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      {{ error }}
    </div>

    <!-- Main Content -->
    <div v-else class="travel-content">
      <!-- Flash Messages -->
      <div v-if="successMessage" class="success-message">
        {{ successMessage }}
      </div>
      <div v-if="errorMessage" class="error-banner">
        {{ errorMessage }}
      </div>

      <!-- Current Location Info -->
      <div v-if="currentLocation" class="current-location-banner">
        <span class="location-label">CURRENT LOCATION:</span>
        <span class="location-name">{{ currentLocation.name?.toUpperCase() }}</span>
      </div>

      <!-- Difficulty Filter -->
      <div class="difficulty-filter">
        <select v-model="selectedDifficulty" class="difficulty-select">
          <option value="">ALL LOCATIONS</option>
          <option value="easy">EASY</option>
          <option value="medium">MEDIUM</option>
          <option value="hard">HARD</option>
        </select>
      </div>

      <!-- Locations List -->
      <div class="locations-list">
        <div
          v-for="loc in filteredLocations"
          :key="loc.id"
          class="location-card"
          :class="{ 'current': currentLocation?.id === loc.id }"
        >
          <div class="location-content">
            <h3 class="location-title">{{ loc.name?.toUpperCase() }}</h3>

            <!-- Difficulty Indicators -->
            <div class="difficulty-indicators">
              <span
                v-for="n in 5"
                :key="n"
                class="difficulty-skull"
                :class="getDifficultyClass(loc.difficulty, n)"
              >
                💀
              </span>
            </div>

            <!-- Tags/Features -->
            <div class="location-tags">
              <span v-if="loc.features?.includes('safe')" class="tag">Safe Zone</span>
              <span v-if="loc.features?.includes('pvp')" class="tag">PvP</span>
              <span v-if="loc.features?.includes('rare')" class="tag tag-rare">Rare Items</span>
              <span v-if="loc.travel_cost > 0" class="tag">${{ formatNumber(loc.travel_cost) }}</span>
            </div>
          </div>

          <div class="location-actions">
            <div class="travel-time">
              <span class="time-icon">⏱</span>
              <span class="time-text">{{ loc.travel_time || '5:00' }}</span>
            </div>
            <button
              v-if="currentLocation?.id !== loc.id"
              @click="travelTo(loc.id)"
              :disabled="processing"
              class="travel-btn"
            >
              {{ processing ? '...' : 'TRAVEL' }}
            </button>
            <div v-else class="current-badge">HERE</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'

// Type definitions
interface Location {
  id: number
  name: string
  difficulty: 'easy' | 'medium' | 'hard'
  features?: string[]
  travel_cost: number
  travel_time: string
}

interface CurrentLocation {
  id: number
  name: string
}

const loading = ref(true)
const processing = ref(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const errorMessage = ref<string | null>(null)
const selectedDifficulty = ref('')

const locations = ref<Location[]>([])
const currentLocation = ref<CurrentLocation | null>(null)
const playersHere = ref<Array<{ id: number; username: string }>>([])
const playerExperience = ref(0)
const playerCash = ref(0)

const filteredLocations = computed(() => {
  if (!selectedDifficulty.value) return locations.value
  return locations.value.filter(loc => loc.difficulty === selectedDifficulty.value)
})

onMounted(async () => {
  await fetchTravelData()
})

async function fetchTravelData() {
  try {
    loading.value = true
    const response = await api.get('/api/v1/travel')
    locations.value = response.data.locations || []
    currentLocation.value = response.data.currentLocation
    playersHere.value = response.data.playersHere || []
    playerExperience.value = response.data.player?.experience || 0
    playerCash.value = response.data.player?.cash || 0
    error.value = null
  } catch (err: unknown) {
    const apiError = err as { response?: { data?: { message?: string } } }
    error.value = apiError.response?.data?.message || 'Failed to load travel data'
  } finally {
    loading.value = false
  }
}

async function travelTo(locationId: number) {
  if (processing.value) return

  processing.value = true
  successMessage.value = null
  errorMessage.value = null

  try {
    const response = await api.post(`/api/v1/travel/${locationId}`)
    successMessage.value = response.data.message || 'Traveled successfully!'
    await fetchTravelData()
  } catch (err: unknown) {
    const error = err as { response?: { data?: { message?: string } } }
    errorMessage.value = error.response?.data?.message || 'Failed to travel'
  } finally {
    processing.value = false
  }
}

function getDifficultyClass(difficulty: string, skullNumber: number): string {
  const difficultyLevels: Record<string, number> = {
    easy: 1,
    medium: 3,
    hard: 5
  }
  const level = difficultyLevels[difficulty] || 1

  if (skullNumber <= level) {
    if (skullNumber <= 1) return 'skull-red'
    if (skullNumber <= 3) return 'skull-orange'
    return 'skull-red'
  }
  return 'skull-grey'
}

function formatNumber(num: number): string {
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(num)
}
</script>

<style scoped>
.travel-view {
  min-height: 100vh;
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* Loading */
.loading-state {
  display: flex;
  justify-content: center;
  padding: 4rem;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-top-color: #00bcd4;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Messages */
.error-message,
.error-banner {
  background: rgba(220, 38, 38, 0.2);
  border: 1px solid rgba(220, 38, 38, 0.5);
  color: #fca5a5;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.95rem;
}

.success-message {
  background: rgba(34, 197, 94, 0.2);
  border: 1px solid rgba(34, 197, 94, 0.5);
  color: #86efac;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.95rem;
}

/* Current Location Banner */
.current-location-banner {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1rem 1.5rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.location-label {
  color: #94a3b8;
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 1px;
}

.location-name {
  color: #e2e8f0;
  font-size: 1.1rem;
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* Difficulty Filter */
.difficulty-filter {
  margin-bottom: 1.5rem;
}

.difficulty-select {
  width: 100%;
  max-width: 300px;
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  color: #e2e8f0;
  font-size: 0.95rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s;
}

.difficulty-select:hover {
  border-color: rgba(255, 255, 255, 0.2);
}

.difficulty-select option {
  background: #1e293b;
  color: #e2e8f0;
}

/* Locations List */
.locations-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.location-card {
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: transform 0.2s, box-shadow 0.2s;
}

.location-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.location-card.current {
  border-color: rgba(74, 222, 128, 0.4);
  background: rgba(34, 197, 94, 0.1);
}

.location-content {
  flex: 1;
}

.location-title {
  font-size: 1.15rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  color: #e2e8f0;
  margin: 0 0 0.75rem 0;
}

/* Difficulty Indicators */
.difficulty-indicators {
  display: flex;
  gap: 0.25rem;
  margin-bottom: 0.75rem;
}

.difficulty-skull {
  font-size: 0.9rem;
  filter: grayscale(1);
  opacity: 0.3;
}

.difficulty-skull.skull-red {
  filter: grayscale(0) hue-rotate(0deg) brightness(1.2);
  opacity: 1;
}

.difficulty-skull.skull-orange {
  filter: grayscale(0) hue-rotate(20deg) brightness(1.1);
  opacity: 1;
}

.difficulty-skull.skull-grey {
  filter: grayscale(1);
  opacity: 0.3;
}

/* Tags */
.location-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.tag {
  padding: 0.25rem 0.75rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 193, 7, 0.5);
  border-radius: 4px;
  font-size: 0.85rem;
  color: #fbbf24;
  letter-spacing: 0.3px;
}

.tag-rare {
  border-color: rgba(168, 85, 247, 0.5);
  color: #c084fc;
}

/* Actions */
.location-actions {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-left: 2rem;
}

.travel-time {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #cbd5e1;
  font-size: 0.95rem;
}

.time-icon {
  font-size: 1.1rem;
}

.time-text {
  font-weight: 600;
}

.travel-btn {
  padding: 0.5rem 1.5rem;
  background: #4ade80;
  border: none;
  border-radius: 6px;
  color: #064e3b;
  font-weight: 700;
  font-size: 0.9rem;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 90px;
}

.travel-btn:hover:not(:disabled) {
  background: #22c55e;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(74, 222, 128, 0.4);
}

.travel-btn:disabled {
  background: #374151;
  color: #6b7280;
  cursor: not-allowed;
}

.current-badge {
  padding: 0.5rem 1.5rem;
  background: rgba(74, 222, 128, 0.2);
  border: 1px solid rgba(74, 222, 128, 0.5);
  border-radius: 6px;
  color: #4ade80;
  font-weight: 700;
  font-size: 0.9rem;
  letter-spacing: 0.5px;
  min-width: 90px;
  text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
  .travel-view {
    padding: 1rem;
  }

  .location-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .location-actions {
    width: 100%;
    justify-content: space-between;
    margin-left: 0;
  }

  .difficulty-select {
    max-width: 100%;
  }
}
</style>
