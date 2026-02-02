<template>
  <div class="profile-container">
    <div v-if="loading" class="loading">Loading profile...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    
    <div v-else class="profile-content">
      <!-- Player Card -->
      <div class="player-card">
        <div class="player-avatar">
          {{ player.username[0].toUpperCase() }}
        </div>
        <div class="player-info">
          <h2>{{ player.username }}</h2>
          <p class="rank">{{ player.current_rank?.name || 'Thug' }}</p>
          <p class="location">📍 {{ player.location?.name || 'Unknown' }}</p>
        </div>
        <div class="player-badges">
          <span v-if="player.is_premium" class="badge premium">⭐ Premium</span>
          <span v-if="player.is_admin" class="badge admin">👑 Admin</span>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <!-- Basic Stats -->
        <div class="stat-card">
          <h3>Basic Stats</h3>
          <div class="stat-row">
            <span>Experience:</span>
            <span class="stat-value">{{ formatNumber(player.experience) }} XP</span>
          </div>
          <div class="stat-row">
            <span>Cash:</span>
            <span class="stat-value cash">${{ formatNumber(player.cash) }}</span>
          </div>
          <div class="stat-row">
            <span>Bank:</span>
            <span class="stat-value">${{ formatNumber(player.bank_balance || 0) }}</span>
          </div>
          <div class="stat-row">
            <span>Net Worth:</span>
            <span class="stat-value gold">${{ formatNumber(netWorth) }}</span>
          </div>
        </div>

        <!-- Combat Stats -->
        <div class="stat-card">
          <h3>Combat Stats</h3>
          <div class="stat-row">
            <span>Health:</span>
            <span class="stat-value health">{{ player.health }}/{{ player.max_health }}</span>
          </div>
          <div class="stat-row">
            <span>Energy:</span>
            <span class="stat-value energy">{{ player.energy }}/{{ player.max_energy }}</span>
          </div>
          <div class="stat-row">
            <span>Strength:</span>
            <span class="stat-value">{{ player.strength || 0 }}</span>
          </div>
          <div class="stat-row">
            <span>Defense:</span>
            <span class="stat-value">{{ player.defense || 0 }}</span>
          </div>
          <div class="stat-row">
            <span>Speed:</span>
            <span class="stat-value">{{ player.speed || 0 }}</span>
          </div>
        </div>

        <!-- Game Activity Stats -->
        <div class="stat-card">
          <h3>Game Activity</h3>
          <div class="stat-row">
            <span>Crimes Committed:</span>
            <span class="stat-value">{{ stats.crimes_committed || 0 }}</span>
          </div>
          <div class="stat-row">
            <span>Battles Won:</span>
            <span class="stat-value success">{{ stats.battles_won || 0 }}</span>
          </div>
          <div class="stat-row">
            <span>Battles Lost:</span>
            <span class="stat-value danger">{{ stats.battles_lost || 0 }}</span>
          </div>
          <div class="stat-row">
            <span>Win Ratio:</span>
            <span class="stat-value">{{ winRatio }}%</span>
          </div>
          <div class="stat-row">
            <span>Total Kills:</span>
            <span class="stat-value">{{ stats.total_kills || 0 }}</span>
          </div>
        </div>

        <!-- Criminal Record -->
        <div class="stat-card">
          <h3>Criminal Record</h3>
          <div class="stat-row">
            <span>Jail Time:</span>
            <span class="stat-value">{{ formatNumber(stats.jail_time || 0) }} minutes</span>
          </div>
          <div class="stat-row">
            <span>Times Jailed:</span>
            <span class="stat-value">{{ stats.times_jailed || 0 }}</span>
          </div>
          <div class="stat-row">
            <span>Bounties Claimed:</span>
            <span class="stat-value">{{ stats.bounties_claimed || 0 }}</span>
          </div>
          <div class="stat-row">
            <span>Hospital Visits:</span>
            <span class="stat-value">{{ stats.hospital_visits || 0 }}</span>
          </div>
        </div>

        <!-- Financial Stats -->
        <div class="stat-card">
          <h3>Financial Stats</h3>
          <div class="stat-row">
            <span>Total Earned:</span>
            <span class="stat-value cash">${{ formatNumber(stats.total_earned || 0) }}</span>
          </div>
          <div class="stat-row">
            <span>Total Spent:</span>
            <span class="stat-value">${{ formatNumber(stats.total_spent || 0) }}</span>
          </div>
          <div class="stat-row">
            <span>Properties Owned:</span>
            <span class="stat-value">{{ stats.properties_owned || 0 }}</span>
          </div>
          <div class="stat-row">
            <span>Cars Owned:</span>
            <span class="stat-value">{{ stats.cars_owned || 0 }}</span>
          </div>
        </div>

        <!-- Social Stats -->
        <div class="stat-card">
          <h3>Social Stats</h3>
          <div class="stat-row">
            <span>Gang:</span>
            <span class="stat-value">{{ player.gang?.name || 'None' }}</span>
          </div>
          <div class="stat-row">
            <span>Gang Rank:</span>
            <span class="stat-value">{{ player.gang_rank || 'N/A' }}</span>
          </div>
          <div class="stat-row">
            <span>Forum Posts:</span>
            <span class="stat-value">{{ stats.forum_posts || 0 }}</span>
          </div>
          <div class="stat-row">
            <span>Messages Sent:</span>
            <span class="stat-value">{{ stats.messages_sent || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Recent Achievements -->
      <div v-if="recentAchievements.length > 0" class="recent-achievements">
        <h3>Recent Achievements</h3>
        <div class="achievements-list">
          <div v-for="achievement in recentAchievements" :key="achievement.id" class="achievement-item">
            <span class="achievement-icon">🏆</span>
            <div class="achievement-info">
              <h4>{{ achievement.name }}</h4>
              <p>{{ achievement.description }}</p>
              <span class="achievement-date">{{ formatDate(achievement.unlocked_at) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Account Info -->
      <div class="account-info">
        <h3>Account Information</h3>
        <div class="info-row">
          <span>Member Since:</span>
          <span>{{ formatDate(player.created_at) }}</span>
        </div>
        <div class="info-row">
          <span>Last Login:</span>
          <span>{{ formatDate(player.last_login) }}</span>
        </div>
        <div class="info-row">
          <span>Total Play Time:</span>
          <span>{{ formatPlayTime(stats.total_playtime || 0) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const error = ref(null)
const player = ref(null)
const stats = ref({})
const recentAchievements = ref([])

const netWorth = computed(() => {
  if (!player.value) return 0
  return (player.value.cash || 0) + (player.value.bank_balance || 0)
})

const winRatio = computed(() => {
  if (!stats.value) return 0
  const won = stats.value.battles_won || 0
  const lost = stats.value.battles_lost || 0
  const total = won + lost
  return total > 0 ? ((won / total) * 100).toFixed(1) : 0
})

onMounted(() => {
  loadProfile()
})

async function loadProfile() {
  loading.value = true
  error.value = null
  try {
    // Load user data first
    const playerRes = await api.get('/user')
    player.value = playerRes.data
    
    // Try to load stats, but don't fail if endpoint doesn't exist
    try {
      const statsRes = await api.get('/stats')
      stats.value = statsRes.data
    } catch (statsErr) {
      console.warn('Stats endpoint not available:', statsErr)
      // Use default/empty stats
      stats.value = {}
    }
    
    // Try to load achievements
    try {
      const achievementsRes = await api.get('/achievements')
      recentAchievements.value = (achievementsRes.data.data || achievementsRes.data).filter(a => a.unlocked_at).slice(0, 5)
    } catch (achErr) {
      console.warn('Achievements endpoint error:', achErr)
      recentAchievements.value = []
    }
    
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load profile'
    console.error('Profile error:', err)
  } finally {
    loading.value = false
  }
}

function formatNumber(num) {
  return new Intl.NumberFormat('en-US').format(num)
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

function formatPlayTime(minutes) {
  if (minutes < 60) return `${minutes} minutes`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours} hours`
  const days = Math.floor(hours / 24)
  return `${days} days`
}
</script>

<style scoped>
.profile-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

.loading, .error {
  text-align: center;
  padding: 40px;
  color: #8b8b8b;
}

.error {
  color: #e94560;
}

.player-card {
  background: linear-gradient(135deg, #16213e 0%, #0f3460 100%);
  border-radius: 12px;
  padding: 30px;
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.player-avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, #e94560 0%, #ff6b81 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 48px;
  color: white;
  font-weight: bold;
  box-shadow: 0 4px 8px rgba(233, 69, 96, 0.4);
}

.player-info {
  flex: 1;
}

.player-info h2 {
  margin: 0 0 10px 0;
  color: #fff;
  font-size: 32px;
}

.player-info .rank {
  color: #e94560;
  font-size: 18px;
  margin: 5px 0;
  font-weight: 600;
}

.player-info .location {
  color: #8b8b8b;
  margin: 5px 0;
}

.player-badges {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.badge {
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 600;
  white-space: nowrap;
}

.badge.premium {
  background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
  color: #000;
}

.badge.admin {
  background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
  color: #fff;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: #16213e;
  border-radius: 8px;
  padding: 20px;
  border: 1px solid #0f3460;
}

.stat-card h3 {
  margin: 0 0 20px 0;
  color: #e94560;
  font-size: 18px;
  border-bottom: 2px solid #0f3460;
  padding-bottom: 10px;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #0f3460;
}

.stat-row:last-child {
  border-bottom: none;
}

.stat-row span:first-child {
  color: #8b8b8b;
}

.stat-value {
  color: #fff;
  font-weight: 600;
}

.stat-value.cash {
  color: #4caf50;
}

.stat-value.gold {
  color: #ffd700;
}

.stat-value.health {
  color: #e94560;
}

.stat-value.energy {
  color: #ffc107;
}

.stat-value.success {
  color: #4caf50;
}

.stat-value.danger {
  color: #e94560;
}

.recent-achievements {
  background: #16213e;
  border-radius: 8px;
  padding: 20px;
  border: 1px solid #0f3460;
  margin-bottom: 30px;
}

.recent-achievements h3 {
  margin: 0 0 20px 0;
  color: #e94560;
  font-size: 18px;
}

.achievements-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.achievement-item {
  display: flex;
  gap: 15px;
  padding: 15px;
  background: #0f3460;
  border-radius: 8px;
}

.achievement-icon {
  font-size: 32px;
}

.achievement-info h4 {
  margin: 0 0 5px 0;
  color: #fff;
}

.achievement-info p {
  margin: 0 0 5px 0;
  color: #8b8b8b;
  font-size: 14px;
}

.achievement-date {
  color: #e94560;
  font-size: 12px;
}

.account-info {
  background: #16213e;
  border-radius: 8px;
  padding: 20px;
  border: 1px solid #0f3460;
}

.account-info h3 {
  margin: 0 0 20px 0;
  color: #e94560;
  font-size: 18px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #0f3460;
  color: #fff;
}

.info-row:last-child {
  border-bottom: none;
}

.info-row span:first-child {
  color: #8b8b8b;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .player-card {
    flex-direction: column;
    text-align: center;
  }
  
  .player-info h2 {
    font-size: 24px;
  }
}
</style>
