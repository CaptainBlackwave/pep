<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '@/services/api';

const listings = ref([]);
const myListings = ref([]);
const categories = ref([]);
const player = ref(null);
const loading = ref(true);
const processing = ref(false);
const error = ref('');
const successMessage = ref('');

const activeTab = ref('browse');
const selectedCategory = ref('all');
const searchQuery = ref('');
const sortBy = ref('newest');

// Create Listing Form
const showCreateForm = ref(false);
const newListing = ref({
  item_id: null,
  quantity: 1,
  price_per_unit: 0
});
const myInventory = ref([]);

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
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const filteredListings = computed(() => {
  let filtered = [...listings.value];

  // Category filter
  if (selectedCategory.value !== 'all') {
    filtered = filtered.filter(l => l.category === selectedCategory.value);
  }

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(l =>
      l.item_name.toLowerCase().includes(query) ||
      l.seller_name.toLowerCase().includes(query)
    );
  }

  // Sort
  switch (sortBy.value) {
    case 'price_asc':
      filtered.sort((a, b) => a.price_per_unit - b.price_per_unit);
      break;
    case 'price_desc':
      filtered.sort((a, b) => b.price_per_unit - a.price_per_unit);
      break;
    case 'newest':
    default:
      filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      break;
  }

  return filtered;
});

const totalListingPrice = computed(() => {
  return newListing.value.quantity * newListing.value.price_per_unit;
});

const fetchData = async () => {
  try {
    loading.value = true;
    error.value = '';
    const response = await api.get('/api/v1/market');
    listings.value = response.data.listings || [];
    myListings.value = response.data.myListings || [];
    categories.value = response.data.categories || [];
    player.value = response.data.player || null;
    myInventory.value = response.data.inventory || [];
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load market data';
  } finally {
    loading.value = false;
  }
};

const buyItem = async (listingId, quantity = 1) => {
  if (processing.value) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post(`/api/v1/market/${listingId}/buy`, { quantity });

    successMessage.value = response.data.message || 'Purchase successful!';
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to purchase item';
  } finally {
    processing.value = false;
  }
};

const createListing = async () => {
  if (processing.value || !newListing.value.item_id) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post('/api/v1/market/create', {
      item_id: newListing.value.item_id,
      quantity: newListing.value.quantity,
      price_per_unit: newListing.value.price_per_unit
    });

    successMessage.value = response.data.message || 'Listing created!';
    showCreateForm.value = false;
    newListing.value = { item_id: null, quantity: 1, price_per_unit: 0 };
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to create listing';
  } finally {
    processing.value = false;
  }
};

const cancelListing = async (listingId) => {
  if (processing.value || !confirm('Cancel this listing? Items will be returned to your inventory.')) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post(`/api/v1/market/${listingId}/cancel`);

    successMessage.value = response.data.message || 'Listing cancelled';
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to cancel listing';
  } finally {
    processing.value = false;
  }
};

const selectInventoryItem = (item) => {
  newListing.value.item_id = item.id;
  newListing.value.quantity = 1;
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="market-view">
    <div class="page-header">
      <h1>🏪 Player Market</h1>
      <p class="subtitle">Buy and sell items with other players</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading market...</p>
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
    <div v-if="!loading" class="market-content">

      <!-- Player Balance -->
      <div class="balance-card">
        <span class="balance-label">Your Balance:</span>
        <span class="balance-amount">{{ formatMoney(player?.cash || 0) }}</span>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button
          :class="['tab', { active: activeTab === 'browse' }]"
          @click="activeTab = 'browse'"
        >
          Browse Market
        </button>
        <button
          :class="['tab', { active: activeTab === 'my-listings' }]"
          @click="activeTab = 'my-listings'"
        >
          My Listings ({{ myListings.length }})
        </button>
        <button
          :class="['tab', { active: activeTab === 'sell' }]"
          @click="activeTab = 'sell'"
        >
          Sell Items
        </button>
      </div>

      <!-- Browse Market -->
      <div v-if="activeTab === 'browse'" class="browse-section">
        <!-- Filters -->
        <div class="filters">
          <div class="filter-group">
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Search items..."
              class="search-input"
            />
          </div>
          <div class="filter-group">
            <select v-model="selectedCategory" class="filter-select">
              <option value="all">All Categories</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>
          <div class="filter-group">
            <select v-model="sortBy" class="filter-select">
              <option value="newest">Newest First</option>
              <option value="price_asc">Price: Low to High</option>
              <option value="price_desc">Price: High to Low</option>
            </select>
          </div>
        </div>

        <!-- Listings Grid -->
        <div v-if="filteredListings.length === 0" class="empty-state">
          <p>No listings found. Be the first to sell something!</p>
        </div>

        <div v-else class="listings-grid">
          <div v-for="listing in filteredListings" :key="listing.id" class="listing-card">
            <div class="listing-image">
              <img v-if="listing.item_image" :src="listing.item_image" :alt="listing.item_name" />
              <span v-else class="item-placeholder">📦</span>
            </div>
            <div class="listing-info">
              <h3>{{ listing.item_name }}</h3>
              <p class="item-category">{{ listing.category_name }}</p>
              <div class="listing-details">
                <span class="quantity">Qty: {{ formatNumber(listing.quantity) }}</span>
                <span class="seller">by {{ listing.seller_name }}</span>
              </div>
            </div>
            <div class="listing-price">
              <span class="price-per-unit">{{ formatMoney(listing.price_per_unit) }}</span>
              <span class="price-label">per unit</span>
            </div>
            <div class="listing-actions">
              <button
                @click="buyItem(listing.id)"
                :disabled="processing || listing.seller_id === player?.id"
                class="btn btn-success"
              >
                Buy
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- My Listings -->
      <div v-if="activeTab === 'my-listings'" class="my-listings-section">
        <div v-if="myListings.length === 0" class="empty-state">
          <p>You don't have any active listings.</p>
          <button @click="activeTab = 'sell'" class="btn btn-primary">
            Create Listing
          </button>
        </div>

        <div v-else class="my-listings-list">
          <div v-for="listing in myListings" :key="listing.id" class="my-listing-item">
            <div class="listing-image">
              <img v-if="listing.item_image" :src="listing.item_image" :alt="listing.item_name" />
              <span v-else class="item-placeholder">📦</span>
            </div>
            <div class="listing-info">
              <h3>{{ listing.item_name }}</h3>
              <div class="listing-stats">
                <span>{{ formatNumber(listing.quantity) }} remaining</span>
                <span>{{ formatMoney(listing.price_per_unit) }} each</span>
              </div>
              <span class="listed-date">Listed: {{ formatDate(listing.created_at) }}</span>
            </div>
            <div class="listing-earnings">
              <span class="earnings-label">Earnings</span>
              <span class="earnings-amount">{{ formatMoney(listing.total_earned || 0) }}</span>
            </div>
            <div class="listing-actions">
              <button
                @click="cancelListing(listing.id)"
                :disabled="processing"
                class="btn btn-danger"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sell Items -->
      <div v-if="activeTab === 'sell'" class="sell-section">
        <div class="card">
          <h2>📤 Create Listing</h2>

          <div v-if="myInventory.length === 0" class="empty-state">
            <p>You don't have any items to sell.</p>
          </div>

          <div v-else class="sell-form">
            <!-- Inventory Selection -->
            <div class="form-section">
              <h3>Select Item</h3>
              <div class="inventory-grid">
                <div
                  v-for="item in myInventory"
                  :key="item.id"
                  :class="['inventory-item', { selected: newListing.item_id === item.id }]"
                  @click="selectInventoryItem(item)"
                >
                  <img v-if="item.image" :src="item.image" :alt="item.name" />
                  <span v-else class="item-placeholder">📦</span>
                  <span class="item-name">{{ item.name }}</span>
                  <span class="item-quantity">x{{ item.quantity }}</span>
                </div>
              </div>
            </div>

            <!-- Listing Details -->
            <div v-if="newListing.item_id" class="form-section">
              <h3>Listing Details</h3>
              <div class="form-group">
                <label>Quantity</label>
                <input
                  type="number"
                  v-model.number="newListing.quantity"
                  min="1"
                  :max="myInventory.find(i => i.id === newListing.item_id)?.quantity || 1"
                />
              </div>
              <div class="form-group">
                <label>Price per Unit</label>
                <input
                  type="number"
                  v-model.number="newListing.price_per_unit"
                  min="1"
                  placeholder="Enter price"
                />
              </div>
              <div class="listing-summary">
                <span class="summary-label">Total Listing Value:</span>
                <span class="summary-value">{{ formatMoney(totalListingPrice) }}</span>
              </div>
              <button
                @click="createListing"
                :disabled="processing || !newListing.price_per_unit"
                class="btn btn-success btn-block"
              >
                Create Listing
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.market-view {
  max-width: 1000px;
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

.balance-card {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
  margin-bottom: 1.5rem;
}

.balance-label {
  color: var(--color-text-muted);
}

.balance-amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-success);
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

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.filter-group {
  flex: 1;
  min-width: 150px;
}

.search-input,
.filter-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  background: var(--color-background);
  color: var(--color-text);
  font-size: 1rem;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: var(--color-primary);
}

.listings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.listing-card {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.listing-image {
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-background);
  border-radius: 0.5rem;
}

.listing-image img {
  max-height: 80px;
  max-width: 100%;
  object-fit: contain;
}

.item-placeholder {
  font-size: 3rem;
}

.listing-info h3 {
  margin: 0;
  font-size: 1rem;
  color: var(--color-heading);
}

.item-category {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin: 0.25rem 0;
}

.listing-details {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.listing-price {
  text-align: center;
  padding: 0.75rem;
  background: var(--color-background);
  border-radius: 0.5rem;
}

.price-per-unit {
  display: block;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-success);
}

.price-label {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.listing-actions {
  display: flex;
  gap: 0.5rem;
}

.my-listings-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.my-listing-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
}

.my-listing-item .listing-image {
  width: 60px;
  height: 60px;
  flex-shrink: 0;
}

.my-listing-item .listing-info {
  flex: 1;
}

.my-listing-item .listing-info h3 {
  margin: 0 0 0.25rem;
}

.listing-stats {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.listed-date {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.listing-earnings {
  text-align: center;
}

.earnings-label {
  display: block;
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.earnings-amount {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-success);
}

.card {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
  padding: 1.5rem;
}

.card h2 {
  margin-bottom: 1.5rem;
  color: var(--color-heading);
}

.form-section {
  margin-bottom: 1.5rem;
}

.form-section h3 {
  font-size: 1rem;
  color: var(--color-text-muted);
  margin-bottom: 1rem;
}

.inventory-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 0.75rem;
}

.inventory-item {
  padding: 0.75rem;
  background: var(--color-background);
  border: 2px solid var(--color-border);
  border-radius: 0.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}

.inventory-item:hover {
  border-color: var(--color-primary);
}

.inventory-item.selected {
  border-color: var(--color-primary);
  background: rgba(59, 130, 246, 0.1);
}

.inventory-item img {
  width: 40px;
  height: 40px;
  object-fit: contain;
}

.inventory-item .item-name {
  display: block;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.inventory-item .item-quantity {
  display: block;
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--color-text);
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  background: var(--color-background);
  color: var(--color-text);
  font-size: 1rem;
}

.form-group input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.listing-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--color-background);
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.summary-label {
  color: var(--color-text-muted);
}

.summary-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-success);
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

.btn-block {
  width: 100%;
}

.btn-primary {
  background-color: var(--color-primary);
  color: white;
}

.btn-success {
  background-color: var(--color-success);
  color: white;
}

.btn-danger {
  background-color: var(--color-danger);
  color: white;
}

@media (max-width: 768px) {
  .filters {
    flex-direction: column;
  }

  .my-listing-item {
    flex-wrap: wrap;
  }

  .listing-earnings {
    width: 100%;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid var(--color-border);
  }
}
</style>
