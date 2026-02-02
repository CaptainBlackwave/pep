<template>
  <div class="stocks-container">
    <h1>📈 Stock Market</h1>

    <!-- Portfolio Summary -->
    <div v-if="portfolio && portfolio.length > 0" class="portfolio-card">
      <h2>💼 My Portfolio</h2>
      <div class="portfolio-stats">
        <div class="portfolio-stat">
          <span class="stat-label">Total Value</span>
          <span class="stat-value">${{ portfolioValue.toLocaleString() }}</span>
        </div>
        <div class="portfolio-stat">
          <span class="stat-label">Total Profit/Loss</span>
          <span :class="['stat-value', totalProfitLoss >= 0 ? 'positive' : 'negative']">
            {{ totalProfitLoss >= 0 ? '+' : '' }}${{ totalProfitLoss.toLocaleString() }}
          </span>
        </div>
        <div class="portfolio-stat">
          <span class="stat-label">Holdings</span>
          <span class="stat-value">{{ portfolio.length }}</span>
        </div>
      </div>

      <div class="portfolio-grid">
        <div v-for="holding in portfolio" :key="holding.stock_id" class="holding-card">
          <div class="holding-header">
            <div>
              <h3>{{ holding.stock.name }}</h3>
              <span class="ticker">{{ holding.stock.ticker }}</span>
            </div>
            <button @click="openSellModal(holding)" class="btn-sell-small">Sell</button>
          </div>
          <div class="holding-details">
            <div class="detail">
              <span class="label">Shares Owned</span>
              <span class="value">{{ holding.shares }}</span>
            </div>
            <div class="detail">
              <span class="label">Avg Buy Price</span>
              <span class="value">${{ holding.average_buy_price.toFixed(2) }}</span>
            </div>
            <div class="detail">
              <span class="label">Current Price</span>
              <span class="value">${{ holding.stock.current_price.toFixed(2) }}</span>
            </div>
            <div class="detail">
              <span class="label">Total Value</span>
              <span class="value">${{ (holding.shares * holding.stock.current_price).toLocaleString() }}</span>
            </div>
            <div class="detail">
              <span class="label">Profit/Loss</span>
              <span :class="['value', holding.profit_loss >= 0 ? 'positive' : 'negative']">
                {{ holding.profit_loss >= 0 ? '+' : '' }}${{ holding.profit_loss.toLocaleString() }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stock Listings -->
    <div class="stocks-section">
      <h2>📊 Available Stocks</h2>
      
      <!-- Sector Filter -->
      <div class="filter-bar">
        <button 
          v-for="sector in sectors" 
          :key="sector"
          @click="selectedSector = sector"
          :class="['filter-btn', { active: selectedSector === sector }]"
        >
          {{ sector }}
        </button>
      </div>

      <div class="stocks-grid">
        <div v-for="stock in filteredStocks" :key="stock.id" class="stock-card">
          <div class="stock-header">
            <div>
              <h3>{{ stock.name }}</h3>
              <span class="ticker">{{ stock.ticker }}</span>
              <span class="sector-badge">{{ stock.sector }}</span>
            </div>
            <div class="stock-icon">{{ getSectorIcon(stock.sector) }}</div>
          </div>

          <div class="stock-price">
            <span class="price">${{ stock.current_price.toFixed(2) }}</span>
            <span :class="['change', stock.price_change >= 0 ? 'positive' : 'negative']">
              {{ stock.price_change >= 0 ? '▲' : '▼' }} 
              {{ Math.abs(stock.price_change).toFixed(2) }}%
            </span>
          </div>

          <div class="stock-stats">
            <div class="stat">
              <span class="label">Today's High</span>
              <span class="value">${{ stock.high_24h?.toFixed(2) || stock.current_price.toFixed(2) }}</span>
            </div>
            <div class="stat">
              <span class="label">Today's Low</span>
              <span class="value">${{ stock.low_24h?.toFixed(2) || stock.current_price.toFixed(2) }}</span>
            </div>
            <div class="stat">
              <span class="label">Volume</span>
              <span class="value">{{ stock.volume?.toLocaleString() || 'N/A' }}</span>
            </div>
          </div>

          <div class="stock-actions">
            <button @click="openBuyModal(stock)" class="btn-buy">Buy</button>
            <button @click="viewStockDetails(stock)" class="btn-details">Details</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Buy Modal -->
    <div v-if="showBuyModal" class="modal-overlay" @click="closeBuyModal">
      <div class="modal-content" @click.stop>
        <h3>Buy {{ selectedStock.name }}</h3>
        <p class="modal-subtitle">{{ selectedStock.ticker }} - ${{ selectedStock.current_price.toFixed(2) }} per share</p>
        
        <div class="input-group">
          <label>Number of Shares</label>
          <input v-model.number="buyShares" type="number" min="1" class="input-field" />
        </div>

        <div class="transaction-summary">
          <div class="summary-row">
            <span>Total Cost:</span>
            <span class="summary-value">${{ (buyShares * selectedStock.current_price).toFixed(2) }}</span>
          </div>
        </div>

        <div class="modal-actions">
          <button @click="executeBuy" :disabled="buying" class="btn-primary">
            {{ buying ? 'Processing...' : 'Confirm Purchase' }}
          </button>
          <button @click="closeBuyModal" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Sell Modal -->
    <div v-if="showSellModal" class="modal-overlay" @click="closeSellModal">
      <div class="modal-content" @click.stop>
        <h3>Sell {{ selectedHolding.stock.name }}</h3>
        <p class="modal-subtitle">
          You own {{ selectedHolding.shares }} shares @ ${{ selectedHolding.stock.current_price.toFixed(2) }}
        </p>
        
        <div class="input-group">
          <label>Number of Shares to Sell</label>
          <input 
            v-model.number="sellShares" 
            type="number" 
            min="1" 
            :max="selectedHolding.shares"
            class="input-field" 
          />
        </div>

        <div class="transaction-summary">
          <div class="summary-row">
            <span>Total Revenue:</span>
            <span class="summary-value">${{ (sellShares * selectedHolding.stock.current_price).toFixed(2) }}</span>
          </div>
          <div class="summary-row">
            <span>Profit/Loss:</span>
            <span :class="['summary-value', profitLoss >= 0 ? 'positive' : 'negative']">
              {{ profitLoss >= 0 ? '+' : '' }}${{ profitLoss.toFixed(2) }}
            </span>
          </div>
        </div>

        <div class="modal-actions">
          <button @click="executeSell" :disabled="selling" class="btn-primary">
            {{ selling ? 'Processing...' : 'Confirm Sale' }}
          </button>
          <button @click="closeSellModal" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const stocks = ref([])
const portfolio = ref([])
const selectedSector = ref('All')
const showBuyModal = ref(false)
const showSellModal = ref(false)
const selectedStock = ref(null)
const selectedHolding = ref(null)
const buyShares = ref(1)
const sellShares = ref(1)
const buying = ref(false)
const selling = ref(false)

const sectors = computed(() => {
  const sectorSet = new Set(['All'])
  stocks.value.forEach(s => sectorSet.add(s.sector))
  return Array.from(sectorSet)
})

const filteredStocks = computed(() => {
  if (selectedSector.value === 'All') return stocks.value
  return stocks.value.filter(s => s.sector === selectedSector.value)
})

const portfolioValue = computed(() => {
  return portfolio.value.reduce((sum, holding) => {
    return sum + (holding.shares * holding.stock.current_price)
  }, 0)
})

const totalProfitLoss = computed(() => {
  return portfolio.value.reduce((sum, holding) => sum + holding.profit_loss, 0)
})

const profitLoss = computed(() => {
  if (!selectedHolding.value) return 0
  const buyPrice = selectedHolding.value.average_buy_price * sellShares.value
  const sellPrice = selectedHolding.value.stock.current_price * sellShares.value
  return sellPrice - buyPrice
})

const getSectorIcon = (sector) => {
  const icons = {
    'Technology': '💻',
    'Finance': '💰',
    'Healthcare': '🏥',
    'Energy': '⚡',
    'Real Estate': '🏢',
    'Manufacturing': '🏭',
    'Retail': '🛒',
    'Entertainment': '🎬'
  }
  return icons[sector] || '📊'
}

const loadStocks = async () => {
  try {
    const response = await api.get('/stocks')
    stocks.value = response.data.stocks
  } catch (error) {
    console.error('Failed to load stocks:', error)
  }
}

const loadPortfolio = async () => {
  try {
    const response = await api.get('/stocks/portfolio/my')
    portfolio.value = response.data.portfolio || []
  } catch (error) {
    console.error('Failed to load portfolio:', error)
  }
}

const openBuyModal = (stock) => {
  selectedStock.value = stock
  buyShares.value = 1
  showBuyModal.value = true
}

const closeBuyModal = () => {
  showBuyModal.value = false
  selectedStock.value = null
}

const openSellModal = (holding) => {
  selectedHolding.value = holding
  sellShares.value = 1
  showSellModal.value = true
}

const closeSellModal = () => {
  showSellModal.value = false
  selectedHolding.value = null
}

const executeBuy = async () => {
  buying.value = true
  try {
    const response = await api.post('/stocks/buy', {
      stock_id: selectedStock.value.id,
      shares: buyShares.value
    })
    alert(response.data.message)
    closeBuyModal()
    await loadPortfolio()
    await loadStocks()
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to purchase stock')
  } finally {
    buying.value = false
  }
}

const executeSell = async () => {
  selling.value = true
  try {
    const response = await api.post('/stocks/sell', {
      stock_id: selectedHolding.value.stock_id,
      shares: sellShares.value
    })
    alert(response.data.message)
    closeSellModal()
    await loadPortfolio()
    await loadStocks()
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to sell stock')
  } finally {
    selling.value = false
  }
}

const viewStockDetails = (stock) => {
  alert(`${stock.name} (${stock.ticker})\n\nPrice: $${stock.current_price.toFixed(2)}\nSector: ${stock.sector}\n\nFull details page coming soon!`)
}

onMounted(() => {
  loadStocks()
  loadPortfolio()
  
  // Auto-refresh every 30 seconds
  setInterval(() => {
    loadStocks()
    loadPortfolio()
  }, 30000)
})
</script>

<style scoped>
.stocks-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

h1 {
  color: #1e3a8a;
  margin-bottom: 2rem;
  font-size: 2.5rem;
}

.portfolio-card {
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: white;
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 3rem;
  box-shadow: 0 8px 16px rgba(5, 150, 105, 0.3);
}

.portfolio-card h2 {
  color: white;
  margin: 0 0 1.5rem 0;
}

.portfolio-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.portfolio-stat {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.portfolio-stat .stat-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.portfolio-stat .stat-value {
  color: white;
  font-size: 1.75rem;
  font-weight: bold;
}

.portfolio-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.holding-card {
  background: white;
  color: #1f2937;
  border-radius: 0.75rem;
  padding: 1.5rem;
}

.holding-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.holding-header h3 {
  color: #1e3a8a;
  margin: 0 0 0.25rem 0;
  font-size: 1.1rem;
}

.ticker {
  background: #dbeafe;
  color: #1e3a8a;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: bold;
}

.btn-sell-small {
  padding: 0.5rem 1rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-sell-small:hover {
  background: #dc2626;
}

.holding-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.holding-details .detail {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.holding-details .label {
  color: #6b7280;
  font-size: 0.75rem;
  text-transform: uppercase;
}

.holding-details .value {
  color: #1f2937;
  font-weight: 600;
}

.positive {
  color: #059669 !important;
}

.negative {
  color: #dc2626 !important;
}

.stocks-section h2 {
  color: #1e3a8a;
  margin-bottom: 1.5rem;
}

.filter-bar {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 0.5rem 1rem;
  background: white;
  color: #059669;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.filter-btn:hover {
  border-color: #059669;
}

.filter-btn.active {
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: white;
  border-color: #059669;
}

.stocks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.stock-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.stock-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(5, 150, 105, 0.2);
}

.stock-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.stock-header h3 {
  color: #1e3a8a;
  margin: 0 0 0.5rem 0;
}

.sector-badge {
  display: inline-block;
  background: #f3f4f6;
  color: #6b7280;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 0.5rem;
}

.stock-icon {
  font-size: 2.5rem;
}

.stock-price {
  display: flex;
  align-items: baseline;
  gap: 1rem;
  margin-bottom: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.price {
  font-size: 2rem;
  font-weight: bold;
  color: #1e3a8a;
}

.change {
  font-size: 1rem;
  font-weight: 600;
}

.stock-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

.stock-stats .stat {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stock-stats .label {
  color: #6b7280;
  font-size: 0.75rem;
}

.stock-stats .value {
  color: #1f2937;
  font-weight: 600;
}

.stock-actions {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 0.5rem;
}

.btn-buy {
  padding: 0.75rem;
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-buy:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);
}

.btn-details {
  padding: 0.75rem;
  background: white;
  color: #059669;
  border: 2px solid #059669;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-details:hover {
  background: #f0fdf4;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 1rem;
  width: 500px;
  max-width: 90%;
}

.modal-content h3 {
  color: #1e3a8a;
  margin: 0 0 0.5rem 0;
}

.modal-subtitle {
  color: #6b7280;
  margin: 0 0 1.5rem 0;
}

.input-group {
  margin-bottom: 1.5rem;
}

.input-group label {
  display: block;
  color: #1f2937;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.input-field {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 1rem;
}

.input-field:focus {
  outline: none;
  border-color: #059669;
}

.transaction-summary {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
}

.summary-value {
  font-weight: bold;
  color: #1e3a8a;
}

.modal-actions {
  display: flex;
  gap: 1rem;
}

.btn-primary, .btn-secondary {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: white;
}

.btn-secondary {
  background: #e5e7eb;
  color: #1f2937;
}

.btn-primary:hover:not(:disabled), .btn-secondary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
