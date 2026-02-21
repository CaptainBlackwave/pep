import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useChatStore } from '@/stores/chat'

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

const makeChannel = (overrides = {}) => ({
  id: 1,
  name: 'General',
  slug: 'general',
  type: 'public' as const,
  ...overrides,
})

const makeMessage = (overrides = {}) => ({
  id: 1,
  channel_id: 1,
  user_id: 1,
  username: 'testuser',
  content: 'Hello world',
  created_at: new Date().toISOString(),
  ...overrides,
})

describe('Chat Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.clearAllMocks()
    const chat = useChatStore()
    chat.clearAll()
  })

  // ─── Initial State ────────────────────────────────────────────────────────

  describe('initial state', () => {
    it('starts with empty channels', () => {
      const chat = useChatStore()
      expect(chat.channels).toEqual([])
    })

    it('starts with empty messages', () => {
      const chat = useChatStore()
      expect(chat.messages).toEqual([])
    })

    it('starts with loading = false', () => {
      const chat = useChatStore()
      expect(chat.loading).toBe(false)
    })

    it('starts with error = null', () => {
      const chat = useChatStore()
      expect(chat.error).toBeNull()
    })

    it('starts with connected = false', () => {
      const chat = useChatStore()
      expect(chat.connected).toBe(false)
    })

    it('starts with totalUnread = 0', () => {
      const chat = useChatStore()
      expect(chat.totalUnread).toBe(0)
    })
  })

  // ─── Computed Properties ──────────────────────────────────────────────────

  describe('computed properties', () => {
    it('hasUnread is false when totalUnread is 0', () => {
      const chat = useChatStore()
      expect(chat.hasUnread).toBe(false)
    })

    it('hasUnread is true when totalUnread > 0', () => {
      const chat = useChatStore()
      chat.totalUnread = 5
      expect(chat.hasUnread).toBe(true)
    })

    it('globalChannel returns undefined when no global channel', () => {
      const chat = useChatStore()
      expect(chat.globalChannel).toBeUndefined()
    })

    it('globalChannel finds global channel', async () => {
      const channelList = [makeChannel({ type: 'global', slug: 'global' })]
      mockGet.mockResolvedValueOnce({ data: { channels: channelList } })

      const chat = useChatStore()
      await chat.fetchChannels()

      expect(chat.globalChannel?.slug).toBe('global')
    })
  })

  // ─── fetchChannels() ──────────────────────────────────────────────────────

  describe('fetchChannels()', () => {
    it('fetches and sets channels', async () => {
      const channelList = [makeChannel(), makeChannel({ id: 2, name: 'Trade', slug: 'trade' })]
      mockGet.mockResolvedValueOnce({ data: { channels: channelList } })

      const chat = useChatStore()
      await chat.fetchChannels()

      expect(chat.channels.length).toBe(2)
      expect(chat.channels[0]!.name).toBe('General')
    })

    it('handles API response without channels wrapper', async () => {
      const channelList = [makeChannel()]
      mockGet.mockResolvedValueOnce({ data: channelList })

      const chat = useChatStore()
      await chat.fetchChannels()

      expect(chat.channels.length).toBe(1)
    })

    it('calculates total unread count', async () => {
      const channelList = [
        makeChannel({ unread_count: 5 }),
        makeChannel({ id: 2, unread_count: 3 }),
      ]
      mockGet.mockResolvedValueOnce({ data: { channels: channelList } })

      const chat = useChatStore()
      await chat.fetchChannels()

      expect(chat.totalUnread).toBe(8)
    })
  })

  // ─── fetchMessages() ──────────────────────────────────────────────────────

  describe('fetchMessages()', () => {
    it('fetches and sets messages', async () => {
      const messageList = [makeMessage(), makeMessage({ id: 2, content: 'Second message' })]
      mockGet.mockResolvedValueOnce({ data: { messages: messageList } })

      const chat = useChatStore()
      chat.activeChannel = makeChannel()
      await chat.fetchMessages()

      expect(chat.messages.length).toBe(2)
    })

    it('does nothing when no active channel', async () => {
      const chat = useChatStore()
      await chat.fetchMessages()

      expect(mockGet).not.toHaveBeenCalled()
    })

    it('sets loading state during fetch', async () => {
      const messageList = [makeMessage()]
      mockGet.mockImplementation(() => new Promise(resolve => setTimeout(() => resolve({ data: { messages: messageList } }), 10)))

      const chat = useChatStore()
      chat.activeChannel = makeChannel()
      const promise = chat.fetchMessages()

      expect(chat.loading).toBe(true)
      await promise
      expect(chat.loading).toBe(false)
    })
  })

  // ─── sendMessage() ────────────────────────────────────────────────────────

  describe('sendMessage()', () => {
    it('sends a message and adds it to messages', async () => {
      const newMessage = makeMessage({ id: 3, content: 'New message' })
      mockPost.mockResolvedValueOnce({ data: { message: newMessage } })

      const chat = useChatStore()
      chat.activeChannel = makeChannel()
      const result = await chat.sendMessage('New message')

      expect(result).toBe(true)
      expect(chat.messages.length).toBe(1)
    })

    it('returns false when no active channel', async () => {
      const chat = useChatStore()
      const result = await chat.sendMessage('Test message')

      expect(result).toBe(false)
    })

    it('returns false when content is empty', async () => {
      const chat = useChatStore()
      chat.activeChannel = makeChannel()
      const result = await chat.sendMessage('')

      expect(result).toBe(false)
    })

    it('returns false on send failure', async () => {
      mockPost.mockRejectedValueOnce(new Error('Network error'))

      const chat = useChatStore()
      chat.activeChannel = makeChannel()
      const result = await chat.sendMessage('Test message')

      expect(result).toBe(false)
    })
  })

  // ─── addMessage() ─────────────────────────────────────────────────────────

  describe('addMessage()', () => {
    it('adds a message to messages', () => {
      const chat = useChatStore()
      chat.addMessage(makeMessage())

      expect(chat.messages.length).toBe(1)
    })

    it('trims messages to 100', () => {
      const chat = useChatStore()

      // Add 105 messages
      for (let i = 0; i < 105; i++) {
        chat.addMessage(makeMessage({ id: i + 1 }))
      }

      expect(chat.messages.length).toBe(100)
    })
  })

  // ─── setActiveChannel() ───────────────────────────────────────────────────

  describe('setActiveChannel()', () => {
    it('sets the active channel', () => {
      const chat = useChatStore()
      const channel = makeChannel()
      chat.setActiveChannel(channel)

      expect(chat.activeChannel?.id).toBe(1)
    })

    it('clears active channel when set to null', () => {
      const chat = useChatStore()
      chat.activeChannel = makeChannel()
      chat.setActiveChannel(null)

      expect(chat.activeChannel).toBeNull()
    })
  })

  // ─── clearAll() ───────────────────────────────────────────────────────────

  describe('clearAll()', () => {
    it('clears all state', async () => {
      const channelList = [makeChannel()]
      mockGet.mockResolvedValueOnce({ data: { channels: channelList } })

      const chat = useChatStore()
      await chat.fetchChannels()
      chat.setActiveChannel(makeChannel())
      chat.totalUnread = 5

      chat.clearAll()

      expect(chat.channels).toEqual([])
      expect(chat.activeChannel).toBeNull()
      expect(chat.messages).toEqual([])
      expect(chat.totalUnread).toBe(0)
      expect(chat.error).toBeNull()
    })
  })
})
