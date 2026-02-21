import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { websocketService } from '@/services/websocket'
import type { StatsUpdatedEvent } from '@/types/websocket'
import type {
  Player,
  PlayerRank,
  PlayerLocation,
  PlayerGang,
  PlayerStats,
  PlayerTimers
} from '@/types/user'

// Re-export types for backward compatibility
export type {
  Player,
  PlayerRank,
  PlayerLocation,
  PlayerGang,
  PlayerStats,
  PlayerTimers
} from '@/types/user'

/**
 * Player store - manages player state and real-time updates
 */
export const usePlayerStore = defineStore('player', () => {
  // State
  const player = ref<Player | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const lastFetched = ref<Date | null>(null)
  const connected = ref(false)

  // WebSocket cleanup functions
  const wsUnsubscribers: (() => void)[] = []

  // Timer interval for local stat regeneration display
  let timerInterval: ReturnType<typeof setInterval> | null = null

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

  // Computed - Permissions
  const isAdmin = computed(() => player.value?.roles?.includes('admin') || false)

  /**
   * Format timer seconds to MM:SS string
   */
  function formatTimer(seconds: number | string | undefined): string {
    if (!seconds) return ''
    const secs = typeof seconds === 'string' ? parseInt(seconds) : seconds
    if (isNaN(secs) || secs <= 0) return ''

    const mins = Math.floor(secs / 60)
    const remainingSecs = secs % 60
    return `${mins.toString().padStart(2, '0')}:${remainingSecs.toString().padStart(2, '0')}`
  }

  /**
   * Transform API response to store format
   * Handles various API response formats and normalizes data structure
   * @param data - Raw API response data
   * @returns Normalized Player object
   */
  function transformUserData(data: Record<string, unknown>): Player {
    const d = data as Record<string, unknown>
    return {
      id: d.id as number,
      username: (d.username || d.name || 'Unknown') as string,
      name: (d.name || d.username || 'Unknown') as string,
      email: d.email as string | undefined,
      avatar: (d.avatar || d.profile_photo_url) as string | undefined,
      rank: (d.current_rank || d.rank || { id: 1, name: 'Citizen' }) as PlayerRank,
      location: (d.location || { id: 1, name: 'Downtown' }) as PlayerLocation,
      gang: (d.gang || null) as PlayerGang | null,
      stats: {
        energy: (d.energy ?? 100) as number,
        maxEnergy: (d.max_energy ?? 100) as number,
        health: (d.health ?? 100) as number,
        maxHealth: (d.max_health ?? 100) as number,
        stamina: (d.stamina ?? 100) as number,
        maxStamina: (d.max_stamina ?? 100) as number,
        nerve: (d.nerve ?? 100) as number,
        maxNerve: (d.max_nerve ?? 100) as number,
        cash: (d.cash ?? 0) as number,
        bank: (d.bank ?? 0) as number,
        points: (d.points ?? 0) as number,
        diamonds: (d.diamonds ?? 0) as number,
        level: (d.level ?? 1) as number,
        experience: (d.experience ?? 0) as number,
        experienceToNextLevel: (d.experience_to_next_level ?? 100) as number,
        strength: (d.strength ?? 0) as number,
        defense: (d.defense ?? 0) as number,
        speed: (d.speed ?? 0) as number,
        endurance: (d.endurance ?? 0) as number,
      },
      timers: {
        energy: formatTimer((d.energy_timer || (d.timers as Record<string, unknown>)?.energy) as string | number | undefined),
        health: formatTimer((d.health_timer || (d.timers as Record<string, unknown>)?.health) as string | number | undefined),
        stamina: formatTimer((d.stamina_timer || (d.timers as Record<string, unknown>)?.stamina) as string | number | undefined),
        nerve: formatTimer((d.nerve_timer || (d.timers as Record<string, unknown>)?.nerve) as string | number | undefined),
        jail: (d.jail_timer || (d.timers as Record<string, unknown>)?.jail || null) as string | null,
        travel: (d.travel_timer || (d.timers as Record<string, unknown>)?.travel || null) as string | null,
      },
      isJailed: (d.is_jailed ?? !!d.jail_timer) as boolean,
      isTraveling: (d.is_traveling ?? !!d.travel_timer) as boolean,
      roles: ((d.roles as Array<string | { name: string }>)?.map(r => typeof r === 'string' ? r : r.name) ?? []) as string[],
      permissions: ((d.permissions as Array<string | { name: string }>)?.map(p => typeof p === 'string' ? p : p.name) ?? []) as string[],
    }
  }

  /**
   * Fetch player data from API
   * Retrieves current player data and updates store state
   * @returns True if fetch was successful, false otherwise
   */
  async function fetchPlayer(): Promise<boolean> {
    if (loading.value) return false

    loading.value = true
    error.value = null

    try {
      const response = await api.get('/user')
      const userData = response.data.user || response.data
      player.value = transformUserData(userData)
      lastFetched.value = new Date()
      return true
    } catch {
      error.value = 'Failed to fetch player data'
      return false
    } finally {
      loading.value = false
    }
  }

  /**
   * Refresh stats from API
   * Updates player stats without full player data fetch
   * Silently fails on error - stats will refresh on next full fetch
   */
  async function refreshStats(): Promise<void> {
    if (!player.value) return

    try {
      const response = await api.get('/stats')
      const statsData = response.data

      if (player.value && statsData) {
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

        if (statsData.timers) {
          player.value.timers = {
            ...player.value.timers,
            ...statsData.timers
          }
        }
      }
    } catch {
      // Silently fail - stats will be refreshed on next fetch
    }
  }

  /**
   * Update stats locally
   */
  function updateStats(newStats: Partial<PlayerStats>): void {
    if (!player.value) return
    player.value.stats = { ...player.value.stats, ...newStats }
  }

  /**
   * Update currency locally
   */
  function updateCurrency(newCash?: number, newBank?: number): void {
    if (!player.value) return
    if (newCash !== undefined) player.value.stats.cash = newCash
    if (newBank !== undefined) player.value.stats.bank = newBank
  }

  /**
   * Handle stats update from WebSocket
   * Updates only the fields that changed in the event
   * @param data - Stats update event from WebSocket
   */
  function handleStatsUpdate(data: StatsUpdatedEvent): void {
    if (!player.value) return

    // Update only the fields that changed
    if (data.energy !== undefined) player.value.stats.energy = data.energy
    if (data.maxEnergy !== undefined) player.value.stats.maxEnergy = data.maxEnergy
    if (data.health !== undefined) player.value.stats.health = data.health
    if (data.maxHealth !== undefined) player.value.stats.maxHealth = data.maxHealth
    if (data.stamina !== undefined) player.value.stats.stamina = data.stamina
    if (data.maxStamina !== undefined) player.value.stats.maxStamina = data.maxStamina
    if (data.nerve !== undefined) player.value.stats.nerve = data.nerve
    if (data.maxNerve !== undefined) player.value.stats.maxNerve = data.maxNerve
    if (data.cash !== undefined) player.value.stats.cash = data.cash
    if (data.bank !== undefined) player.value.stats.bank = data.bank
    if (data.points !== undefined) player.value.stats.points = data.points
    if (data.diamonds !== undefined) player.value.stats.diamonds = data.diamonds
    if (data.experience !== undefined) player.value.stats.experience = data.experience
    if (data.level !== undefined) player.value.stats.level = data.level
  }

  /**
   * Initialize WebSocket listeners
   */
  function initWebSocket(): void {
    const unsubStats = websocketService.on<StatsUpdatedEvent>('stats-updated', handleStatsUpdate)
    wsUnsubscribers.push(unsubStats)
    connected.value = true
  }

  /**
   * Connect to WebSocket and start real-time updates
   */
  async function connect(): Promise<void> {
    if (!player.value?.id) return

    try {
      await websocketService.connect()

      // Subscribe to user's private channel
      websocketService.subscribe(`private-user.${player.value.id}`)

      // Initialize WebSocket listeners
      initWebSocket()
    } catch {
      // Fall back to polling if WebSocket fails
      startPolling()
    }
  }

  /**
   * Start polling for stats (fallback)
   */
  function startPolling(intervalMs = 30000): void {
    stopPolling()
    const interval = setInterval(refreshStats, intervalMs)
    wsUnsubscribers.push(() => clearInterval(interval))
  }

  /**
   * Stop polling
   */
  function stopPolling(): void {
    if (timerInterval) {
      clearInterval(timerInterval)
      timerInterval = null
    }
  }

  /**
   * Disconnect and cleanup
   */
  function disconnect(): void {
    wsUnsubscribers.forEach(unsub => unsub())
    wsUnsubscribers.length = 0
    stopPolling()
    connected.value = false
  }

  /**
   * Clear all state
   */
  function clearPlayer(): void {
    disconnect()
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
    connected,

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

    // Permissions
    isAdmin,

    // Actions
    fetchPlayer,
    refreshStats,
    updateStats,
    updateCurrency,
    connect,
    disconnect,
    clearPlayer,
    stopPolling,
  }
})
