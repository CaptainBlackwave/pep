<template>
  <div class="casino-container">
    <h1>🎰 Casino</h1>

    <!-- Player Stats -->
    <div class="stats-card">
      <h2>Your Gambling Statistics</h2>
      <div class="stats-grid">
        <div class="stat-box">
          <span class="stat-icon">💰</span>
          <div>
            <span class="stat-label">Total Wagered</span>
            <span class="stat-value">${{ stats.total_wagered?.toLocaleString() || 0 }}</span>
          </div>
        </div>
        <div class="stat-box">
          <span class="stat-icon">✅</span>
          <div>
            <span class="stat-label">Total Won</span>
            <span class="stat-value positive">${{ stats.total_won?.toLocaleString() || 0 }}</span>
          </div>
        </div>
        <div class="stat-box">
          <span class="stat-icon">❌</span>
          <div>
            <span class="stat-label">Total Lost</span>
            <span class="stat-value negative">${{ stats.total_lost?.toLocaleString() || 0 }}</span>
          </div>
        </div>
        <div class="stat-box">
          <span class="stat-icon">📊</span>
          <div>
            <span class="stat-label">Net Profit/Loss</span>
            <span :class="['stat-value', netProfit >= 0 ? 'positive' : 'negative']">
              {{ netProfit >= 0 ? '+' : '' }}${{ netProfit.toLocaleString() }}
            </span>
          </div>
        </div>
        <div class="stat-box">
          <span class="stat-icon">🎲</span>
          <div>
            <span class="stat-label">Games Played</span>
            <span class="stat-value">{{ stats.games_played || 0 }}</span>
          </div>
        </div>
        <div class="stat-box">
          <span class="stat-icon">🔥</span>
          <div>
            <span class="stat-label">Win Streak</span>
            <span class="stat-value">{{ stats.current_win_streak || 0 }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Game Selection -->
    <div class="games-section">
      <h2>🎮 Select a Game</h2>
      <div class="games-grid">
        <div 
          v-for="game in games" 
          :key="game.id" 
          @click="selectGame(game)"
          :class="['game-card', { active: selectedGame?.id === game.id }]"
        >
          <div class="game-icon">{{ getGameIcon(game.type) }}</div>
          <h3>{{ game.name }}</h3>
          <p class="game-desc">{{ game.description }}</p>
          <div class="game-info">
            <span class="info-item">Min: ${{ game.min_bet }}</span>
            <span class="info-item">Max: ${{ game.max_bet }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Game Play Area -->
    <div v-if="selectedGame" class="play-area">
      <h2>🎮 {{ selectedGame.name }}</h2>

      <!-- Slots Game -->
      <div v-if="selectedGame.type === 'Slots'" class="slots-game">
        <div class="slots-reels">
          <div v-for="(symbol, index) in slotsResult" :key="index" class="reel">
            {{ symbol }}
          </div>
        </div>
        <div class="bet-controls">
          <label>Bet Amount:</label>
          <input 
            v-model.number="betAmount" 
            type="number" 
            :min="selectedGame.min_bet" 
            :max="selectedGame.max_bet"
            class="bet-input"
          />
          <button @click="playSlots" :disabled="playing" class="btn-play">
            {{ playing ? 'Spinning...' : '🎰 Spin' }}
          </button>
        </div>
      </div>

      <!-- Roulette Game -->
      <div v-if="selectedGame.type === 'Roulette'" class="roulette-game">
        <div class="roulette-wheel">
          <div class="roulette-ball">{{ rouletteResult }}</div>
        </div>
        <div class="roulette-bets">
          <h3>Place Your Bet</h3>
          <div class="bet-type-selection">
            <button 
              v-for="betType in rouletteBetTypes" 
              :key="betType.value"
              @click="rouletteBetType = betType.value"
              :class="['bet-type-btn', { active: rouletteBetType === betType.value }]"
            >
              {{ betType.label }}
            </button>
          </div>
          <div v-if="rouletteBetType === 'number'" class="number-grid">
            <button 
              v-for="num in 37" 
              :key="num-1"
              @click="rouletteBetValue = num - 1"
              :class="['number-btn', { active: rouletteBetValue === num - 1 }]"
            >
              {{ num - 1 }}
            </button>
          </div>
          <div v-else class="color-selection">
            <button 
              @click="rouletteBetValue = 'red'"
              :class="['color-btn red', { active: rouletteBetValue === 'red' }]"
            >
              Red
            </button>
            <button 
              @click="rouletteBetValue = 'black'"
              :class="['color-btn black', { active: rouletteBetValue === 'black' }]"
            >
              Black
            </button>
          </div>
          <div class="bet-controls">
            <label>Bet Amount:</label>
            <input 
              v-model.number="betAmount" 
              type="number" 
              :min="selectedGame.min_bet" 
              :max="selectedGame.max_bet"
              class="bet-input"
            />
            <button @click="playRoulette" :disabled="playing || !rouletteBetValue" class="btn-play">
              {{ playing ? 'Spinning...' : '🎡 Spin Wheel' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Dice Game -->
      <div v-if="selectedGame.type === 'Dice'" class="dice-game">
        <div class="dice-display">
          <div class="dice">🎲 {{ diceResult || '?' }}</div>
        </div>
        <div class="dice-bets">
          <h3>Predict High or Low</h3>
          <div class="dice-options">
            <button 
              @click="dicePrediction = 'high'"
              :class="['dice-btn', { active: dicePrediction === 'high' }]"
            >
              High (4-6)
            </button>
            <button 
              @click="dicePrediction = 'low'"
              :class="['dice-btn', { active: dicePrediction === 'low' }]"
            >
              Low (1-3)
            </button>
          </div>
          <div class="bet-controls">
            <label>Bet Amount:</label>
            <input 
              v-model.number="betAmount" 
              type="number" 
              :min="selectedGame.min_bet" 
              :max="selectedGame.max_bet"
              class="bet-input"
            />
            <button @click="playDice" :disabled="playing || !dicePrediction" class="btn-play">
              {{ playing ? 'Rolling...' : '🎲 Roll Dice' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Result Display -->
      <div v-if="lastResult" class="result-card" :class="lastResult.won ? 'win' : 'lose'">
        <h3>{{ lastResult.won ? '🎉 You Won!' : '😢 You Lost' }}</h3>
        <p class="result-message">{{ lastResult.message }}</p>
        <p class="result-amount">{{ lastResult.won ? '+' : '-' }}${{ lastResult.amount.toLocaleString() }}</p>
      </div>
    </div>

    <!-- Recent Bets History -->
    <div v-if="history.length > 0" class="history-section">
      <h2>📜 Recent Bets</h2>
      <div class="history-table">
        <div class="history-header">
          <span>Game</span>
          <span>Bet</span>
          <span>Result</span>
          <span>Payout</span>
          <span>Time</span>
        </div>
        <div v-for="bet in history.slice(0, 10)" :key="bet.id" class="history-row">
          <span>{{ bet.game.name }}</span>
          <span>${{ bet.bet_amount }}</span>
          <span :class="bet.won ? 'win-badge' : 'lose-badge'">
            {{ bet.won ? 'Won' : 'Lost' }}
          </span>
          <span :class="bet.won ? 'positive' : 'negative'">
            {{ bet.won ? '+' : '-' }}${{ bet.payout }}
          </span>
          <span class="time">{{ formatTime(bet.created_at) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const games = ref([])
const stats = ref({})
const history = ref([])
const selectedGame = ref(null)
const betAmount = ref(100)
const playing = ref(false)
const lastResult = ref(null)

// Slots
const slotsResult = ref(['🍒', '🍒', '🍒'])

// Roulette
const rouletteResult = ref(null)
const rouletteBetType = ref('color')
const rouletteBetValue = ref(null)
const rouletteBetTypes = [
  { value: 'color', label: 'Color' },
  { value: 'number', label: 'Number' }
]

// Dice
const diceResult = ref(null)
const dicePrediction = ref(null)

const netProfit = computed(() => {
  const won = stats.value.total_won || 0
  const lost = stats.value.total_lost || 0
  return won - lost
})

const getGameIcon = (type) => {
  const icons = {
    'Slots': '🎰',
    'Roulette': '🎡',
    'Blackjack': '🃏',
    'Poker': '♠️',
    'Dice': '🎲'
  }
  return icons[type] || '🎮'
}

const formatTime = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const loadGames = async () => {
  try {
    const response = await api.get('/casino/games')
    games.value = response.data.games
  } catch (error) {
    console.error('Failed to load games:', error)
  }
}

const loadStats = async () => {
  try {
    const response = await api.get('/casino/stats')
    stats.value = response.data.stats || {}
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

const loadHistory = async () => {
  try {
    const response = await api.get('/casino/history')
    history.value = response.data.history || []
  } catch (error) {
    console.error('Failed to load history:', error)
  }
}

const selectGame = (game) => {
  selectedGame.value = game
  lastResult.value = null
  betAmount.value = game.min_bet
}

const playSlots = async () => {
  playing.value = true
  lastResult.value = null
  
  // Animation
  const symbols = ['🍒', '🍋', '🍊', '🍉', '💎', '⭐', '7️⃣']
  const animationDuration = 1000
  const intervalTime = 100
  const iterations = animationDuration / intervalTime
  
  let count = 0
  const animation = setInterval(() => {
    slotsResult.value = [
      symbols[Math.floor(Math.random() * symbols.length)],
      symbols[Math.floor(Math.random() * symbols.length)],
      symbols[Math.floor(Math.random() * symbols.length)]
    ]
    count++
    if (count >= iterations) clearInterval(animation)
  }, intervalTime)
  
  try {
    const response = await api.post('/casino/play/slots', {
      game_id: selectedGame.value.id,
      bet_amount: betAmount.value
    })
    
    setTimeout(() => {
      slotsResult.value = response.data.result.split(',')
      lastResult.value = {
        won: response.data.won,
        message: response.data.message,
        amount: response.data.payout
      }
      loadStats()
      loadHistory()
    }, animationDuration)
  } catch (error) {
    clearInterval(animation)
    alert(error.response?.data?.message || 'Failed to play slots')
  } finally {
    setTimeout(() => {
      playing.value = false
    }, animationDuration + 100)
  }
}

const playRoulette = async () => {
  playing.value = true
  lastResult.value = null
  rouletteResult.value = '?'
  
  try {
    const response = await api.post('/casino/play/roulette', {
      game_id: selectedGame.value.id,
      bet_amount: betAmount.value,
      bet_type: rouletteBetType.value,
      bet_value: rouletteBetValue.value
    })
    
    setTimeout(() => {
      rouletteResult.value = response.data.result
      lastResult.value = {
        won: response.data.won,
        message: response.data.message,
        amount: response.data.payout
      }
      loadStats()
      loadHistory()
      playing.value = false
    }, 2000)
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to play roulette')
    playing.value = false
  }
}

const playDice = async () => {
  playing.value = true
  lastResult.value = null
  diceResult.value = '?'
  
  try {
    const response = await api.post('/casino/play/dice', {
      game_id: selectedGame.value.id,
      bet_amount: betAmount.value,
      prediction: dicePrediction.value
    })
    
    setTimeout(() => {
      diceResult.value = response.data.result
      lastResult.value = {
        won: response.data.won,
        message: response.data.message,
        amount: response.data.payout
      }
      loadStats()
      loadHistory()
      playing.value = false
    }, 1000)
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to play dice')
    playing.value = false
  }
}

onMounted(() => {
  loadGames()
  loadStats()
  loadHistory()
})
</script>

<style scoped>
.casino-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

h1 {
  color: #1e3a8a;
  margin-bottom: 2rem;
  font-size: 2.5rem;
}

.stats-card {
  background: linear-gradient(135deg, #7c2d12 0%, #dc2626 100%);
  color: white;
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 3rem;
  box-shadow: 0 8px 16px rgba(124, 45, 18, 0.3);
}

.stats-card h2 {
  color: white;
  margin: 0 0 1.5rem 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.stat-box {
  background: rgba(255, 255, 255, 0.1);
  padding: 1.5rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  font-size: 2.5rem;
}

.stat-label {
  display: block;
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  display: block;
  color: white;
  font-size: 1.5rem;
  font-weight: bold;
}

.positive {
  color: #22c55e !important;
}

.negative {
  color: #ef4444 !important;
}

.games-section h2 {
  color: #1e3a8a;
  margin-bottom: 1.5rem;
}

.games-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
  margin-bottom: 3rem;
}

.game-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: all 0.3s ease;
  border: 3px solid transparent;
}

.game-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(220, 38, 38, 0.2);
}

.game-card.active {
  border-color: #dc2626;
  box-shadow: 0 8px 16px rgba(220, 38, 38, 0.3);
}

.game-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.game-card h3 {
  color: #1e3a8a;
  margin: 0 0 0.5rem 0;
}

.game-desc {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 1rem 0;
}

.game-info {
  display: flex;
  justify-content: space-between;
  color: #1f2937;
  font-size: 0.875rem;
  font-weight: 600;
}

.play-area {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 3rem;
}

.play-area h2 {
  color: #1e3a8a;
  margin: 0 0 2rem 0;
}

.slots-game, .roulette-game, .dice-game {
  max-width: 600px;
  margin: 0 auto;
}

.slots-reels {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.reel {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 4rem;
  border: 3px solid #dc2626;
}

.roulette-wheel {
  width: 200px;
  height: 200px;
  margin: 0 auto 2rem;
  border-radius: 50%;
  background: radial-gradient(circle, #7c2d12 0%, #dc2626 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 8px solid #fbbf24;
}

.roulette-ball {
  width: 80px;
  height: 80px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: bold;
  color: #1e3a8a;
}

.roulette-bets h3, .dice-bets h3 {
  color: #1e3a8a;
  margin: 0 0 1rem 0;
  text-align: center;
}

.bet-type-selection {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  justify-content: center;
}

.bet-type-btn {
  padding: 0.5rem 1.5rem;
  background: white;
  color: #dc2626;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.bet-type-btn:hover {
  border-color: #dc2626;
}

.bet-type-btn.active {
  background: #dc2626;
  color: white;
  border-color: #dc2626;
}

.number-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.number-btn {
  aspect-ratio: 1;
  background: white;
  color: #1e3a8a;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.number-btn:hover {
  border-color: #dc2626;
}

.number-btn.active {
  background: #dc2626;
  color: white;
  border-color: #dc2626;
}

.color-selection {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  justify-content: center;
}

.color-btn {
  padding: 1rem 3rem;
  border: 3px solid white;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 1.1rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.color-btn.red {
  background: #dc2626;
  color: white;
}

.color-btn.black {
  background: #1f2937;
  color: white;
}

.color-btn.active {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.dice-display {
  text-align: center;
  margin-bottom: 2rem;
}

.dice {
  display: inline-block;
  width: 150px;
  height: 150px;
  background: white;
  border: 3px solid #dc2626;
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 4rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.dice-options {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  justify-content: center;
}

.dice-btn {
  padding: 1rem 2rem;
  background: white;
  color: #dc2626;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 1.1rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.dice-btn:hover {
  border-color: #dc2626;
}

.dice-btn.active {
  background: #dc2626;
  color: white;
  border-color: #dc2626;
}

.bet-controls {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: center;
}

.bet-controls label {
  color: #1f2937;
  font-weight: 600;
}

.bet-input {
  width: 200px;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 1rem;
  text-align: center;
}

.bet-input:focus {
  outline: none;
  border-color: #dc2626;
}

.btn-play {
  padding: 1rem 3rem;
  background: linear-gradient(135deg, #dc2626 0%, #7c2d12 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-play:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4);
}

.btn-play:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.result-card {
  margin-top: 2rem;
  padding: 2rem;
  border-radius: 1rem;
  text-align: center;
}

.result-card.win {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white;
}

.result-card.lose {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.result-card h3 {
  color: white;
  margin: 0 0 0.5rem 0;
  font-size: 2rem;
}

.result-message {
  color: rgba(255, 255, 255, 0.9);
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
}

.result-amount {
  color: white;
  font-size: 2.5rem;
  font-weight: bold;
  margin: 0;
}

.history-section h2 {
  color: #1e3a8a;
  margin-bottom: 1.5rem;
}

.history-table {
  background: white;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.history-header {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
  gap: 1rem;
  padding: 1rem 1.5rem;
  background: #f3f4f6;
  font-weight: 600;
  color: #1f2937;
}

.history-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  align-items: center;
}

.history-row:last-child {
  border-bottom: none;
}

.win-badge {
  background: #d1fae5;
  color: #065f46;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  display: inline-block;
}

.lose-badge {
  background: #fee2e2;
  color: #991b1b;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  display: inline-block;
}

.time {
  color: #6b7280;
  font-size: 0.875rem;
}
</style>
