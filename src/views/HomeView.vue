<template>
  <div class="home-view">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
      <div class="welcome-content">
        <div class="welcome-text">
          <h1>Welcome back, <span class="username">{{ playerStore.username }}</span>!</h1>
          <p>{{ currentRank }} | {{ currentLocation }}</p>
        </div>
        <div class="welcome-actions">
          <router-link to="/daily-rewards" class="daily-reward-btn">
            <span class="btn-icon">🎁</span>
            <span>Daily Reward</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
      <div class="stat-card level">
        <div class="stat-icon">⭐</div>
        <div class="stat-details">
          <span class="stat-label">Level</span>
          <span class="stat-value">{{ playerStore.level }}</span>
        </div>
        <div class="stat-bar">
          <div class="bar-fill" :style="{ width: playerStore.experiencePercent + '%' }"></div>
        </div>
      </div>
      <div class="stat-card cash">
        <div class="stat-icon">💰</div>
        <div class="stat-details">
          <span class="stat-label">Cash</span>
          <span class="stat-value">{{ formatMoney(playerStore.cash) }}</span>
        </div>
      </div>
      <div class="stat-card bank">
        <div class="stat-icon">🏦</div>
        <div class="stat-details">
          <span class="stat-label">Bank</span>
          <span class="stat-value">{{ formatMoney(playerStore.bank) }}</span>
        </div>
      </div>
      <div class="stat-card combat">
        <div class="stat-icon">⚔️</div>
        <div class="stat-details">
          <span class="stat-label">Power</span>
          <span class="stat-value">{{ totalPower }}</span>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <h2 class="section-title">Quick Actions</h2>
    <div class="quick-actions">
      <router-link to="/gym" class="action-card">
        <div class="action-icon">💪</div>
        <span>Train</span>
      </router-link>
      <router-link to="/hospital" class="action-card">
        <div class="action-icon">🏥</div>
        <span>Heal</span>
      </router-link>
      <router-link to="/crimes" class="action-card">
        <div class="action-icon">🔫</div>
        <span>Crimes</span>
      </router-link>
      <router-link to="/combat" class="action-card">
        <div class="action-icon">⚔️</div>
        <span>Combat</span>
      </router-link>
      <router-link to="/bank" class="action-card">
        <div class="action-icon">🏦</div>
        <span>Bank</span>
      </router-link>
      <router-link to="/travel" class="action-card">
        <div class="action-icon">✈️</div>
        <span>Travel</span>
      </router-link>
    </div>

    <!-- Feature Categories -->
    <div class="feature-sections">
      <!-- Combat & Activities -->
      <section class="feature-section">
        <h2 class="section-title">⚔️ Combat & Activities</h2>
        <div class="features-grid">
          <router-link to="/gym" class="feature-card">
            <div class="feature-icon">💪</div>
            <h3 class="feature-title">Gym</h3>
            <p class="feature-desc">Train your stats and become stronger</p>
          </router-link>
          <router-link to="/crimes" class="feature-card">
            <div class="feature-icon">🔫</div>
            <h3 class="feature-title">Crimes</h3>
            <p class="feature-desc">Commit crimes for cash and respect</p>
          </router-link>
          <router-link to="/combat" class="feature-card">
            <div class="feature-icon">⚔️</div>
            <h3 class="feature-title">Combat</h3>
            <p class="feature-desc">Battle players and hunt NPCs</p>
          </router-link>
          <router-link to="/organized-crime" class="feature-card">
            <div class="feature-icon">🎭</div>
            <h3 class="feature-title">Organized Crime</h3>
            <p class="feature-desc">Team up with your gang</p>
          </router-link>
          <router-link to="/theft" class="feature-card">
            <div class="feature-icon">🚗</div>
            <h3 class="feature-title">Car Theft</h3>
            <p class="feature-desc">Steal vehicles for profit</p>
          </router-link>
          <router-link to="/racing" class="feature-card">
            <div class="feature-icon">🏎️</div>
            <h3 class="feature-title">Racing</h3>
            <p class="feature-desc">Race for glory and cash</p>
          </router-link>
          <router-link to="/bounty" class="feature-card">
            <div class="feature-icon">🎯</div>
            <h3 class="feature-title">Bounties</h3>
            <p class="feature-desc">Hunt or be hunted</p>
          </router-link>
          <router-link to="/missions" class="feature-card">
            <div class="feature-icon">📜</div>
            <h3 class="feature-title">Missions</h3>
            <p class="feature-desc">Complete tasks for rewards</p>
          </router-link>
        </div>
      </section>

      <!-- Economy & Trading -->
      <section class="feature-section">
        <h2 class="section-title">💰 Economy & Trading</h2>
        <div class="features-grid">
          <router-link to="/bank" class="feature-card">
            <div class="feature-icon">🏦</div>
            <h3 class="feature-title">Bank</h3>
            <p class="feature-desc">Manage your finances safely</p>
          </router-link>
          <router-link to="/shop" class="feature-card">
            <div class="feature-icon">🛒</div>
            <h3 class="feature-title">Shop</h3>
            <p class="feature-desc">Buy weapons and equipment</p>
          </router-link>
          <router-link to="/drugs" class="feature-card">
            <div class="feature-icon">💊</div>
            <h3 class="feature-title">Drugs</h3>
            <p class="feature-desc">Buy low, sell high</p>
          </router-link>
          <router-link to="/stocks" class="feature-card">
            <div class="feature-icon">📈</div>
            <h3 class="feature-title">Stocks</h3>
            <p class="feature-desc">Invest in the market</p>
          </router-link>
          <router-link to="/properties" class="feature-card">
            <div class="feature-icon">🏠</div>
            <h3 class="feature-title">Properties</h3>
            <p class="feature-desc">Own real estate</p>
          </router-link>
          <router-link to="/casino" class="feature-card">
            <div class="feature-icon">🎰</div>
            <h3 class="feature-title">Casino</h3>
            <p class="feature-desc">Try your luck</p>
          </router-link>
          <router-link to="/employment" class="feature-card">
            <div class="feature-icon">💼</div>
            <h3 class="feature-title">Jobs</h3>
            <p class="feature-desc">Earn a legitimate income</p>
          </router-link>
          <router-link to="/bullets" class="feature-card">
            <div class="feature-icon">🔫</div>
            <h3 class="feature-title">Ammo</h3>
            <p class="feature-desc">Stock up on bullets</p>
          </router-link>
        </div>
      </section>

      <!-- Social & Community -->
      <section class="feature-section">
        <h2 class="section-title">👥 Social & Community</h2>
        <div class="features-grid">
          <router-link to="/gang" class="feature-card">
            <div class="feature-icon">👥</div>
            <h3 class="feature-title">Gang</h3>
            <p class="feature-desc">Join or create a gang</p>
          </router-link>
          <router-link to="/forums" class="feature-card">
            <div class="feature-icon">💬</div>
            <h3 class="feature-title">Forums</h3>
            <p class="feature-desc">Discuss with the community</p>
          </router-link>
          <router-link to="/chat" class="feature-card">
            <div class="feature-icon">🗨️</div>
            <h3 class="feature-title">Chat</h3>
            <p class="feature-desc">Real-time chat</p>
          </router-link>
          <router-link to="/leaderboards" class="feature-card">
            <div class="feature-icon">🏆</div>
            <h3 class="feature-title">Leaderboards</h3>
            <p class="feature-desc">See top players</p>
          </router-link>
          <router-link to="/achievements" class="feature-card">
            <div class="feature-icon">🎖️</div>
            <h3 class="feature-title">Achievements</h3>
            <p class="feature-desc">Track your progress</p>
          </router-link>
          <router-link to="/profile" class="feature-card">
            <div class="feature-icon">👤</div>
            <h3 class="feature-title">Profile</h3>
            <p class="feature-desc">View your profile</p>
          </router-link>
          <router-link to="/tickets" class="feature-card">
            <div class="feature-icon">🎫</div>
            <h3 class="feature-title">Support</h3>
            <p class="feature-desc">Get help from staff</p>
          </router-link>
          <router-link to="/wiki" class="feature-card">
            <div class="feature-icon">📚</div>
            <h3 class="feature-title">Wiki</h3>
            <p class="feature-desc">Game guides and info</p>
          </router-link>
        </div>
      </section>

      <!-- Utilities -->
      <section class="feature-section">
        <h2 class="section-title">🔧 Utilities</h2>
        <div class="features-grid">
          <router-link to="/hospital" class="feature-card">
            <div class="feature-icon">🏥</div>
            <h3 class="feature-title">Hospital</h3>
            <p class="feature-desc">Heal your wounds</p>
          </router-link>
          <router-link to="/travel" class="feature-card">
            <div class="feature-icon">✈️</div>
            <h3 class="feature-title">Travel</h3>
            <p class="feature-desc">Visit other cities</p>
          </router-link>
          <router-link to="/jail" class="feature-card">
            <div class="feature-icon">⛓️</div>
            <h3 class="feature-title">Jail</h3>
            <p class="feature-desc">Break out or bail out</p>
          </router-link>
          <router-link to="/detective" class="feature-card">
            <div class="feature-icon">🕵️</div>
            <h3 class="feature-title">Detective</h3>
            <p class="feature-desc">Hire a private eye</p>
          </router-link>
          <router-link to="/inventory" class="feature-card">
            <div class="feature-icon">🎒</div>
            <h3 class="feature-title">Inventory</h3>
            <p class="feature-desc">Manage your items</p>
          </router-link>
          <router-link to="/education" class="feature-card">
            <div class="feature-icon">🎓</div>
            <h3 class="feature-title">Education</h3>
            <p class="feature-desc">Learn new skills</p>
          </router-link>
          <router-link to="/activity" class="feature-card">
            <div class="feature-icon">📊</div>
            <h3 class="feature-title">Activity</h3>
            <p class="feature-desc">View your history</p>
          </router-link>
          <router-link to="/announcements" class="feature-card">
            <div class="feature-icon">📢</div>
            <h3 class="feature-title">News</h3>
            <p class="feature-desc">Latest announcements</p>
          </router-link>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePlayerStore } from '@/stores/player'

const playerStore = usePlayerStore()

const currentRank = computed(() => playerStore.currentRank)
const currentLocation = computed(() => playerStore.currentLocation)
const totalPower = computed(() => playerStore.strength + playerStore.defense + playerStore.speed + playerStore.endurance)

const formatMoney = (val) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0
  }).format(val)
}
</script>

<style scoped>
.home-view {
  max-width: 1400px;
  margin: 0 auto;
}

/* Welcome Banner */
.welcome-banner {
  background: linear-gradient(135deg, rgba(0, 188, 212, 0.2) 0%, rgba(6, 95, 134, 0.2) 100%);
  border: 1px solid rgba(0, 188, 212, 0.3);
  border-radius: 1rem;
  padding: 1.5rem 2rem;
  margin-bottom: 2rem;
}

.welcome-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.welcome-text h1 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #f9fafb;
  margin: 0 0 0.25rem;
}

.username {
  color: #00bcd4;
}

.welcome-text p {
  color: #9ca3af;
  margin: 0;
  font-size: 0.875rem;
}

.daily-reward-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
  color: white;
  text-decoration: none;
  padding: 0.75rem 1.25rem;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.daily-reward-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(168, 85, 247, 0.4);
}

.btn-icon {
  font-size: 1.25rem;
}

/* Quick Stats */
.quick-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: rgba(31, 41, 55, 0.5);
  border: 1px solid rgba(75, 85, 99, 0.3);
  border-radius: 0.75rem;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  position: relative;
  overflow: hidden;
}

.stat-icon {
  font-size: 2rem;
  width: 3rem;
  height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 0.5rem;
}

.stat-details {
  flex: 1;
}

.stat-label {
  display: block;
  font-size: 0.75rem;
  color: #9ca3af;
  margin-bottom: 0.125rem;
}

.stat-value {
  font-size: 1.125rem;
  font-weight: 700;
  color: #f9fafb;
}

.stat-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: rgba(0, 0, 0, 0.3);
}

.bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #00bcd4, #0891b2);
  transition: width 0.3s;
}

/* Quick Actions */
.quick-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 2.5rem;
}

.action-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 0.75rem 1.25rem;
  background: rgba(31, 41, 55, 0.5);
  border: 1px solid rgba(75, 85, 99, 0.3);
  border-radius: 0.5rem;
  text-decoration: none;
  color: #d1d5db;
  font-size: 0.75rem;
  font-weight: 600;
  transition: all 0.2s;
}

.action-card:hover {
  background: rgba(0, 188, 212, 0.1);
  border-color: rgba(0, 188, 212, 0.3);
  color: #00bcd4;
  transform: translateY(-2px);
}

.action-icon {
  font-size: 1.5rem;
}

/* Section Title */
.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #f9fafb;
  margin: 0 0 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid rgba(75, 85, 99, 0.3);
}

/* Feature Sections */
.feature-sections {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.feature-section {
  background: rgba(31, 41, 55, 0.3);
  border-radius: 1rem;
  padding: 1.5rem;
  border: 1px solid rgba(75, 85, 99, 0.2);
}

/* Features Grid */
.features-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.feature-card {
  background: rgba(17, 24, 39, 0.5);
  border: 1px solid rgba(75, 85, 99, 0.3);
  border-radius: 0.5rem;
  padding: 1.25rem 1rem;
  text-decoration: none;
  color: #e2e8f0;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.feature-card:hover {
  transform: translateY(-4px);
  border-color: rgba(0, 188, 212, 0.5);
  background: rgba(0, 188, 212, 0.1);
  box-shadow: 0 8px 20px rgba(0, 188, 212, 0.2);
}

.feature-icon {
  font-size: 2rem;
  line-height: 1;
}

.feature-title {
  font-size: 0.9375rem;
  font-weight: 600;
  margin: 0;
  color: #ffffff;
}

.feature-desc {
  font-size: 0.75rem;
  color: #9ca3af;
  margin: 0;
  line-height: 1.4;
}

/* Responsive */
@media (max-width: 1200px) {
  .features-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 1024px) {
  .quick-stats {
    grid-template-columns: repeat(2, 1fr);
  }

  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .welcome-content {
    flex-direction: column;
    text-align: center;
  }

  .quick-stats {
    grid-template-columns: 1fr;
  }

  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .quick-actions {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .features-grid {
    grid-template-columns: 1fr;
  }
}
</style>
