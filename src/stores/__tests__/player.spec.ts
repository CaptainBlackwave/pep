import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { usePlayerStore } from '@/stores/player'

// Use vi.hoisted to define mocks before vi.mock is hoisted
const { mockGet, mockPost } = vi.hoisted(() => {
  return {
    mockGet: vi.fn(),
    mockPost: vi.fn(),
  }
})

vi.mock('@/services/api', () => ({
  default: {
    get: mockGet,
    post: mockPost,
  },
}))

vi.mock('@/services/websocket', () => ({
  websocketService: {
    connect: vi.fn().mockResolvedValue(undefined),
    subscribe: vi.fn(),
    unsubscribe: vi.fn(),
    on: vi.fn().mockReturnValue(() => {}),
  },
}))

const makeUserResponse = (overrides = {}) => ({
  data: {
    id: 1,
    username: 'testuser',
    name: 'Test User',
    email: 'test@example.com',
    energy: 80,
    max_energy: 100,
    health: 90,
    max_health: 100,
    stamina: 70,
    max_stamina: 100,
    nerve: 50,
    max_nerve: 100,
    cash: 5000,
    bank: 10000,
    points: 100,
    diamonds: 10,
    level: 5,
    experience: 500,
    strength: 100,
    defense: 80,
    speed: 90,
    endurance: 85,
    current_rank: { id: 1, name: 'Citizen' },
    location: { id: 1, name: 'Downtown' },
    gang: null,
    roles: ['player'],
    ...overrides,
  },
})

describe('Player Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.clearAllMocks()
    // Clear the player store state
    const player = usePlayerStore()
    player.clearPlayer()
  })

  // ─── Initial State ────────────────────────────────────────────────────────

  describe('initial state', () => {
    it('starts with no player', () => {
      const player = usePlayerStore()
      expect(player.player).toBeNull()
    })

    it('starts with loading = false', () => {
      const player = usePlayerStore()
      expect(player.loading).toBe(false)
    })

    it('starts with error = null', () => {
      const player = usePlayerStore()
      expect(player.error).toBeNull()
    })

    it('starts with connected = false', () => {
      const player = usePlayerStore()
      expect(player.connected).toBe(false)
    })
  })

  // ─── Computed Properties (Default Values) ─────────────────────────────────

  describe('computed properties (default values)', () => {
    it('returns default username when no player', () => {
      const player = usePlayerStore()
      expect(player.username).toBe('Guest')
    })

    it('returns default displayName when no player', () => {
      const player = usePlayerStore()
      expect(player.displayName).toBe('Guest')
    })

    it('returns default rank when no player', () => {
      const player = usePlayerStore()
      expect(player.rank).toBe('Citizen')
    })

    it('returns default location when no player', () => {
      const player = usePlayerStore()
      expect(player.location).toBe('Downtown')
    })

    it('returns default level when no player', () => {
      const player = usePlayerStore()
      expect(player.level).toBe(1)
    })

    it('returns default energy values when no player', () => {
      const player = usePlayerStore()
      expect(player.energy).toBe(0)
      expect(player.maxEnergy).toBe(100)
      expect(player.energyPercent).toBe(0)
    })

    it('returns default health values when no player', () => {
      const player = usePlayerStore()
      expect(player.health).toBe(0)
      expect(player.maxHealth).toBe(100)
      expect(player.healthPercent).toBe(0)
    })

    it('returns default currency values when no player', () => {
      const player = usePlayerStore()
      expect(player.cash).toBe(0)
      expect(player.bank).toBe(0)
      expect(player.points).toBe(0)
      expect(player.diamonds).toBe(0)
    })

    it('returns isAdmin = false when no player', () => {
      const player = usePlayerStore()
      expect(player.isAdmin).toBe(false)
    })
  })

  // ─── fetchPlayer() ────────────────────────────────────────────────────────

  describe('fetchPlayer()', () => {
    it('fetches and sets player data', async () => {
      const mockData = makeUserResponse()
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      const result = await player.fetchPlayer()

      expect(result).toBe(true)
      expect(player.player).not.toBeNull()
      expect(player.username).toBe('testuser')
    })

    it('transforms API data correctly', async () => {
      const mockData = makeUserResponse({
        energy: 75,
        max_energy: 150,
        cash: 25000,
      })
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      await player.fetchPlayer()

      expect(player.energy).toBe(75)
      expect(player.maxEnergy).toBe(150)
      expect(player.cash).toBe(25000)
    })

    it('calculates energy percent correctly', async () => {
      const mockData = makeUserResponse({
        energy: 75,
        max_energy: 150,
      })
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      await player.fetchPlayer()

      expect(player.energyPercent).toBe(50)
    })

    it('sets error on fetch failure', async () => {
      mockGet.mockRejectedValueOnce(new Error('Network error'))

      const player = usePlayerStore()
      const result = await player.fetchPlayer()

      expect(result).toBe(false)
      expect(player.error).toBe('Failed to fetch player data')
    })

    it('sets loading state during fetch', async () => {
      const mockData = makeUserResponse()
      mockGet.mockImplementation(() => new Promise(resolve => setTimeout(() => resolve(mockData), 10)))

      const player = usePlayerStore()
      const promise = player.fetchPlayer()

      expect(player.loading).toBe(true)
      await promise
      expect(player.loading).toBe(false)
    })

    it('returns false if already loading', async () => {
      const mockData = makeUserResponse()
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      player.loading = true
      const result = await player.fetchPlayer()

      expect(result).toBe(false)
    })
  })

  // ─── updateStats() ────────────────────────────────────────────────────────

  describe('updateStats()', () => {
    it('updates stats locally', async () => {
      const mockData = makeUserResponse()
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      await player.fetchPlayer()

      player.updateStats({ energy: 50, cash: 1000 })

      expect(player.energy).toBe(50)
      expect(player.cash).toBe(1000)
    })

    it('does nothing when no player', () => {
      const player = usePlayerStore()
      player.updateStats({ energy: 50 })

      expect(player.energy).toBe(0)
    })
  })

  // ─── updateCurrency() ─────────────────────────────────────────────────────

  describe('updateCurrency()', () => {
    it('updates cash', async () => {
      const mockData = makeUserResponse({ cash: 5000 })
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      await player.fetchPlayer()

      player.updateCurrency(10000)

      expect(player.cash).toBe(10000)
    })

    it('updates bank', async () => {
      const mockData = makeUserResponse({ bank: 5000 })
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      await player.fetchPlayer()

      player.updateCurrency(undefined, 20000)

      expect(player.bank).toBe(20000)
    })

    it('updates both cash and bank', async () => {
      const mockData = makeUserResponse({ cash: 5000, bank: 10000 })
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      await player.fetchPlayer()

      player.updateCurrency(15000, 25000)

      expect(player.cash).toBe(15000)
      expect(player.bank).toBe(25000)
    })
  })

  // ─── clearPlayer() ────────────────────────────────────────────────────────

  describe('clearPlayer()', () => {
    it('clears all player state', async () => {
      const mockData = makeUserResponse()
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      await player.fetchPlayer()
      expect(player.player).not.toBeNull()

      player.clearPlayer()

      expect(player.player).toBeNull()
      expect(player.error).toBeNull()
      expect(player.lastFetched).toBeNull()
    })
  })

  // ─── Admin Detection ──────────────────────────────────────────────────────

  describe('admin detection', () => {
    it('isAdmin returns false when player has no admin role', async () => {
      const mockData = makeUserResponse()
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      await player.fetchPlayer()

      // Default roles is ['player'], so isAdmin should be false
      expect(player.player?.roles).toContain('player')
      expect(player.isAdmin).toBe(false)
    })
  })

  // ─── Status Flags ─────────────────────────────────────────────────────────

  describe('status flags', () => {
    it('isJailed defaults to false', async () => {
      const mockData = makeUserResponse()
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      await player.fetchPlayer()

      expect(player.isJailed).toBe(false)
    })

    it('isTraveling defaults to false', async () => {
      const mockData = makeUserResponse()
      mockGet.mockResolvedValueOnce(mockData)

      const player = usePlayerStore()
      await player.fetchPlayer()

      expect(player.isTraveling).toBe(false)
    })
  })
})
