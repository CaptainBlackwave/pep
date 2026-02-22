import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { websocketService } from '@/services/websocket'
import type { ChatMessageEvent } from '@/types/websocket'
import type { ChatChannel, ChatMessage } from '@/types/chat'

// Re-export types for backward compatibility
export type { ChatChannel, ChatMessage } from '@/types/chat'

// API response interfaces
interface ChannelsResponse {
  channels: ChatChannel[]
}

interface MessagesResponse {
  messages: ChatMessage[]
}

interface UnreadCountResponse {
  count?: number
  total?: number
}

/**
 * Chat store - manages chat channels and messages with WebSocket
 */
export const useChatStore = defineStore('chat', () => {
  // State
  const channels = ref<ChatChannel[]>([])
  const activeChannel = ref<ChatChannel | null>(null)
  const messages = ref<ChatMessage[]>([])
  const loading = ref(false)
  const sending = ref(false)
  const error = ref<string | null>(null)
  const totalUnread = ref(0)
  const connected = ref(false)

  // WebSocket cleanup functions
  const wsUnsubscribers: (() => void)[] = []

  // Computed
  const globalChannel = computed(() =>
    channels.value.find(c => c.type === 'global' || c.slug === 'global')
  )
  const hasUnread = computed(() => totalUnread.value > 0)
  const recentMessages = computed(() => messages.value.slice(-50))

  /**
   * Fetch all chat channels
   */
  async function fetchChannels(): Promise<void> {
    try {
      const response = await api.get('/api/v1/channels')
      const data = response.data as ChannelsResponse | ChatChannel[]
      channels.value = ('channels' in data ? data.channels : data) || []
      totalUnread.value = channels.value.reduce((sum, c) => sum + (c.unread_count || 0), 0)
    } catch {
      // Silently fail - channels will be fetched on next connect
    }
  }

  /**
   * Fetch messages for a channel
   */
  async function fetchMessages(channelId?: number): Promise<void> {
    const channel = channelId || activeChannel.value?.id || globalChannel.value?.id
    if (!channel) return

    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/api/v1/channels/${channel}/messages`)
      const data = response.data as MessagesResponse | ChatMessage[]
      messages.value = ('messages' in data ? data.messages : data) || []
    } catch {
      error.value = 'Failed to fetch messages'
    } finally {
      loading.value = false
    }
  }

  /**
   * Send a message to a channel
   */
  async function sendMessage(content: string, channelId?: number): Promise<boolean> {
    const channel = channelId || activeChannel.value?.id || globalChannel.value?.id
    if (!channel || !content.trim()) return false

    sending.value = true

    try {
      const response = await api.post(`/api/v1/channels/${channel}/messages`, {
        content: content.trim()
      })

      // Message will be received via WebSocket, but add optimistically
      const newMessage = (response.data as { message?: ChatMessage }).message || response.data as ChatMessage
      if (newMessage) {
        addMessage(newMessage)
      }

      return true
    } catch {
      error.value = 'Failed to send message'
      return false
    } finally {
      sending.value = false
    }
  }

  /**
   * Fetch unread message count
   */
  async function fetchUnreadCount(): Promise<void> {
    try {
      const response = await api.get('/api/v1/channels/unread-count')
      const data = response.data as UnreadCountResponse
      totalUnread.value = data.count || data.total || 0
    } catch {
      // Silently fail - unread count will be updated on next fetch
    }
  }

  /**
   * Set active channel
   */
  function setActiveChannel(channel: ChatChannel | null): void {
    activeChannel.value = channel
    if (channel) {
      fetchMessages(channel.id)
      // Subscribe to channel via WebSocket
      subscribeToChannel(channel.id)
    }
  }

  /**
   * Add a message to the local state
   */
  function addMessage(message: ChatMessage): void {
    messages.value.push(message)
    // Keep buffer at 100 messages
    if (messages.value.length > 100) {
      messages.value = messages.value.slice(-100)
    }
  }

  /**
   * Handle incoming chat message from WebSocket
   */
  function handleChatMessage(data: ChatMessageEvent): void {
    // Only add if it's for the current channel
    if (activeChannel.value && data.channel_id === activeChannel.value.id) {
      addMessage({
        id: data.id,
        channel_id: data.channel_id,
        user_id: data.user_id,
        username: data.username,
        content: data.content,
        created_at: data.created_at
      })
    }

    // Update unread count if not current channel
    if (!activeChannel.value || data.channel_id !== activeChannel.value.id) {
      totalUnread.value++
    }
  }

  /**
   * Handle unread count update from WebSocket
   */
  function handleUnreadCount(data: { count: number }): void {
    totalUnread.value = data.count
  }

  /**
   * Subscribe to a channel via WebSocket
   */
  function subscribeToChannel(channelId: number): void {
    websocketService.subscribe(`chat.${channelId}`)
  }

  /**
   * Unsubscribe from a channel
   */
  function unsubscribeFromChannel(channelId: number): void {
    websocketService.unsubscribe(`chat.${channelId}`)
  }

  /**
   * Initialize WebSocket listeners
   */
  function initWebSocket(): void {
    // Listen for chat messages
    const unsubChat = websocketService.on<ChatMessageEvent>('chat-message', handleChatMessage)
    wsUnsubscribers.push(unsubChat)

    // Listen for unread count updates
    const unsubUnread = websocketService.on<{ count: number }>('unread-count', handleUnreadCount)
    wsUnsubscribers.push(unsubUnread)

    // Subscribe to global chat
    if (globalChannel.value) {
      subscribeToChannel(globalChannel.value.id)
    }

    connected.value = true
  }

  /**
   * Connect to WebSocket and start real-time updates
   */
  async function connect(userId: number): Promise<void> {
    try {
      await websocketService.connect()

      // Subscribe to user's private channel for unread counts
      websocketService.subscribe(`private-user.${userId}`)

      // Initialize WebSocket listeners
      initWebSocket()

      // Fetch initial data
      await fetchChannels()
    } catch {
      // Fall back to polling if WebSocket fails
      startPolling()
    }
  }

  /**
   * Start polling for messages (fallback)
   */
  function startPolling(intervalMs = 5000): void {
    const pollInterval = setInterval(() => {
      if (activeChannel.value) {
        fetchMessages(activeChannel.value.id)
      }
      fetchUnreadCount()
    }, intervalMs)

    // Store for cleanup
    wsUnsubscribers.push(() => clearInterval(pollInterval))
  }

  /**
   * Disconnect and cleanup
   */
  function disconnect(): void {
    // Cleanup WebSocket listeners
    wsUnsubscribers.forEach(unsub => unsub())
    wsUnsubscribers.length = 0

    // Unsubscribe from all channels
    channels.value.forEach(channel => {
      unsubscribeFromChannel(channel.id)
    })

    connected.value = false
  }

  /**
   * Clear all state
   */
  function clearAll(): void {
    disconnect()
    channels.value = []
    activeChannel.value = null
    messages.value = []
    totalUnread.value = 0
    error.value = null
  }

  return {
    // State
    channels,
    activeChannel,
    messages,
    loading,
    sending,
    error,
    totalUnread,
    connected,

    // Computed
    globalChannel,
    hasUnread,
    recentMessages,

    // Actions
    fetchChannels,
    fetchMessages,
    sendMessage,
    fetchUnreadCount,
    setActiveChannel,
    addMessage,
    connect,
    disconnect,
    clearAll,
  }
})
