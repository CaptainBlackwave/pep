<template>
  <div class="activity-container">
    <div class="activity-header">
      <h2>Activity Feed</h2>
      <div class="filter-tabs">
        <button
          v-for="filter in filters"
          :key="filter"
          :class="['filter-tab', {active: selectedFilter === filter}]"
          @click="selectedFilter = filter"
        >
          {{ capitalize(filter) }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading">Loading activities...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else class="activity-feed">
      <div v-if="filteredActivities.length === 0" class="empty-state">
        <p>No activities found</p>
      </div>

      <div v-else class="activity-list">
        <div
          v-for="activity in filteredActivities"
          :key="activity.id"
          :class="['activity-item', `type-${activity.type}`]"
        >
          <div class="activity-icon">
            {{ getActivityIcon(activity.type) }}
          </div>
          <div class="activity-content">
            <div class="activity-header-line">
              <span class="activity-user">{{ activity.user?.username }}</span>
              <span class="activity-action">{{ activity.action }}</span>
              <span v-if="activity.target" class="activity-target">{{ activity.target }}</span>
            </div>
            <p v-if="activity.description" class="activity-description">{{ activity.description }}</p>
            <div class="activity-details">
              <span v-if="activity.amount" class="activity-amount">
                ${{ formatNumber(activity.amount) }}
              </span>
              <span v-if="activity.xp_gained" class="activity-xp">
                +{{ formatNumber(activity.xp_gained) }} XP
              </span>
              <span class="activity-time">{{ formatTime(activity.created_at) }}</span>
            </div>
          </div>
          <div v-if="activity.result" :class="['activity-badge', activity.result]">
            {{ activity.result }}
          </div>
        </div>
      </div>

      <!-- Load More -->
      <div v-if="hasMore" class="load-more-container">
        <button @click="loadMore" :disabled="loadingMore" class="btn-load-more">
          {{ loadingMore ? 'Loading...' : 'Load More' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const error = ref(null)
const activities = ref([])
const selectedFilter = ref('all')
const page = ref(1)
const hasMore = ref(true)
const loadingMore = ref(false)

const filters = ['all', 'crime', 'combat', 'trade', 'gang', 'social', 'achievement']

const filteredActivities = computed(() => {
  if (selectedFilter.value === 'all') {
    return activities.value
  }
  return activities.value.filter(a => a.type === selectedFilter.value)
})

onMounted(() => {
  loadActivities()
})

async function loadActivities() {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/activity/my-activity', {
      params: { page: 1, per_page: 20 }
    })
    activities.value = response.data.data || response.data
    hasMore.value = response.data.current_page < response.data.last_page
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load activities'
    console.error('Activity error:', err)
    activities.value = []
    hasMore.value = false
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  if (loadingMore.value || !hasMore.value) return

  loadingMore.value = true
  page.value++

  try {
    const response = await api.get('/activity/my-activity', {
      params: { page: page.value, per_page: 20 }
    })
    const newActivities = response.data.data || response.data
    activities.value = [...activities.value, ...newActivities]
    hasMore.value = response.data.current_page < response.data.last_page
  } catch (err) {
    console.error('Failed to load more activities:', err)
  } finally {
    loadingMore.value = false
  }
}

function getActivityIcon(type) {
  const icons = {
    crime: '🔫',
    combat: '⚔️',
    trade: '💰',
    gang: '👥',
    social: '💬',
    achievement: '🏆',
    travel: '✈️',
    gym: '💪',
    hospital: '🏥',
    jail: '🔒',
    property: '🏠',
    racing: '🏎️'
  }
  return icons[type] || '📝'
}

function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function formatNumber(num) {
  return new Intl.NumberFormat('en-US').format(num)
}

function formatTime(timestamp) {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now - date

  if (diff < 60000) return 'just now'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`
  if (diff < 604800000) return `${Math.floor(diff / 86400000)}d ago`

  return date.toLocaleDateString()
}
</script>

<style scoped>
.activity-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}

.activity-header {
  margin-bottom: 30px;
}

.activity-header h2 {
  color: #e94560;
  margin: 0 0 20px 0;
  font-size: 28px;
}

.filter-tabs {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-tab {
  padding: 10px 20px;
  background: #16213e;
  color: #8b8b8b;
  border: 1px solid #0f3460;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.filter-tab:hover {
  background: #0f3460;
  color: #fff;
}

.filter-tab.active {
  background: #e94560;
  color: white;
  border-color: #e94560;
}

.loading, .error {
  text-align: center;
  padding: 40px;
  color: #8b8b8b;
}

.error {
  color: #e94560;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #8b8b8b;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.activity-item {
  display: flex;
  gap: 15px;
  padding: 20px;
  background: #16213e;
  border-left: 3px solid #0f3460;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.activity-item:hover {
  background: #1a2842;
  transform: translateX(5px);
}

.activity-item.type-crime {
  border-left-color: #ff6b6b;
}

.activity-item.type-combat {
  border-left-color: #e94560;
}

.activity-item.type-trade {
  border-left-color: #4caf50;
}

.activity-item.type-gang {
  border-left-color: #9b59b6;
}

.activity-item.type-social {
  border-left-color: #3498db;
}

.activity-item.type-achievement {
  border-left-color: #ffd700;
}

.activity-icon {
  font-size: 32px;
  line-height: 1;
}

.activity-content {
  flex: 1;
}

.activity-header-line {
  margin-bottom: 8px;
}

.activity-user {
  color: #e94560;
  font-weight: 600;
  margin-right: 5px;
}

.activity-action {
  color: #fff;
  margin-right: 5px;
}

.activity-target {
  color: #3498db;
  font-weight: 600;
}

.activity-description {
  color: #8b8b8b;
  font-size: 14px;
  margin: 5px 0;
}

.activity-details {
  display: flex;
  gap: 15px;
  margin-top: 10px;
  font-size: 13px;
}

.activity-amount {
  color: #4caf50;
  font-weight: 600;
}

.activity-xp {
  color: #ffd700;
  font-weight: 600;
}

.activity-time {
  color: #8b8b8b;
}

.activity-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  align-self: flex-start;
}

.activity-badge.success {
  background: rgba(76, 175, 80, 0.2);
  color: #4caf50;
  border: 1px solid #4caf50;
}

.activity-badge.failure {
  background: rgba(233, 69, 96, 0.2);
  color: #e94560;
  border: 1px solid #e94560;
}

.activity-badge.captured {
  background: rgba(255, 107, 107, 0.2);
  color: #ff6b6b;
  border: 1px solid #ff6b6b;
}

.load-more-container {
  text-align: center;
  margin-top: 30px;
}

.btn-load-more {
  padding: 12px 40px;
  background: #16213e;
  color: #e94560;
  border: 2px solid #e94560;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-load-more:hover:not(:disabled) {
  background: #e94560;
  color: white;
}

.btn-load-more:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .filter-tabs {
    gap: 5px;
  }

  .filter-tab {
    padding: 8px 12px;
    font-size: 13px;
  }

  .activity-item {
    padding: 15px;
  }

  .activity-icon {
    font-size: 24px;
  }
}
</style>
