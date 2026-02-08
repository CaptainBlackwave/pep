<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/services/api';

const events = ref([]);
const activeEvent = ref(null);
const myParticipation = ref(null);
const eventHistory = ref([]);
const loading = ref(true);
const processing = ref(false);
const error = ref('');
const successMessage = ref('');
const activeTab = ref('current');

const formatMoney = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0
  }).format(amount);
};

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US').format(num);
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const timeRemaining = computed(() => {
  if (!activeEvent.value?.ends_at) return null;

  const now = new Date();
  const end = new Date(activeEvent.value.ends_at);
  const diff = end - now;

  if (diff <= 0) return 'Ended';

  const hours = Math.floor(diff / (1000 * 60 * 60));
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

  return `${hours}h ${minutes}m remaining`;
});

const fetchData = async () => {
  try {
    loading.value = true;
    error.value = '';
    const response = await api.get('/events');
    events.value = response.data.events || [];
    activeEvent.value = response.data.activeEvent || null;
    myParticipation.value = response.data.myParticipation || null;
    eventHistory.value = response.data.history || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load events';
  } finally {
    loading.value = false;
  }
};

const joinEvent = async (eventId) => {
  if (processing.value) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post(`/events/${eventId}/join`);

    successMessage.value = response.data.message || 'Joined event successfully!';
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to join event';
  } finally {
    processing.value = false;
  }
};

const claimReward = async (eventId) => {
  if (processing.value) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post(`/events/${eventId}/claim`);

    successMessage.value = response.data.message || 'Reward claimed!';
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to claim reward';
  } finally {
    processing.value = false;
  }
};

const getEventTypeIcon = (type) => {
  const icons = {
    'double_xp': '⭐',
    'double_money': '💰',
    'boss_raid': '👹',
    'pvp_tournament': '⚔️',
    'collection': '📦',
    'hunt': '🎯',
    'default': '🎉'
  };
  return icons[type] || icons.default;
};

const getEventTypeColor = (type) => {
  const colors = {
    'double_xp': '#f59e0b',
    'double_money': '#22c55e',
    'boss_raid': '#ef4444',
    'pvp_tournament': '#8b5cf6',
    'collection': '#3b82f6',
    'hunt': '#ec4899'
  };
  return colors[type] || '#6b7280';
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="events-view">
    <div class="page-header">
      <h1>🎉 Events</h1>
      <p class="subtitle">Participate in special events for exclusive rewards</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading events...</p>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="alert alert-error">
      {{ error }}
    </div>

    <!-- Success Message -->
    <div v-if="successMessage" class="alert alert-success">
      {{ successMessage }}
    </div>

    <!-- Content -->
    <div v-if="!loading" class="events-content">

      <!-- Active Event Banner -->
      <div v-if="activeEvent" class="active-event-banner" :style="{ borderColor: getEventTypeColor(activeEvent.type) }">
        <div class="event-icon" :style="{ backgroundColor: getEventTypeColor(activeEvent.type) }">
          {{ getEventTypeIcon(activeEvent.type) }}
        </div>
        <div class="event-info">
          <h2>{{ activeEvent.name }}</h2>
          <p class="event-description">{{ activeEvent.description }}</p>
          <div class="event-meta">
            <span class="time-remaining">⏱️ {{ timeRemaining }}</span>
            <span class="participants">👥 {{ formatNumber(activeEvent.participants || 0) }} participants</span>
          </div>
        </div>
        <div class="event-action">
          <button
            v-if="!myParticipation"
            @click="joinEvent(activeEvent.id)"
            :disabled="processing"
            class="btn btn-primary btn-lg"
          >
            Join Event
          </button>
          <div v-else class="participation-status">
            <span class="status-badge">✓ Participating</span>
            <span class="my-score">Your Score: {{ formatNumber(myParticipation.score || 0) }}</span>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button
          :class="['tab', { active: activeTab === 'current' }]"
          @click="activeTab = 'current'"
        >
          Current Events
        </button>
        <button
          :class="['tab', { active: activeTab === 'upcoming' }]"
          @click="activeTab = 'upcoming'"
        >
          Upcoming
        </button>
        <button
          :class="['tab', { active: activeTab === 'history' }]"
          @click="activeTab = 'history'"
        >
          History
        </button>
      </div>

      <!-- Current Events -->
      <div v-if="activeTab === 'current'" class="events-list">
        <div v-if="events.filter(e => e.status === 'active').length === 0" class="empty-state">
          <p>No active events at the moment. Check back soon!</p>
        </div>

        <div
          v-for="event in events.filter(e => e.status === 'active')"
          :key="event.id"
          class="event-card"
        >
          <div class="event-type-badge" :style="{ backgroundColor: getEventTypeColor(event.type) }">
            {{ getEventTypeIcon(event.type) }} {{ event.type_label || event.type }}
          </div>
          <h3>{{ event.name }}</h3>
          <p class="event-description">{{ event.description }}</p>

          <div class="event-rewards">
            <h4>Rewards</h4>
            <ul>
              <li v-for="(reward, index) in event.rewards" :key="index">
                {{ reward }}
              </li>
            </ul>
          </div>

          <div class="event-leaderboard" v-if="event.leaderboard">
            <h4>Top Players</h4>
            <div class="leaderboard-list">
              <div
                v-for="(player, index) in event.leaderboard.slice(0, 5)"
                :key="player.id"
                class="leaderboard-item"
              >
                <span class="rank">#{{ index + 1 }}</span>
                <span class="player-name">{{ player.username }}</span>
                <span class="player-score">{{ formatNumber(player.score) }}</span>
              </div>
            </div>
          </div>

          <div class="event-footer">
            <span class="ends-at">Ends: {{ formatDate(event.ends_at) }}</span>
            <button
              v-if="!event.is_participating"
              @click="joinEvent(event.id)"
              :disabled="processing"
              class="btn btn-primary"
            >
              Join
            </button>
            <span v-else class="participating-badge">Participating</span>
          </div>
        </div>
      </div>

      <!-- Upcoming Events -->
      <div v-if="activeTab === 'upcoming'" class="events-list">
        <div v-if="events.filter(e => e.status === 'upcoming').length === 0" class="empty-state">
          <p>No upcoming events scheduled.</p>
        </div>

        <div
          v-for="event in events.filter(e => e.status === 'upcoming')"
          :key="event.id"
          class="event-card upcoming"
        >
          <div class="event-type-badge" :style="{ backgroundColor: getEventTypeColor(event.type) }">
            {{ getEventTypeIcon(event.type) }} {{ event.type_label || event.type }}
          </div>
          <h3>{{ event.name }}</h3>
          <p class="event-description">{{ event.description }}</p>

          <div class="event-footer">
            <span class="starts-at">Starts: {{ formatDate(event.starts_at) }}</span>
            <span class="upcoming-badge">Coming Soon</span>
          </div>
        </div>
      </div>

      <!-- Event History -->
      <div v-if="activeTab === 'history'" class="events-list">
        <div v-if="eventHistory.length === 0" class="empty-state">
          <p>No event history yet.</p>
        </div>

        <div
          v-for="event in eventHistory"
          :key="event.id"
          class="event-card history"
        >
          <div class="event-type-badge" :style="{ backgroundColor: getEventTypeColor(event.type) }">
            {{ getEventTypeIcon(event.type) }} {{ event.type_label || event.type }}
          </div>
          <h3>{{ event.name }}</h3>

          <div class="history-details">
            <div class="history-stat">
              <span class="label">Your Rank</span>
              <span class="value">#{{ event.my_rank || 'N/A' }}</span>
            </div>
            <div class="history-stat">
              <span class="label">Your Score</span>
              <span class="value">{{ formatNumber(event.my_score || 0) }}</span>
            </div>
            <div class="history-stat">
              <span class="label">Participants</span>
              <span class="value">{{ formatNumber(event.total_participants || 0) }}</span>
            </div>
          </div>

          <div class="event-footer">
            <span class="ended-at">Ended: {{ formatDate(event.ended_at) }}</span>
            <button
              v-if="event.has_unclaimed_reward"
              @click="claimReward(event.id)"
              :disabled="processing"
              class="btn btn-success"
            >
              Claim Reward
            </button>
            <span v-else-if="event.reward_claimed" class="claimed-badge">Reward Claimed</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.events-view {
  max-width: 900px;
  margin: 0 auto;
  padding: 1rem;
}

.page-header {
  text-align: center;
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  color: var(--color-heading);
}

.subtitle {
  color: var(--color-text-muted);
}

.loading-state {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.alert {
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.alert-error {
  background-color: rgba(239, 68, 68, 0.1);
  border: 1px solid var(--color-danger);
  color: var(--color-danger);
}

.alert-success {
  background-color: rgba(34, 197, 94, 0.1);
  border: 1px solid var(--color-success);
  color: var(--color-success);
}

.active-event-banner {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, var(--color-background-soft), var(--color-background));
  border: 2px solid;
  border-radius: 1rem;
  margin-bottom: 2rem;
}

.event-icon {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  border-radius: 1rem;
  flex-shrink: 0;
}

.event-info {
  flex: 1;
}

.event-info h2 {
  margin-bottom: 0.5rem;
  color: var(--color-heading);
}

.event-description {
  color: var(--color-text-muted);
  margin-bottom: 0.75rem;
}

.event-meta {
  display: flex;
  gap: 1.5rem;
  font-size: 0.875rem;
}

.time-remaining {
  color: var(--color-warning);
  font-weight: 600;
}

.participants {
  color: var(--color-text-muted);
}

.event-action {
  flex-shrink: 0;
}

.participation-status {
  text-align: center;
}

.status-badge {
  display: block;
  color: var(--color-success);
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.my-score {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 0.5rem;
}

.tab {
  padding: 0.5rem 1rem;
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  border-radius: 0.375rem;
  transition: all 0.2s;
}

.tab:hover {
  color: var(--color-text);
  background: var(--color-background-soft);
}

.tab.active {
  color: var(--color-primary);
  background: var(--color-background-soft);
  font-weight: 600;
}

.events-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.event-card {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
  padding: 1.5rem;
}

.event-card.upcoming {
  opacity: 0.8;
}

.event-card.history {
  background: var(--color-background);
}

.event-type-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: white;
  margin-bottom: 0.75rem;
  text-transform: uppercase;
}

.event-card h3 {
  margin-bottom: 0.5rem;
  color: var(--color-heading);
}

.event-rewards {
  margin-top: 1rem;
  padding: 1rem;
  background: var(--color-background);
  border-radius: 0.5rem;
}

.event-rewards h4 {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  margin-bottom: 0.5rem;
}

.event-rewards ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.event-rewards li {
  padding: 0.25rem 0;
  color: var(--color-success);
}

.event-leaderboard {
  margin-top: 1rem;
}

.event-leaderboard h4 {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  margin-bottom: 0.5rem;
}

.leaderboard-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.leaderboard-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
  background: var(--color-background);
  border-radius: 0.25rem;
}

.leaderboard-item .rank {
  font-weight: 700;
  color: var(--color-primary);
  min-width: 2rem;
}

.leaderboard-item .player-name {
  flex: 1;
}

.leaderboard-item .player-score {
  font-weight: 600;
  color: var(--color-heading);
}

.history-details {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-top: 1rem;
}

.history-stat {
  text-align: center;
  padding: 0.75rem;
  background: var(--color-background-soft);
  border-radius: 0.5rem;
}

.history-stat .label {
  display: block;
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin-bottom: 0.25rem;
}

.history-stat .value {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-heading);
}

.event-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border);
}

.ends-at, .starts-at, .ended-at {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.participating-badge, .upcoming-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.participating-badge {
  background: rgba(34, 197, 94, 0.1);
  color: var(--color-success);
}

.upcoming-badge {
  background: rgba(59, 130, 246, 0.1);
  color: var(--color-primary);
}

.claimed-badge {
  color: var(--color-text-muted);
  font-size: 0.875rem;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: var(--color-text-muted);
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-lg {
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
}

.btn-primary {
  background-color: var(--color-primary);
  color: white;
}

.btn-success {
  background-color: var(--color-success);
  color: white;
}

@media (max-width: 768px) {
  .active-event-banner {
    flex-direction: column;
    text-align: center;
  }

  .event-meta {
    justify-content: center;
  }

  .history-details {
    grid-template-columns: 1fr;
  }
}
</style>
