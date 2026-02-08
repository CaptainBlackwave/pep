import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useChatStore = defineStore('chat', () => {
  // State
  const channels = ref([])
  const activeChannel = ref(null)
  const messages = ref([])
  const loading = ref(false)
  const sending = ref(false)
  const error = ref(null)
  const totalUnread = ref(0)

  // Polling
  let pollInterval = null

  // Computed
  const globalChannel = computed(() => channels.value.find(c => c.type === 'global' || c.slug === 'global'))
  const hasUnread = computed(() => totalUnread.value > 0)
  const recentMessages = computed(() => messages.value.slice(-50))

  // Actions
  async function fetchChannels() {
    try {
      const response = await api.get('/channels')
      channels.value = response.data.channels || response.data || []
      totalUnread.value = channels.value.reduce((sum, c) => sum + (c.unread_count || 0), 0)
    } catch (err) {
      console.error('Failed to fetch channels:', err)
    }
  }

  async function fetchMessages(channelId) {
    const channel = channelId || activeChannel.value?.id || globalChannel.value?.id
    if (!channel) return

    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/channels/${channel}/messages`)
      messages.value = response.data.messages || response.data || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch messages'
      console.error('Failed to fetch messages:', err)
    } finally {
      loading.value = false
    }
  }

  async function sendMessage(content, channelId) {
    const channel = channelId || activeChannel.value?.id || globalChannel.value?.id
    if (!channel || !content.trim()) return false

    sending.value = true

    try {
      const response = await api.post(`/channels/${channel}/messages`, { content: content.trim() })

      // Add message to local state
      const newMessage = response.data.message || response.data
      if (newMessage) {
        messages.value.push(newMessage)
      }

      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to send message'
      console.error('Failed to send message:', err)
      return false
    } finally {
      sending.value = false
    }
  }

  async function fetchUnreadCount() {
    try {
      const response = await api.get('/channels/unread-count')
      totalUnread.value = response.data.count || response.data.total || 0
    } catch (err) {
      console.error('Failed to fetch unread count:', err)
    }
  }

  function setActiveChannel(channel) {
    activeChannel.value = channel
    if (channel) {
      fetchMessages(channel.id)
    }
  }

  function addMessage(message) {
    // Add new message and keep buffer at 100 messages
    messages.value.push(message)
    if (messages.value.length > 100) {
      messages.value = messages.value.slice(-100)
    }
  }

  function startPolling(intervalMs = 5000) {
    stopPolling()
    pollInterval = setInterval(() => {
      if (activeChannel.value) {
        fetchMessages(activeChannel.value.id)
      }
    }, intervalMs)
  }

  function stopPolling() {
    if (pollInterval) {
      clearInterval(pollInterval)
      pollInterval = null
    }
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
    startPolling,
    stopPolling,
  }
})
