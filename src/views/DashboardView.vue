<template>
  <div class="dashboard-view">
    <!-- User Info Banner -->
    <div class="user-banner">
      <div class="user-info">
        <h2 class="game-title">OpenPBBG</h2>
        <div class="user-stats-inline">
          <span class="user-rank">
            <span class="rank-badge">Hustler</span>
            <span class="rank-progress">━━━━</span>
          </span>
          <span class="stat-item">150 XP</span>
          <span class="stat-item">💵 $5,231</span>
          <span class="stat-item">❤️ 100/150</span>
          <span class="stat-item">⚡ 100/100</span>
          <span class="stat-item">📍 Detroit</span>
        </div>
      </div>
      <div class="user-actions">
        <router-link to="/admin" class="action-link">admin</router-link>
        <button @click="logout" class="logout-btn">Logout</button>
      </div>
    </div>

    <!-- Quick Access Cards -->
    <div class="quick-access">
      <router-link to="/profile" class="quick-card">
        <span class="card-icon">👤</span>
        <span class="card-label">Profile</span>
      </router-link>
      <router-link to="/chat" class="quick-card">
        <span class="card-icon">💬</span>
        <span class="card-label">Chat</span>
      </router-link>
      <router-link to="/activity" class="quick-card">
        <span class="card-icon">📊</span>
        <span class="card-label">Activity</span>
      </router-link>
      <router-link to="/wiki" class="quick-card">
        <span class="card-icon">📚</span>
        <span class="card-label">Wiki</span>
      </router-link>
    </div>

    <!-- Game Features Section -->
    <h2 class="section-title">Game Features</h2>
    <div class="features-grid">
      <router-link to="/scavenge" class="feature-card">
        <div class="feature-icon">🔍</div>
        <h3 class="feature-title">Scavenge</h3>
        <p class="feature-desc">Search for valuable items and resources</p>
      </router-link>

      <router-link to="/jail" class="feature-card">
        <div class="feature-icon">⛓️</div>
        <h3 class="feature-title">Jail</h3>
        <p class="feature-desc">Bust players out of jail or serve your time</p>
      </router-link>

      <router-link to="/bank" class="feature-card">
        <div class="feature-icon">🏦</div>
        <h3 class="feature-title">Bank</h3>
        <p class="feature-desc">Deposit and withdraw money safely</p>
      </router-link>

      <router-link to="/theft" class="feature-card">
        <div class="feature-icon">🚗</div>
        <h3 class="feature-title">Car Theft (GTA)</h3>
        <p class="feature-desc">Steal cars for quick cash</p>
      </router-link>

      <router-link to="/hospital" class="feature-card">
        <div class="feature-icon">🏥</div>
        <h3 class="feature-title">Hospital</h3>
        <p class="feature-desc">Heal your wounds and recover health</p>
      </router-link>

      <router-link to="/travel" class="feature-card">
        <div class="feature-icon">✈️</div>
        <h3 class="feature-title">Travel</h3>
        <p class="feature-desc">Visit different cities and locations</p>
      </router-link>

      <router-link to="/gym" class="feature-card">
        <div class="feature-icon">💪</div>
        <h3 class="feature-title">Gym</h3>
        <p class="feature-desc">Train your stats and become stronger</p>
      </router-link>

      <router-link to="/forums" class="feature-card">
        <div class="feature-icon">💬</div>
        <h3 class="feature-title">Forums</h3>
        <p class="feature-desc">Discuss with other players</p>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const logout = () => {
  localStorage.removeItem('auth_token')
  router.push('/login')
}
</script>

<style scoped>
.dashboard-view {
  max-width: 1400px;
  margin: 0 auto;
}

/* User Banner */
.user-banner {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 20, 25, 0.9) 100%);
  border: 1px solid rgba(0, 188, 212, 0.3);
  border-radius: 0.5rem;
  padding: 1.5rem 2rem;
  margin-bottom: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.user-info {
  flex: 1;
}

.game-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #00bcd4;
  margin: 0 0 0.75rem 0;
}

.user-stats-inline {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  align-items: center;
}

.user-rank {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.rank-badge {
  background: linear-gradient(135deg, #00bcd4 0%, #0891b2 100%);
  padding: 0.25rem 0.75rem;
  border-radius: 0.25rem;
  font-weight: 700;
  font-size: 0.875rem;
}

.rank-progress {
  color: #00bcd4;
  letter-spacing: 0.1em;
}

.stat-item {
  font-size: 0.9375rem;
  color: #cbd5e1;
  font-weight: 600;
}

.user-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.action-link {
  color: #00bcd4;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.875rem;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  transition: all 0.2s;
}

.action-link:hover {
  background: rgba(0, 188, 212, 0.1);
}

.logout-btn {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  border: none;
  padding: 0.625rem 1.25rem;
  border-radius: 0.375rem;
  font-weight: 700;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.logout-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* Quick Access */
.quick-access {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.quick-card {
  background: linear-gradient(135deg, rgba(30, 58, 138, 0.4) 0%, rgba(30, 64, 175, 0.4) 100%);
  border: 1px solid rgba(59, 130, 246, 0.3);
  border-radius: 0.5rem;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  text-decoration: none;
  color: #e2e8f0;
  transition: all 0.3s;
}

.quick-card:hover {
  transform: translateY(-4px);
  border-color: #3b82f6;
  box-shadow: 0 8px 24px rgba(59, 130, 246, 0.2);
}

.card-icon {
  font-size: 2rem;
}

.card-label {
  font-size: 1.125rem;
  font-weight: 600;
}

/* Section Title */
.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 1.5rem 0;
}

/* Features Grid */
.features-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
}

.feature-card {
  background: linear-gradient(135deg, rgba(6, 95, 134, 0.4) 0%, rgba(14, 116, 144, 0.4) 100%);
  border: 1px solid rgba(8, 145, 178, 0.3);
  border-radius: 0.5rem;
  padding: 2rem 1.5rem;
  text-decoration: none;
  color: #e2e8f0;
  transition: all 0.3s;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.feature-card:hover {
  transform: translateY(-6px);
  border-color: #0891b2;
  box-shadow: 0 12px 32px rgba(8, 145, 178, 0.3);
  background: linear-gradient(135deg, rgba(6, 95, 134, 0.6) 0%, rgba(14, 116, 144, 0.6) 100%);
}

.feature-icon {
  font-size: 3rem;
  line-height: 1;
}

.feature-title {
  font-size: 1.125rem;
  font-weight: 700;
  margin: 0;
  color: #ffffff;
}

.feature-desc {
  font-size: 0.875rem;
  color: #cbd5e1;
  margin: 0;
  line-height: 1.5;
}

/* Responsive */
@media (max-width: 1024px) {
  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .quick-access {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .user-banner {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .quick-access {
    grid-template-columns: 1fr;
  }
}
</style>
