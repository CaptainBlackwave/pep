import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const usePlayerStore = defineStore('player', () => {
  // State
  const player = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const lastFetched = ref(null)

  // Timer intervals
  let refreshInterval = null
  let timerInterval = null

  // Computed - Player info
  const username = computed(() => player.value?.username || 'Guest')
  const displayName = computed(() => player.value?.name || player.value?.username || 'Guest')
  const avatar = computed(() => player.value?.avatar || null)
  const rank = computed(() => player.value?.rank?.name || 'Citizen')
  const rankIcon = computed(() => player.value?.rank?.icon || '👤')
  const location = computed(() => player.value?.location?.name || 'Downtown')
  const gang = computed(() => player.value?.gang?.name || null)
  const level = computed(() => player.value?.stats?.level || 1)

  // Computed - Stats
  const energy = computed(() => player.value?.stats?.energy || 0)
  const maxEnergy = computed(() => player.value?.stats?.maxEnergy || 100)
  const energyPercent = computed(() => Math.round((energy.value / maxEnergy.value) * 100))
  const energyTimer = computed(() => player.value?.timers?.energy || '')

  const health = computed(() => player.value?.stats?.health || 0)
  const maxHealth = computed(() => player.value?.stats?.maxHealth || 100)
  const healthPercent = computed(() => Math.round((health.value / maxHealth.value) * 100))
  const healthTimer = computed(() => player.value?.timers?.health || '')

  const stamina = computed(() => player.value?.stats?.stamina || 0)
  const maxStamina = computed(() => player.value?.stats?.maxStamina || 100)
  const staminaPercent = computed(() => Math.round((stamina.value / maxStamina.value) * 100))
  const staminaTimer = computed(() => player.value?.timers?.stamina || '')

  const nerve = computed(() => player.value?.stats?.nerve || 0)
  const maxNerve = computed(() => player.value?.stats?.maxNerve || 100)
  const nervePercent = computed(() => Math.round((nerve.value / maxNerve.value) * 100))
  const nerveTimer = computed(() => player.value?.timers?.nerve || '')

  // Computed - Currency
  const cash = computed(() => player.value?.stats?.cash || 0)
  const bank = computed(() => player.value?.stats?.bank || 0)
  const points = computed(() => player.value?.stats?.points || 0)
  const diamonds = computed(() => player.value?.stats?.diamonds || 0)

  // Computed - Combat stats
  const strength = computed(() => player.value?.stats?.strength || 0)
  const defense = computed(() => player.value?.stats?.defense || 0)
  const speed = computed(() => player.value?.stats?.speed || 0)
  const endurance = computed(() => player.value?.stats?.endurance || 0)

  // Computed - Status
  const isJailed = computed(() => player.value?.isJailed || false)
  const isTraveling = computed(() => player.value?.isTraveling || false)
  const jailTimer = computed(() => player.value?.timers?.jail || null)
  const travelTimer = computed(() => player.value?.timers?.travel || null)

  // Actions
  async function fetchPlayer() {
    if (loading.value) return false

    loading.value = true
    error.value = null

    try {
      const response = await api.get('/user')
      const userData = response.data.user || response.data

      // Transform API response to our store format
      player.value = transformUserData(userData)
      lastFetched.value = new Date()

      // Start timers
      startTimers()

      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch player data'
      console.error('Failed to fetch player:', err)
      return false
    } finally {
      loading.value = false
    }
  }

  async function refreshStats() {
    if (!player.value) return

    try {
      const response = await api.get('/stats')
      const statsData = response.data

      if (player.value && statsData) {
        // Update stats without full reload
        player.value.stats = {
          ...player.value.stats,
          energy: statsData.energy ?? player.value.stats.energy,
          maxEnergy: statsData.max_energy ?? player.value.stats.maxEnergy,
          health: statsData.health ?? player.value.stats.health,
          maxHealth: statsData.max_health ?? player.value.stats.maxHealth,
          stamina: statsData.stamina ?? player.value.stats.stamina,
          maxStamina: statsData.max_stamina ?? player.value.stats.maxStamina,
          nerve: statsData.nerve ?? player.value.stats.nerve,
          maxNerve: statsData.max_nerve ?? player.value.stats.maxNerve,
          cash: statsData.cash ?? player.value.stats.cash,
          bank: statsData.bank ?? player.value.stats.bank,
          points: statsData.points ?? player.value.stats.points,
          diamonds: statsData.diamonds ?? player.value.stats.diamonds,
          level: statsData.level ?? player.value.stats.level,
          experience: statsData.experience ?? player.value.stats.experience,
        }

        // Update timers if provided
        if (statsData.timers) {
          player.value.timers = {
            ...player.value.timers,
            ...statsData.timers
          }
        }
      }
    } catch (err) {
      console.error('Failed to refresh stats:', err)
    }
  }

  function transformUserData(data) {
    return {
      id: data.id,
      username: data.username || data.name,
      name: data.name || data.username,
      email: data.email,
      avatar: data.avatar || data.profile_photo_url,
      rank: data.current_rank || data.rank || { id: 1, name: 'Citizen' },
      location: data.location || { id: 1, name: 'Downtown' },
      gang: data.gang || null,
      stats: {
        energy: data.energy ?? 100,
        maxEnergy: data.max_energy ?? 100,
        health: data.health ?? 100,
        maxHealth: data.max_health ?? 100,
        stamina: data.stamina ?? 100,
        maxStamina: data.max_stamina ?? 100,
        nerve: data.nerve ?? 100,
        maxNerve: data.max_nerve ?? 100,
        cash: data.cash ?? 0,
        bank: data.bank ?? 0,
        points: data.points ?? 0,
        diamonds: data.diamonds ?? 0,
        level: data.level ?? 1,
        experience: data.experience ?? 0,
        experienceToNextLevel: data.experience_to_next_level ?? 100,
        strength: data.strength ?? 0,
        defense: data.defense ?? 0,
        speed: data.speed ?? 0,
        endurance: data.endurance ?? 0,
      },
      timers: {
        energy: formatTimer(data.energy_timer || data.timers?.energy),
        health: formatTimer(data.health_timer || data.timers?.health),
        stamina: formatTimer(data.stamina_timer || data.timers?.stamina),
        nerve: formatTimer(data.nerve_timer || data.timers?.nerve),
        jail: data.jail_timer || data.timers?.jail || null,
        travel: data.travel_timer || data.timers?.travel || null,
      },
      isJailed: data.is_jailed ?? data.jail_timer != null,
      isTraveling: data.is_traveling ?? data.travel_timer != null,
      roles: data.roles?.map(r => typeof r === 'string' ? r : r.name) ?? [],
      permissions: data.permissions?.map(p => typeof p === 'string' ? p : p.name) ?? [],
    }
  }

  function formatTimer(seconds) {
    if (!seconds) return ''
    const secs = typeof seconds === 'string' ? parseInt(seconds) : seconds
    if (isNaN(secs) || secs <= 0) return ''

    const mins = Math.floor(secs / 60)
    const remainingSecs = secs % 60
    return `${mins.toString().padStart(2, '0')}:${remainingSecs.toString().padStart(2, '0')}`
  }

  function startTimers() {
    stopTimers()

    // Refresh stats every 30 seconds
    refreshInterval = setInterval(() => {
      refreshStats()
    }, 30000)
  }

  function stopTimers() {
    if (refreshInterval) {
      clearInterval(refreshInterval)
      refreshInterval = null
    }
    if (timerInterval) {
      clearInterval(timerInterval)
      timerInterval = null
    }
  }

  function updateStats(newStats) {
    if (!player.value) return
    player.value.stats = { ...player.value.stats, ...newStats }
  }

  function updateCurrency(cash, bank) {
    if (!player.value) return
    if (cash !== undefined) player.value.stats.cash = cash
    if (bank !== undefined) player.value.stats.bank = bank
  }

  function clearPlayer() {
    stopTimers()
    player.value = null
    lastFetched.value = null
    error.value = null
  }

  return {
    // State
    player,
    loading,
    error,
    lastFetched,

    // Player info
    username,
    displayName,
    avatar,
    rank,
    rankIcon,
    location,
    gang,
    level,

    // Stats
    energy,
    maxEnergy,
    energyPercent,
    energyTimer,
    health,
    maxHealth,
    healthPercent,
    healthTimer,
    stamina,
    maxStamina,
    staminaPercent,
    staminaTimer,
    nerve,
    maxNerve,
    nervePercent,
    nerveTimer,

    // Currency
    cash,
    bank,
    points,
    diamonds,

    // Combat stats
    strength,
    defense,
    speed,
    endurance,

    // Status
    isJailed,
    isTraveling,
    jailTimer,
    travelTimer,

    // Actions
    fetchPlayer,
    refreshStats,
    updateStats,
    updateCurrency,
    clearPlayer,
    stopTimers,
  }
})
