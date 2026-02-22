<template>
  <div class="combat-view">
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      {{ error }}
    </div>

    <!-- Combat Screen (Active Fight) -->
    <div v-else-if="activeFight" class="combat-screen">
      <!-- Fight Timer -->
      <div class="fight-timer">
        FIGHT EXPIRE: {{ fightTimer }}
      </div>

      <div class="combat-arena">
        <!-- Player Panel -->
        <div class="player-panel">
          <div class="fighter-header">
            <div class="fighter-avatar">LF</div>
            <div class="fighter-info">
              <h3 class="fighter-name">{{ player.username || 'Player' }}</h3>
              <div class="health-bar-container">
                <div class="health-bar">
                  <div class="health-fill" :style="{ width: getHealthPercentage(player.health, player.max_health) + '%' }"></div>
                </div>
                <span class="health-text">{{ player.health }}/{{ player.max_health }}</span>
              </div>
            </div>
          </div>

          <!-- Equipment Slots -->
          <div class="equipment-slots">
            <div v-for="item in playerEquipment" :key="item.slot" class="equip-slot">
              <div class="item-icon">{{ item.icon }}</div>
              <div class="item-durability" :class="getDurabilityClass(item.durability)">
                {{ item.durability }}%
              </div>
            </div>
          </div>
        </div>

        <!-- Enemy Panel -->
        <div class="enemy-panel">
          <div class="fighter-header">
            <div class="fighter-avatar">👤</div>
            <div class="fighter-info">
              <div class="enemy-title">
                <h3 class="fighter-name">{{ activeFight.enemy.name }}</h3>
                <span class="enemy-level">LEVEL {{ activeFight.enemy.level }}</span>
              </div>
              <div class="health-bar-container">
                <div class="health-bar">
                  <div class="health-fill enemy" :style="{ width: getHealthPercentage(activeFight.enemy.health, activeFight.enemy.max_health) + '%' }"></div>
                </div>
                <span class="health-text">{{ activeFight.enemy.health }}/{{ activeFight.enemy.max_health }}</span>
              </div>
            </div>
          </div>

          <!-- Enemy Stats -->
          <div class="enemy-stats">
            <div class="difficulty-skulls">
              <span v-for="n in 5" :key="n" class="skull" :class="{ active: n <= activeFight.enemy.difficulty }">💀</span>
            </div>
            <div class="weakness-info">Weakness: {{ activeFight.enemy.weakness }}</div>
            <div class="stats-grid">
              <div class="stat-row">
                <span class="stat-label">STRENGTH</span>
                <span class="stat-value">{{ formatNumber(activeFight.enemy.strength) }}</span>
              </div>
              <div class="stat-row">
                <span class="stat-label">DEFENSE</span>
                <span class="stat-value">{{ formatNumber(activeFight.enemy.defense) }}</span>
              </div>
              <div class="stat-row">
                <span class="stat-label">SPEED</span>
                <span class="stat-value">{{ formatNumber(activeFight.enemy.speed) }}</span>
              </div>
              <div class="stat-row">
                <span class="stat-label">AGILITY</span>
                <span class="stat-value">{{ formatNumber(activeFight.enemy.agility) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Weapons Section -->
      <div class="weapons-section">
        <div v-for="weapon in weapons" :key="weapon.id" class="weapon-card">
          <div class="weapon-icon">{{ weapon.icon }}</div>
          <div class="weapon-info">
            <h4 class="weapon-name">{{ weapon.name }}</h4>
            <div class="weapon-stats">
              <span class="weapon-stat">🎯 {{ weapon.damage }}</span>
              <span class="weapon-stat">🔫 {{ weapon.accuracy }}</span>
              <span v-if="weapon.ammo !== undefined" class="weapon-stat">Ammo: {{ weapon.ammo.current }}/{{ weapon.ammo.max }}</span>
            </div>
            <div v-if="weapon.durability" class="weapon-durability">
              {{ weapon.durability }}%
            </div>
          </div>
          <button
            @click="attackWithWeapon(weapon)"
            :disabled="processing || weapon.ammo?.current === 0"
            class="attack-btn"
            :class="{ 'out-of-ammo': weapon.ammo?.current === 0 }"
          >
            {{ weapon.ammo?.current === 0 ? 'OUT OF AMMO' : 'ATTACK' }}
          </button>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button @click="autoAttack" :disabled="processing" class="auto-attack-btn">AUTO ATTACK</button>
        <button @click="runAway" :disabled="processing" class="run-away-btn">RUN AWAY</button>
      </div>

      <!-- Fight Log -->
      <div class="fight-log">
        <h3 class="log-title">FIGHT LOG</h3>
        <div class="log-messages">
          <div v-for="(log, index) in fightLogs" :key="index" class="log-message" :class="log.type">
            {{ log.message }}
          </div>
        </div>
      </div>
    </div>

    <!-- Area Selection Screen -->
    <div v-else-if="selectedLocation" class="area-selection">
      <!-- Back Button -->
      <button @click="backToLocations" class="back-btn">
        <span>←</span> GO BACK
      </button>

      <!-- Location Header -->
      <div class="location-header-card">
        <h2 class="location-title">{{ selectedLocation.name?.toUpperCase() }}</h2>
        <div class="location-energy">
          <span class="energy-icon">⚡</span>
          <span class="energy-value">{{ selectedLocation.energy_cost || 20 }}</span>
        </div>
      </div>

      <!-- Areas Grid -->
      <div class="areas-grid">
        <div v-for="area in selectedLocation.areas" :key="area.id" class="area-card">
          <div class="area-content">
            <h3 class="area-name">{{ area.name?.toUpperCase() }}</h3>
            <div class="difficulty-skulls">
              <span v-for="n in 5" :key="n" class="skull" :class="{ active: n <= area.difficulty }">💀</span>
            </div>
          </div>
          <button @click="startHunt(area)" class="hunt-btn">HUNT</button>
        </div>
      </div>
    </div>

    <!-- Location Selection Screen -->
    <div v-else class="location-selection">
      <div class="selection-header">
        <h1>SELECT A LOCATION TO CONTINUE</h1>
      </div>

      <div class="locations-grid">
        <div
          v-for="location in locations"
          :key="location.id"
          class="location-card"
          @click="selectLocation(location)"
        >
          <div class="location-overlay"></div>
          <div class="location-content">
            <h3 class="location-name">{{ location.name?.toUpperCase() }}</h3>
            <div class="location-energy">
              <span class="energy-icon">⚡</span>
              <span class="energy-value">{{ location.energy_cost || 20 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import api from '@/services/api';

const loading = ref(true);
const processing = ref(false);
const error = ref(null);

const locations = ref([]);
const selectedLocation = ref(null);
const activeFight = ref(null);
const player = ref({});
const weapons = ref([]);
const playerEquipment = ref([]);
const fightLogs = ref([]);
const fightTimer = ref('09:50');
let timerInterval = null;

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US').format(num);
};

const getHealthPercentage = (health, maxHealth) => {
  return Math.round((health / maxHealth) * 100);
};

const getDurabilityClass = (durability) => {
  if (durability > 70) return 'high';
  if (durability > 30) return 'medium';
  return 'low';
};

const loadCombatData = async () => {
  try {
    loading.value = true;
    const response = await api.get('/combat/locations');
    locations.value = response.data.locations || [];
    player.value = response.data.player || {};
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load combat data';
  } finally {
    loading.value = false;
  }
};

const selectLocation = (location) => {
  selectedLocation.value = location;
};

const backToLocations = () => {
  selectedLocation.value = null;
  activeFight.value = null;
};

const startHunt = async (area) => {
  try {
    processing.value = true;
    const response = await api.post(`/combat/hunt`, {
      location_id: selectedLocation.value.id,
      area_id: area.id
    });

    // Initialize fight
    activeFight.value = {
      fight_id: response.data.fight_id,
      enemy: response.data.enemy,
      area: area
    };

    // Setup weapons
    weapons.value = response.data.weapons || [];
    playerEquipment.value = response.data.equipment || [];
    fightLogs.value = [];

    // Start fight timer
    startFightTimer();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to start hunt');
  } finally {
    processing.value = false;
  }
};

const attackWithWeapon = async (weapon) => {
  if (processing.value) return;

  try {
    processing.value = true;
    const response = await api.post('/combat/attack-npc', {
      fight_id: activeFight.value.fight_id,
      weapon_id: weapon.id
    });

    // Update fight state
    activeFight.value.enemy.health = response.data.enemy.health;
    player.value.health = response.data.player.health;

    // Add to fight log
    fightLogs.value.unshift({
      type: response.data.result,
      message: response.data.message
    });

    // Check if fight ended
    if (response.data.ended) {
      stopFightTimer();
      alert(response.data.end_message);
      backToLocations();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Attack failed');
  } finally {
    processing.value = false;
  }
};

const autoAttack = async () => {
  if (processing.value) return;

  try {
    processing.value = true;
    const response = await api.post('/combat/auto-attack-npc', {
      fight_id: activeFight.value.fight_id
    });

    // Update fight state
    activeFight.value.enemy.health = response.data.enemy.health;
    player.value.health = response.data.player.health;

    // Add logs
    response.data.logs.forEach(log => {
      fightLogs.value.unshift(log);
    });

    if (response.data.ended) {
      stopFightTimer();
      alert(response.data.end_message);
      backToLocations();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Auto attack failed');
  } finally {
    processing.value = false;
  }
};

const runAway = () => {
  if (confirm('Are you sure you want to run away?')) {
    stopFightTimer();
    backToLocations();
  }
};

const startFightTimer = () => {
  let seconds = 590; // 9:50
  timerInterval = setInterval(() => {
    seconds--;
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    fightTimer.value = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;

    if (seconds <= 0) {
      stopFightTimer();
      alert('Fight expired! You ran away.');
      backToLocations();
    }
  }, 1000);
};

const stopFightTimer = () => {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
};

onMounted(() => {
  loadCombatData();
});

onUnmounted(() => {
  stopFightTimer();
});
</script>

<style scoped>
.combat-view {
  min-height: 100vh;
  padding: 2rem;
  max-width: 1600px;
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

/* Error */
.error-message {
  background: rgba(220, 38, 38, 0.2);
  border: 1px solid rgba(220, 38, 38, 0.5);
  color: #fca5a5;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

/* SCREEN 1: Location Selection */
.selection-header {
  text-align: center;
  margin-bottom: 2rem;
}

.selection-header h1 {
  color: #e2e8f0;
  font-size: 1.5rem;
  font-weight: 400;
  letter-spacing: 1px;
  margin: 0;
}

.locations-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.location-card {
  position: relative;
  height: 120px;
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(51, 65, 85, 0.6));
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s;
}

.location-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
  border-color: rgba(255, 255, 255, 0.2);
}

.location-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: 1;
}

.location-content {
  position: relative;
  z-index: 2;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem 2rem;
}

.location-name {
  color: #e2e8f0;
  font-size: 1.5rem;
  font-weight: 600;
  letter-spacing: 1px;
  margin: 0;
}

.location-energy {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(0, 0, 0, 0.5);
  padding: 0.5rem 1rem;
  border-radius: 6px;
}

.energy-icon {
  font-size: 1.2rem;
}

.energy-value {
  color: #fbbf24;
  font-size: 1.25rem;
  font-weight: 700;
}

/* SCREEN 2: Area Selection */
.back-btn {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  color: #e2e8f0;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 1.5rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.back-btn:hover {
  background: rgba(30, 41, 59, 0.8);
  border-color: rgba(255, 255, 255, 0.2);
}

.location-header-card {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1.5rem 2rem;
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.location-title {
  color: #e2e8f0;
  font-size: 1.75rem;
  font-weight: 600;
  letter-spacing: 1px;
  margin: 0;
}

.areas-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.area-card {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: all 0.2s;
}

.area-card:hover {
  background: rgba(30, 41, 59, 0.7);
  border-color: rgba(255, 255, 255, 0.2);
}

.area-content {
  flex: 1;
}

.area-name {
  color: #e2e8f0;
  font-size: 1.15rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  margin: 0 0 0.75rem 0;
}

.difficulty-skulls {
  display: flex;
  gap: 0.25rem;
}

.skull {
  font-size: 1rem;
  filter: grayscale(1);
  opacity: 0.3;
}

.skull.active {
  filter: grayscale(0);
  opacity: 1;
}

.hunt-btn {
  padding: 0.75rem 2rem;
  background: #4ade80;
  border: none;
  border-radius: 6px;
  color: #064e3b;
  font-weight: 700;
  font-size: 1rem;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.2s;
  margin-left: 1.5rem;
}

.hunt-btn:hover {
  background: #22c55e;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(74, 222, 128, 0.4);
}

/* SCREEN 3: Combat Screen */
.fight-timer {
  text-align: right;
  color: #cbd5e1;
  font-size: 0.95rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  margin-bottom: 1.5rem;
}

.combat-arena {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.player-panel,
.enemy-panel {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1.5rem;
}

.fighter-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.fighter-avatar {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  font-weight: 700;
  flex-shrink: 0;
}

.fighter-info {
  flex: 1;
  min-width: 0;
}

.fighter-name {
  color: #e2e8f0;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.enemy-title {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.enemy-level {
  padding: 0.25rem 0.75rem;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 4px;
  color: #94a3b8;
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.health-bar-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.health-bar {
  flex: 1;
  height: 8px;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 4px;
  overflow: hidden;
}

.health-fill {
  height: 100%;
  background: #ef4444;
  border-radius: 4px;
  transition: width 0.3s;
}

.health-fill.enemy {
  background: #ef4444;
}

.health-text {
  color: #cbd5e1;
  font-size: 0.9rem;
  font-weight: 600;
  white-space: nowrap;
}

.equipment-slots {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.75rem;
}

.equip-slot {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  padding: 0.75rem;
  text-align: center;
}

.item-icon {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.item-durability {
  font-size: 0.85rem;
  font-weight: 600;
}

.item-durability.high {
  color: #4ade80;
}

.item-durability.medium {
  color: #fbbf24;
}

.item-durability.low {
  color: #ef4444;
}

.enemy-stats {
  margin-top: 1rem;
}

.weakness-info {
  color: #94a3b8;
  font-size: 0.9rem;
  margin: 0.75rem 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.stat-row {
  background: rgba(0, 0, 0, 0.3);
  border-radius: 4px;
  padding: 0.5rem 0.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-label {
  color: #94a3b8;
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.stat-value {
  color: #e2e8f0;
  font-weight: 700;
}

.weapons-section {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.weapon-card {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1.5rem;
  text-align: center;
}

.weapon-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.weapon-name {
  color: #e2e8f0;
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 0.75rem 0;
}

.weapon-stats {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.weapon-stat {
  color: #94a3b8;
  font-size: 0.9rem;
}

.weapon-durability {
  color: #fbbf24;
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.attack-btn {
  width: 100%;
  padding: 0.75rem;
  background: #0891b2;
  border: none;
  border-radius: 6px;
  color: white;
  font-weight: 700;
  font-size: 0.95rem;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.2s;
}

.attack-btn:hover:not(:disabled) {
  background: #06b6d4;
  transform: translateY(-2px);
}

.attack-btn:disabled {
  background: #374151;
  color: #6b7280;
  cursor: not-allowed;
}

.attack-btn.out-of-ammo {
  background: rgba(100, 116, 139, 0.5);
}

.action-buttons {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.auto-attack-btn,
.run-away-btn {
  padding: 0.75rem 2rem;
  border: none;
  border-radius: 6px;
  font-weight: 700;
  font-size: 1rem;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.2s;
}

.auto-attack-btn {
  background: #0891b2;
  color: white;
}

.auto-attack-btn:hover:not(:disabled) {
  background: #06b6d4;
  transform: translateY(-2px);
}

.run-away-btn {
  background: #ef4444;
  color: white;
}

.run-away-btn:hover:not(:disabled) {
  background: #dc2626;
  transform: translateY(-2px);
}

.fight-log {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1.5rem;
}

.log-title {
  color: #e2e8f0;
  font-size: 1.1rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  margin: 0 0 1rem 0;
}

.log-messages {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: 300px;
  overflow-y: auto;
}

.log-message {
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.95rem;
  line-height: 1.5;
}

.log-message.enemy {
  background: rgba(220, 38, 38, 0.2);
  border-left: 3px solid #ef4444;
  color: #fca5a5;
}

.log-message.player {
  background: rgba(34, 197, 94, 0.2);
  border-left: 3px solid #22c55e;
  color: #86efac;
}

.log-message.miss {
  background: rgba(100, 116, 139, 0.2);
  border-left: 3px solid #64748b;
  color: #cbd5e1;
}

/* Responsive */
@media (max-width: 1024px) {
  .combat-arena {
    grid-template-columns: 1fr;
  }

  .weapons-section {
    grid-template-columns: 1fr;
  }

  .locations-grid,
  .areas-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .combat-view {
    padding: 1rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .equipment-slots {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
