<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import api from '@/services/api';

const tournaments = ref([]);
const activeTournament = ref(null);
const myRegistration = ref(null);
const brackets = ref([]);
const pastTournaments = ref([]);
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

const timeUntil = computed(() => {
  if (!activeTournament.value?.starts_at) return null;

  const now = new Date();
  const start = new Date(activeTournament.value.starts_at);
  const diff = start - now;

  if (diff <= 0) return 'Started';

  const days = Math.floor(diff / (1000 * 60 * 60 * 24));
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

  if (days > 0) return `${days}d ${hours}h`;
  if (hours > 0) return `${hours}h ${minutes}m`;
  return `${minutes}m`;
});

const fetchData = async () => {
  try {
    loading.value = true;
    error.value = '';
    const response = await api.get('/tournaments');
    tournaments.value = response.data.tournaments || [];
    activeTournament.value = response.data.activeTournament || null;
    myRegistration.value = response.data.myRegistration || null;
    brackets.value = response.data.brackets || [];
    pastTournaments.value = response.data.pastTournaments || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load tournaments';
  } finally {
    loading.value = false;
  }
};

const registerForTournament = async (tournamentId) => {
  if (processing.value) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post(`/tournaments/${tournamentId}/register`);

    successMessage.value = response.data.message || 'Registered successfully!';
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to register';
  } finally {
    processing.value = false;
  }
};

const withdrawFromTournament = async (tournamentId) => {
  if (processing.value || !confirm('Withdraw from this tournament?')) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post(`/tournaments/${tournamentId}/withdraw`);

    successMessage.value = response.data.message || 'Withdrawn successfully';
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to withdraw';
  } finally {
    processing.value = false;
  }
};

const getTournamentTypeIcon = (type) => {
  const icons = {
    'pvp': '⚔️',
    'racing': '🏎️',
    'crime': '🔫',
    'strength': '💪',
    'wealth': '💰',
    'default': '🏆'
  };
  return icons[type] || icons.default;
};

const getStatusColor = (status) => {
  const colors = {
    'registration': '#3b82f6',
    'in_progress': '#22c55e',
    'completed': '#6b7280',
    'cancelled': '#ef4444'
  };
  return colors[status] || '#6b7280';
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="tournament-view">
    <div class="page-header">
      <h1>🏆 Tournaments</h1>
      <p class="subtitle">Compete against other players for glory and prizes</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading tournaments...</p>
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
    <div v-if="!loading" class="tournament-content">

      <!-- Active Tournament Banner -->
      <div v-if="activeTournament" class="active-tournament-banner">
        <div class="tournament-icon">
          {{ getTournamentTypeIcon(activeTournament.type) }}
        </div>
        <div class="tournament-info">
          <span class="tournament-type">{{ activeTournament.type_label || activeTournament.type }}</span>
          <h2>{{ activeTournament.name }}</h2>
          <p>{{ activeTournament.description }}</p>
          <div class="tournament-meta">
            <span class="participants">
              👥 {{ formatNumber(activeTournament.participant_count || 0) }} / {{ formatNumber(activeTournament.max_participants || 'Unlimited') }}
            </span>
            <span class="prize-pool">
              💰 Prize: {{ formatMoney(activeTournament.prize_pool || 0) }}
            </span>
            <span class="starts-in" v-if="activeTournament.status === 'registration'">
              ⏱️ Starts in {{ timeUntil }}
            </span>
            <span class="in-progress" v-else-if="activeTournament.status === 'in_progress'">
              🔴 In Progress
            </span>
          </div>
        </div>
        <div class="tournament-actions">
          <div v-if="myRegistration">
            <span class="registered-badge">✓ Registered</span>
            <button
              v-if="activeTournament.status === 'registration'"
              @click="withdrawFromTournament(activeTournament.id)"
              :disabled="processing"
              class="btn btn-danger btn-sm"
            >
              Withdraw
            </button>
          </div>
          <button
            v-else-if="activeTournament.status === 'registration'"
            @click="registerForTournament(activeTournament.id)"
            :disabled="processing"
            class="btn btn-primary btn-lg"
          >
            Register Now
          </button>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button
          :class="['tab', { active: activeTab === 'current' }]"
          @click="activeTab = 'current'"
        >
          Current
        </button>
        <button
          :class="['tab', { active: activeTab === 'brackets' }]"
          @click="activeTab = 'brackets'"
        >
          Brackets
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

      <!-- Current Tournaments -->
      <div v-if="activeTab === 'current'" class="tournaments-list">
        <div v-if="tournaments.filter(t => t.status === 'in_progress' || t.status === 'registration').length === 0" class="empty-state">
          <p>No active tournaments at the moment.</p>
        </div>

        <div
          v-for="tournament in tournaments.filter(t => t.status === 'in_progress' || t.status === 'registration')"
          :key="tournament.id"
          class="tournament-card"
        >
          <div class="card-header">
            <span class="tournament-type-icon">{{ getTournamentTypeIcon(tournament.type) }}</span>
            <div class="tournament-title">
              <h3>{{ tournament.name }}</h3>
              <span class="status-badge" :style="{ backgroundColor: getStatusColor(tournament.status) }">
                {{ tournament.status === 'registration' ? 'Registration Open' : 'In Progress' }}
              </span>
            </div>
          </div>

          <p class="tournament-description">{{ tournament.description }}</p>

          <div class="tournament-details">
            <div class="detail">
              <span class="detail-label">Entry Fee</span>
              <span class="detail-value">{{ tournament.entry_fee > 0 ? formatMoney(tournament.entry_fee) : 'Free' }}</span>
            </div>
            <div class="detail">
              <span class="detail-label">Prize Pool</span>
              <span class="detail-value highlight">{{ formatMoney(tournament.prize_pool || 0) }}</span>
            </div>
            <div class="detail">
              <span class="detail-label">Participants</span>
              <span class="detail-value">{{ formatNumber(tournament.participant_count || 0) }}</span>
            </div>
            <div class="detail">
              <span class="detail-label">Starts</span>
              <span class="detail-value">{{ formatDate(tournament.starts_at) }}</span>
            </div>
          </div>

          <div class="prize-breakdown" v-if="tournament.prizes">
            <h4>Prize Distribution</h4>
            <div class="prizes">
              <div v-for="(prize, index) in tournament.prizes" :key="index" class="prize-item">
                <span class="place">{{ index + 1 }}{{ ['st', 'nd', 'rd'][index] || 'th' }}</span>
                <span class="prize-amount">{{ formatMoney(prize) }}</span>
              </div>
            </div>
          </div>

          <div class="card-actions">
            <button
              v-if="tournament.status === 'registration' && !tournament.is_registered"
              @click="registerForTournament(tournament.id)"
              :disabled="processing"
              class="btn btn-primary"
            >
              Register
            </button>
            <span v-else-if="tournament.is_registered" class="registered-label">
              ✓ You're registered
            </span>
          </div>
        </div>
      </div>

      <!-- Brackets -->
      <div v-if="activeTab === 'brackets'" class="brackets-section">
        <div v-if="brackets.length === 0" class="empty-state">
          <p>No brackets available. Tournament needs to start first.</p>
        </div>

        <div v-else class="brackets-container">
          <div v-for="(round, roundIndex) in brackets" :key="roundIndex" class="bracket-round">
            <h3 class="round-title">Round {{ roundIndex + 1 }}</h3>
            <div class="matches">
              <div v-for="match in round" :key="match.id" class="match-card">
                <div :class="['player-slot', { winner: match.winner_id === match.player1_id }]">
                  <span class="player-name">{{ match.player1_name || 'TBD' }}</span>
                  <span class="player-score">{{ match.player1_score ?? '-' }}</span>
                </div>
                <div class="vs">VS</div>
                <div :class="['player-slot', { winner: match.winner_id === match.player2_id }]">
                  <span class="player-name">{{ match.player2_name || 'TBD' }}</span>
                  <span class="player-score">{{ match.player2_score ?? '-' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming -->
      <div v-if="activeTab === 'upcoming'" class="tournaments-list">
        <div v-if="tournaments.filter(t => t.status === 'scheduled').length === 0" class="empty-state">
          <p>No upcoming tournaments scheduled.</p>
        </div>

        <div
          v-for="tournament in tournaments.filter(t => t.status === 'scheduled')"
          :key="tournament.id"
          class="tournament-card upcoming"
        >
          <div class="card-header">
            <span class="tournament-type-icon">{{ getTournamentTypeIcon(tournament.type) }}</span>
            <div class="tournament-title">
              <h3>{{ tournament.name }}</h3>
              <span class="status-badge coming-soon">Coming Soon</span>
            </div>
          </div>

          <p class="tournament-description">{{ tournament.description }}</p>

          <div class="tournament-details">
            <div class="detail">
              <span class="detail-label">Registration Opens</span>
              <span class="detail-value">{{ formatDate(tournament.registration_opens) }}</span>
            </div>
            <div class="detail">
              <span class="detail-label">Prize Pool</span>
              <span class="detail-value highlight">{{ formatMoney(tournament.prize_pool || 0) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- History -->
      <div v-if="activeTab === 'history'" class="tournaments-list">
        <div v-if="pastTournaments.length === 0" class="empty-state">
          <p>No completed tournaments yet.</p>
        </div>

        <div
          v-for="tournament in pastTournaments"
          :key="tournament.id"
          class="tournament-card history"
        >
          <div class="card-header">
            <span class="tournament-type-icon">{{ getTournamentTypeIcon(tournament.type) }}</span>
            <div class="tournament-title">
              <h3>{{ tournament.name }}</h3>
              <span class="status-badge completed">Completed</span>
            </div>
          </div>

          <div class="winners-podium">
            <div class="winner second">
              <span class="medal">🥈</span>
              <span class="winner-name">{{ tournament.second_place || 'N/A' }}</span>
            </div>
            <div class="winner first">
              <span class="medal">🥇</span>
              <span class="winner-name">{{ tournament.first_place || 'N/A' }}</span>
            </div>
            <div class="winner third">
              <span class="medal">🥉</span>
              <span class="winner-name">{{ tournament.third_place || 'N/A' }}</span>
            </div>
          </div>

          <div class="tournament-result">
            <span v-if="tournament.my_rank" class="my-result">
              Your Rank: #{{ tournament.my_rank }}
            </span>
            <span class="ended-date">Ended: {{ formatDate(tournament.ended_at) }}</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.tournament-view {
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

.active-tournament-banner {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.1));
  border: 2px solid #f59e0b;
  border-radius: 1rem;
  margin-bottom: 2rem;
}

.tournament-icon {
  font-size: 3rem;
  flex-shrink: 0;
}

.tournament-info {
  flex: 1;
}

.tournament-type {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: #f59e0b;
  font-weight: 600;
}

.tournament-info h2 {
  margin: 0.25rem 0 0.5rem;
  color: var(--color-heading);
}

.tournament-info p {
  color: var(--color-text-muted);
  margin-bottom: 0.75rem;
}

.tournament-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  font-size: 0.875rem;
}

.starts-in {
  color: var(--color-warning);
  font-weight: 600;
}

.in-progress {
  color: var(--color-success);
  font-weight: 600;
}

.tournament-actions {
  flex-shrink: 0;
  text-align: center;
}

.registered-badge {
  display: block;
  color: var(--color-success);
  font-weight: 600;
  margin-bottom: 0.5rem;
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

.tournaments-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.tournament-card {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
  padding: 1.5rem;
}

.tournament-card.upcoming {
  opacity: 0.85;
}

.tournament-card.history {
  background: var(--color-background);
}

.card-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.tournament-type-icon {
  font-size: 2rem;
}

.tournament-title h3 {
  margin: 0;
  color: var(--color-heading);
}

.status-badge {
  display: inline-block;
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: white;
  margin-top: 0.25rem;
}

.status-badge.coming-soon {
  background: #6b7280;
}

.status-badge.completed {
  background: #22c55e;
}

.tournament-description {
  color: var(--color-text-muted);
  margin-bottom: 1rem;
}

.tournament-details {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

.detail {
  text-align: center;
  padding: 0.75rem;
  background: var(--color-background);
  border-radius: 0.5rem;
}

.detail-label {
  display: block;
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin-bottom: 0.25rem;
}

.detail-value {
  font-weight: 600;
  color: var(--color-heading);
}

.detail-value.highlight {
  color: var(--color-success);
}

.prize-breakdown {
  margin-bottom: 1rem;
  padding: 1rem;
  background: var(--color-background);
  border-radius: 0.5rem;
}

.prize-breakdown h4 {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  margin-bottom: 0.75rem;
}

.prizes {
  display: flex;
  gap: 1rem;
}

.prize-item {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.place {
  font-weight: 600;
  color: var(--color-heading);
}

.prize-amount {
  color: var(--color-success);
}

.card-actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border);
}

.registered-label {
  color: var(--color-success);
  font-weight: 500;
}

.brackets-container {
  display: flex;
  gap: 2rem;
  overflow-x: auto;
  padding: 1rem 0;
}

.bracket-round {
  min-width: 200px;
}

.round-title {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  text-align: center;
  margin-bottom: 1rem;
}

.matches {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.match-card {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  padding: 0.75rem;
}

.player-slot {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem;
  border-radius: 0.25rem;
}

.player-slot.winner {
  background: rgba(34, 197, 94, 0.1);
}

.player-slot.winner .player-name {
  color: var(--color-success);
  font-weight: 600;
}

.player-score {
  font-weight: 600;
}

.vs {
  text-align: center;
  font-size: 0.75rem;
  color: var(--color-text-muted);
  padding: 0.25rem 0;
}

.winners-podium {
  display: flex;
  justify-content: center;
  align-items: flex-end;
  gap: 1rem;
  margin: 1rem 0;
  padding: 1rem;
  background: var(--color-background-soft);
  border-radius: 0.5rem;
}

.winner {
  text-align: center;
  padding: 0.75rem;
}

.winner.first {
  order: 2;
}

.winner.second {
  order: 1;
}

.winner.third {
  order: 3;
}

.medal {
  font-size: 2rem;
  display: block;
}

.winner.first .medal {
  font-size: 2.5rem;
}

.winner-name {
  display: block;
  font-weight: 600;
  color: var(--color-heading);
  margin-top: 0.25rem;
}

.tournament-result {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border);
}

.my-result {
  color: var(--color-primary);
  font-weight: 600;
}

.ended-date {
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

.btn-sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
}

.btn-lg {
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
}

.btn-primary {
  background-color: var(--color-primary);
  color: white;
}

.btn-danger {
  background-color: var(--color-danger);
  color: white;
}

@media (max-width: 768px) {
  .active-tournament-banner {
    flex-direction: column;
    text-align: center;
  }

  .tournament-meta {
    justify-content: center;
  }

  .tournament-details {
    grid-template-columns: repeat(2, 1fr);
  }

  .brackets-container {
    flex-direction: column;
  }

  .winners-podium {
    flex-wrap: wrap;
  }
}
</style>
