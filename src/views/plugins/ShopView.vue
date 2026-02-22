<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();

const loading = ref(true);
const error = ref(null);
const processing = ref(false);

const items = ref([]);
const player = ref(null);
const selectedFilter = ref('all');
const flashMessage = ref(null);
const flashType = ref('success');

// Get unique item types for filtering
const itemTypes = computed(() => {
  const types = new Set(items.value.map(item => item.type));
  return ['all', ...Array.from(types)];
});

// Filter items by type
const filteredItems = computed(() => {
  if (selectedFilter.value === 'all') {
    return items.value;
  }
  return items.value.filter(item => item.type === selectedFilter.value);
});

// Format numbers with commas
const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US').format(num);
};

// Get rarity color class
const getRarityColor = (rarity) => {
  const colors = {
    common: 'rarity-common',
    uncommon: 'rarity-uncommon',
    rare: 'rarity-rare',
    epic: 'rarity-epic',
    legendary: 'rarity-legendary'
  };
  return colors[rarity] || 'rarity-common';
};

// Get type icon
const getTypeIcon = (type) => {
  const icons = {
    weapon: '⚔️',
    armor: '🛡️',
    vehicle: '🚗',
    consumable: '💊',
    ammo: '🔫',
    tool: '🔧',
    special: '✨'
  };
  return icons[type] || '📦';
};

// Check if player can afford item
const canAfford = (item) => {
  return player.value && player.value.cash >= item.price;
};

// Check if player meets level requirement
const meetsLevel = (item) => {
  if (!item.requirements?.level) return true;
  return player.value && player.value.level >= item.requirements.level;
};

// Load shop data
const loadShop = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await api.get('/api/v1/inventory/shop');

    items.value = response.data.items || [];
    player.value = response.data.player || null;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load shop';
    console.error('Error loading shop:', err);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadShop();
});

// Show flash message
const showFlash = (message, type = 'success') => {
  flashMessage.value = message;
  flashType.value = type;
  setTimeout(() => {
    flashMessage.value = null;
  }, 5000);
};

// Buy item
const buyItem = async (item) => {
  if (processing.value) return;

  if (!canAfford(item)) {
    showFlash(`You don't have enough cash! Need $${formatNumber(item.price)}`, 'error');
    return;
  }

  if (!meetsLevel(item)) {
    showFlash(`You need to be level ${item.requirements.level} to buy this!`, 'error');
    return;
  }

  processing.value = true;

  try {
    const response = await api.post(`/api/v1/inventory/buy/${item.id}`);
    showFlash(response.data.message || 'Item purchased successfully!', 'success');

    // Update player data from response
    if (response.data.player) {
      player.value = response.data.player;
    } else {
      // Reload to get updated cash
      await loadShop();
    }
  } catch (err) {
    const message = err.response?.data?.message || 'Failed to buy item';
    showFlash(message, 'error');
  } finally {
    processing.value = false;
  }
};

const goToDashboard = () => {
  router.push('/dashboard');
};

const goToInventory = () => {
  router.push('/inventory');
};
</script>

<template>
  <div class="shop-view">
    <!-- Header -->
    <div class="page-header">
      <h1 class="page-title">🛒 Shop</h1>
      <div class="header-actions">
        <button @click="goToInventory" class="inventory-button">
          💼 Inventory
        </button>
        <button @click="goToDashboard" class="back-button">
          ← Dashboard
        </button>
      </div>
    </div>

    <!-- Player Cash Display -->
    <div v-if="player" class="player-cash">
      <span class="cash-label">Your Cash:</span>
      <span class="cash-amount">${{ formatNumber(player.cash) }}</span>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading shop...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">⚠️</div>
      <p>{{ error }}</p>
      <button @click="loadShop" class="retry-button">Retry</button>
    </div>

    <!-- Main Content -->
    <div v-else class="content">
      <!-- Flash Messages -->
      <div v-if="flashMessage" class="flash-message" :class="flashType">
        {{ flashMessage }}
      </div>

      <!-- Filter Tabs -->
      <div class="filter-tabs">
        <button
          v-for="type in itemTypes"
          :key="type"
          @click="selectedFilter = type"
          class="filter-tab"
          :class="{ active: selectedFilter === type }"
        >
          {{ type === 'all' ? '📦 All' : `${getTypeIcon(type)} ${type.charAt(0).toUpperCase() + type.slice(1)}` }}
        </button>
      </div>

      <!-- Items Grid -->
      <div v-if="filteredItems.length > 0" class="items-grid">
        <div
          v-for="item in filteredItems"
          :key="item.id"
          class="item-card"
          :class="[getRarityColor(item.rarity), { 'cannot-afford': !canAfford(item), 'level-locked': !meetsLevel(item) }]"
        >
          <!-- Item Header -->
          <div class="item-header">
            <span class="item-icon">{{ getTypeIcon(item.type) }}</span>
            <span class="item-name">{{ item.name }}</span>
            <span class="item-rarity">{{ item.rarity }}</span>
          </div>

          <!-- Item Description -->
          <p class="item-description">{{ item.description }}</p>

          <!-- Item Stats -->
          <div v-if="item.stats && Object.keys(item.stats).length > 0" class="item-stats">
            <div v-for="(value, stat) in item.stats" :key="stat" class="stat">
              <span class="stat-name">{{ stat }}:</span>
              <span class="stat-value">+{{ value }}</span>
            </div>
          </div>

          <!-- Requirements -->
          <div v-if="item.requirements?.level" class="item-requirements">
            <span :class="{ 'unmet': !meetsLevel(item) }">
              Required Level: {{ item.requirements.level }}
            </span>
          </div>

          <!-- Price and Buy -->
          <div class="item-footer">
            <div class="item-price">
              <span class="price-label">Price:</span>
              <span class="price-value" :class="{ 'too-expensive': !canAfford(item) }">
                ${{ formatNumber(item.price) }}
              </span>
            </div>
            <button
              @click="buyItem(item)"
              :disabled="processing || !canAfford(item) || !meetsLevel(item)"
              class="buy-button"
            >
              {{ processing ? '...' : 'Buy' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <div class="empty-icon">🏪</div>
        <p>No items available in this category.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.shop-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  color: #e0e0e0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 15px;
}

.page-title {
  font-size: 2rem;
  margin: 0;
  color: #ffd700;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.back-button,
.inventory-button {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.back-button {
  background: #333;
  color: #fff;
}

.back-button:hover {
  background: #444;
}

.inventory-button {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: white;
}

.inventory-button:hover {
  background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
}

.player-cash {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 1px solid #2d4a3e;
  border-radius: 12px;
  padding: 15px 20px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.cash-label {
  color: #888;
  font-size: 1rem;
}

.cash-amount {
  color: #4ade80;
  font-size: 1.5rem;
  font-weight: bold;
}

.loading-state,
.error-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #333;
  border-top-color: #ffd700;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-icon {
  font-size: 3rem;
  margin-bottom: 15px;
}

.retry-button {
  padding: 10px 30px;
  background: #ffd700;
  color: #1a1a2e;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  margin-top: 15px;
}

.flash-message {
  padding: 15px 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-weight: 500;
}

.flash-message.success {
  background: rgba(74, 222, 128, 0.2);
  border: 1px solid #4ade80;
  color: #4ade80;
}

.flash-message.error {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid #ef4444;
  color: #ef4444;
}

.filter-tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.filter-tab {
  padding: 10px 20px;
  background: #2a2a3e;
  border: 1px solid #3a3a4e;
  border-radius: 8px;
  color: #888;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: capitalize;
}

.filter-tab:hover {
  background: #3a3a4e;
  color: #fff;
}

.filter-tab.active {
  background: #ffd700;
  color: #1a1a2e;
  border-color: #ffd700;
}

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.item-card {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border: 2px solid #3a3a4e;
  border-radius: 12px;
  padding: 20px;
  transition: all 0.3s ease;
}

.item-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.item-card.cannot-afford {
  opacity: 0.7;
}

.item-card.level-locked {
  opacity: 0.6;
  border-color: #ef4444;
}

/* Rarity colors */
.rarity-common {
  border-color: #9ca3af;
}

.rarity-uncommon {
  border-color: #22c55e;
}

.rarity-rare {
  border-color: #3b82f6;
}

.rarity-epic {
  border-color: #a855f7;
}

.rarity-legendary {
  border-color: #f59e0b;
  background: linear-gradient(135deg, #1a1a2e 0%, #2d2710 100%);
}

.item-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.item-icon {
  font-size: 1.5rem;
}

.item-name {
  font-size: 1.1rem;
  font-weight: bold;
  color: #fff;
  flex: 1;
}

.item-rarity {
  font-size: 0.75rem;
  padding: 3px 8px;
  border-radius: 4px;
  text-transform: uppercase;
  background: rgba(255, 255, 255, 0.1);
}

.item-description {
  color: #888;
  font-size: 0.9rem;
  margin-bottom: 15px;
  line-height: 1.4;
}

.item-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 15px;
}

.stat {
  background: rgba(255, 255, 255, 0.05);
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 0.85rem;
}

.stat-name {
  color: #888;
  text-transform: capitalize;
}

.stat-value {
  color: #4ade80;
  font-weight: bold;
  margin-left: 5px;
}

.item-requirements {
  margin-bottom: 15px;
  font-size: 0.85rem;
  color: #888;
}

.item-requirements .unmet {
  color: #ef4444;
  font-weight: bold;
}

.item-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #3a3a4e;
  padding-top: 15px;
}

.item-price {
  display: flex;
  flex-direction: column;
}

.price-label {
  font-size: 0.75rem;
  color: #888;
}

.price-value {
  font-size: 1.2rem;
  font-weight: bold;
  color: #ffd700;
}

.price-value.too-expensive {
  color: #ef4444;
}

.buy-button {
  padding: 10px 25px;
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  transition: all 0.3s ease;
}

.buy-button:hover:not(:disabled) {
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  transform: scale(1.05);
}

.buy-button:disabled {
  background: #4a4a5e;
  cursor: not-allowed;
  opacity: 0.6;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .filter-tabs {
    justify-content: flex-start;
  }

  .items-grid {
    grid-template-columns: 1fr;
  }
}
</style>
