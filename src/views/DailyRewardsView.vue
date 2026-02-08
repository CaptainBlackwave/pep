<template>
  <div class="daily-rewards-container">
    <div class="header">
      <div class="header-content">
        <router-link to="/dashboard" class="back-link">← Back</router-link>
      </div>
    </div>

    <div class="content-wrapper">
      <div class="rewards-banner">
        <div class="banner-content">
          <div>
            <h1 class="banner-title">🎁 Daily Rewards</h1>
            <p class="banner-subtitle">Claim your daily bonus rewards</p>
          </div>
          <div class="streak-info" v-if="currentStreak > 0">
            <span class="streak-label">Current Streak</span>
            <span class="streak-value">🔥 {{ currentStreak }} days</span>
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
      </div>

      <div v-else class="rewards-content">
        <!-- Can Claim Today -->
        <div v-if="canClaim" class="claim-section">
          <div class="today-reward">
            <div class="reward-day">Day {{ nextDay }}</div>
            <div class="reward-icon">{{ todayReward.icon }}</div>
            <div class="reward-name">{{ todayReward.name }}</div>
            <div class="reward-value">{{ todayReward.value }}</div>
            <button @click="claimReward" :disabled="claiming" class="claim-btn">
              {{ claiming ? 'Claiming...' : 'Claim Reward' }}
            </button>
          </div>
        </div>

        <!-- Already Claimed Today -->
        <div v-else class="claimed-section">
          <div class="claimed-message">
            <div class="claimed-icon">✅</div>
            <h3>Today's reward claimed!</h3>
            <p>Come back in <span class="countdown">{{ nextClaimTime }}</span></p>
          </div>
        </div>

        <!-- Rewards Calendar -->
        <div class="rewards-calendar">
          <h2 class="section-title">Weekly Rewards</h2>
          <div class="calendar-grid">
            <div v-for="day in 7" :key="day"
                 :class="['calendar-day', {
                   claimed: day <= currentStreak,
                   today: day === nextDay && canClaim,
                   locked: day > nextDay
                 }]">
              <div class="day-number">Day {{ day }}</div>
              <div class="day-reward">{{ rewards[day - 1]?.icon || '🎁' }}</div>
              <div class="day-name">{{ rewards[day - 1]?.name || 'Reward' }}</div>
              <div v-if="day <= currentStreak" class="claimed-badge">✓</div>
            </div>
          </div>
        </div>

        <!-- Reward History -->
        <div v-if="claimHistory.length > 0" class="history-section">
          <h2 class="section-title">Recent Claims</h2>
          <div class="history-list">
            <div v-for="claim in claimHistory" :key="claim.id" class="history-item">
              <span class="history-icon">{{ claim.reward_icon }}</span>
              <span class="history-reward">{{ claim.reward_name }}</span>
              <span class="history-date">{{ formatDate(claim.claimed_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const loading = ref(true)
const claiming = ref(false)
const canClaim = ref(false)
const currentStreak = ref(0)
const nextDay = ref(1)
const nextClaimTime = ref('00:00:00')
const claimHistory = ref([])

let countdownInterval = null

const rewards = [
  { day: 1, icon: '💰', name: 'Cash Bonus', value: '$500' },
  { day: 2, icon: '⚡', name: 'Energy Boost', value: '+25 Energy' },
  { day: 3, icon: '💎', name: 'Diamonds', value: '5 Diamonds' },
  { day: 4, icon: '🎯', name: 'XP Bonus', value: '+100 XP' },
  { day: 5, icon: '🔫', name: 'Bullets', value: '50 Bullets' },
  { day: 6, icon: '💊', name: 'Health Pack', value: 'Full Health' },
  { day: 7, icon: '🏆', name: 'Premium Chest', value: 'Mystery Reward' },
]

const defaultReward = rewards[0]

const todayReward = computed(() => {
  return rewards[nextDay.value - 1] || defaultReward
})

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const fetchRewardsStatus = async () => {
  try {
    const response = await api.get('/daily-rewards')
    const data = response.data

    canClaim.value = data.can_claim ?? true
    currentStreak.value = data.current_streak ?? 0
    nextDay.value = data.next_day ?? (currentStreak.value % 7) + 1
    claimHistory.value = data.history || []

    if (!canClaim.value && data.next_claim_at) {
      startCountdown(new Date(data.next_claim_at))
    }
  } catch (err) {
    console.error('Failed to fetch daily rewards status:', err)
    // Use defaults for demo
    canClaim.value = true
    currentStreak.value = 3
    nextDay.value = 4
  } finally {
    loading.value = false
  }
}

const claimReward = async () => {
  if (claiming.value || !canClaim.value) return

  claiming.value = true

  try {
    const response = await api.post('/daily-rewards/claim')

    toast.success(response.data.message || 'Reward claimed successfully!')

    canClaim.value = false
    currentStreak.value++
    nextDay.value = (currentStreak.value % 7) + 1

    if (response.data.next_claim_at) {
      startCountdown(new Date(response.data.next_claim_at))
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to claim reward')
  } finally {
    claiming.value = false
  }
}

const startCountdown = (targetDate) => {
  if (countdownInterval) {
    clearInterval(countdownInterval)
  }

  const updateCountdown = () => {
    const now = new Date()
    const diff = targetDate.getTime() - now.getTime()

    if (diff <= 0) {
      canClaim.value = true
      nextClaimTime.value = '00:00:00'
      if (countdownInterval) {
        clearInterval(countdownInterval)
        countdownInterval = null
      }
      return
    }

    const hours = Math.floor(diff / 3600000)
    const minutes = Math.floor((diff % 3600000) / 60000)
    const seconds = Math.floor((diff % 60000) / 1000)

    nextClaimTime.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
  }

  updateCountdown()
  countdownInterval = setInterval(updateCountdown, 1000)
}

onMounted(() => {
  fetchRewardsStatus()
})

onUnmounted(() => {
  if (countdownInterval) {
    clearInterval(countdownInterval)
  }
})
</script>

<style scoped>
.daily-rewards-container {
  min-height: 100vh;
  background: linear-gradient(to bottom right, #111827, #1f2937, #111827);
}

.header {
  background-color: rgba(31, 41, 55, 0.5);
  padding: 1rem 1.5rem;
}

.header-content {
  max-width: 800px;
  margin: 0 auto;
}

.back-link {
  color: #9ca3af;
  text-decoration: none;
  font-size: 0.875rem;
  transition: color 0.2s;
}

.back-link:hover {
  color: #00bcd4;
}

.content-wrapper {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.rewards-banner {
  background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 2rem;
}

.banner-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.banner-title {
  font-size: 2rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 0.5rem;
}

.banner-subtitle {
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
}

.streak-info {
  text-align: center;
  background: rgba(255, 255, 255, 0.1);
  padding: 1rem;
  border-radius: 0.75rem;
}

.streak-label {
  display: block;
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 0.25rem;
}

.streak-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
}

.loading-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.spinner {
  width: 3rem;
  height: 3rem;
  border: 3px solid rgba(168, 85, 247, 0.2);
  border-top-color: #a855f7;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.claim-section {
  margin-bottom: 2rem;
}

.today-reward {
  background: linear-gradient(135deg, rgba(124, 58, 237, 0.2), rgba(168, 85, 247, 0.2));
  border: 2px solid #a855f7;
  border-radius: 1rem;
  padding: 2rem;
  text-align: center;
}

.reward-day {
  font-size: 0.875rem;
  color: #a855f7;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.reward-icon {
  font-size: 4rem;
  margin-bottom: 0.5rem;
}

.reward-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #f9fafb;
  margin-bottom: 0.25rem;
}

.reward-value {
  font-size: 1rem;
  color: #a855f7;
  margin-bottom: 1.5rem;
}

.claim-btn {
  background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
  color: white;
  border: none;
  padding: 1rem 3rem;
  border-radius: 0.75rem;
  font-size: 1.125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.claim-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(168, 85, 247, 0.4);
}

.claim-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.claimed-section {
  margin-bottom: 2rem;
}

.claimed-message {
  background: rgba(31, 41, 55, 0.5);
  border: 1px solid rgba(75, 85, 99, 0.3);
  border-radius: 1rem;
  padding: 2rem;
  text-align: center;
}

.claimed-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.claimed-message h3 {
  font-size: 1.25rem;
  color: #f9fafb;
  margin: 0 0 0.5rem;
}

.claimed-message p {
  color: #9ca3af;
  margin: 0;
}

.countdown {
  color: #a855f7;
  font-weight: 600;
  font-family: monospace;
  font-size: 1.125rem;
}

.rewards-calendar {
  background: rgba(31, 41, 55, 0.5);
  border-radius: 1rem;
  padding: 1.5rem;
  margin-bottom: 2rem;
  border: 1px solid rgba(75, 85, 99, 0.3);
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #f9fafb;
  margin: 0 0 1rem;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 0.5rem;
}

.calendar-day {
  background: rgba(17, 24, 39, 0.5);
  border: 1px solid rgba(75, 85, 99, 0.3);
  border-radius: 0.5rem;
  padding: 0.75rem 0.5rem;
  text-align: center;
  position: relative;
  transition: all 0.2s;
}

.calendar-day.claimed {
  background: rgba(16, 185, 129, 0.1);
  border-color: #10b981;
}

.calendar-day.today {
  background: rgba(168, 85, 247, 0.2);
  border-color: #a855f7;
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.3);
}

.calendar-day.locked {
  opacity: 0.5;
}

.day-number {
  font-size: 0.6875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.day-reward {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}

.day-name {
  font-size: 0.625rem;
  color: #9ca3af;
}

.claimed-badge {
  position: absolute;
  top: 0.25rem;
  right: 0.25rem;
  background: #10b981;
  color: white;
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  font-size: 0.625rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.history-section {
  background: rgba(31, 41, 55, 0.5);
  border-radius: 1rem;
  padding: 1.5rem;
  border: 1px solid rgba(75, 85, 99, 0.3);
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.history-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: rgba(17, 24, 39, 0.3);
  border-radius: 0.5rem;
}

.history-icon {
  font-size: 1.25rem;
}

.history-reward {
  flex: 1;
  color: #d1d5db;
  font-size: 0.875rem;
}

.history-date {
  color: #6b7280;
  font-size: 0.75rem;
}

@media (max-width: 640px) {
  .banner-content {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }

  .calendar-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}
</style>
