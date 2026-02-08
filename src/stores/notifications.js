import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useNotificationsStore = defineStore('notifications', () => {
  // State
  const notifications = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const error = ref(null)

  // Polling interval
  let pollInterval = null

  // Computed
  const hasUnread = computed(() => unreadCount.value > 0)
  const unreadNotifications = computed(() => notifications.value.filter(n => !n.read_at))
  const readNotifications = computed(() => notifications.value.filter(n => n.read_at))

  // Actions
  async function fetchNotifications() {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/notifications')
      notifications.value = response.data.notifications || response.data || []
      unreadCount.value = notifications.value.filter(n => !n.read_at).length
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch notifications'
      console.error('Failed to fetch notifications:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchUnreadCount() {
    try {
      const response = await api.get('/notifications/unread-count')
      unreadCount.value = response.data.count || response.data.unread_count || 0
    } catch (err) {
      console.error('Failed to fetch unread count:', err)
    }
  }

  async function fetchRecent() {
    try {
      const response = await api.get('/notifications/recent')
      notifications.value = response.data.notifications || response.data || []
      unreadCount.value = notifications.value.filter(n => !n.read_at).length
    } catch (err) {
      console.error('Failed to fetch recent notifications:', err)
    }
  }

  async function markAsRead(id) {
    try {
      await api.post(`/notifications/${id}/read`)

      const notification = notifications.value.find(n => n.id === id)
      if (notification && !notification.read_at) {
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (err) {
      console.error('Failed to mark notification as read:', err)
    }
  }

  async function markAllAsRead() {
    try {
      await api.post('/notifications/mark-all-read')

      notifications.value.forEach(n => {
        if (!n.read_at) {
          n.read_at = new Date().toISOString()
        }
      })
      unreadCount.value = 0
    } catch (err) {
      console.error('Failed to mark all as read:', err)
    }
  }

  async function deleteNotification(id) {
    try {
      await api.delete(`/notifications/${id}`)

      const index = notifications.value.findIndex(n => n.id === id)
      if (index !== -1) {
        const notification = notifications.value[index]
        if (notification && !notification.read_at) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
        notifications.value.splice(index, 1)
      }
    } catch (err) {
      console.error('Failed to delete notification:', err)
    }
  }

  async function clearRead() {
    try {
      await api.delete('/notifications/read/clear')
      notifications.value = notifications.value.filter(n => !n.read_at)
    } catch (err) {
      console.error('Failed to clear read notifications:', err)
    }
  }

  function startPolling(intervalMs = 60000) {
    stopPolling()
    fetchUnreadCount()
    pollInterval = setInterval(fetchUnreadCount, intervalMs)
  }

  function stopPolling() {
    if (pollInterval) {
      clearInterval(pollInterval)
      pollInterval = null
    }
  }

  return {
    // State
    notifications,
    unreadCount,
    loading,
    error,

    // Computed
    hasUnread,
    unreadNotifications,
    readNotifications,

    // Actions
    fetchNotifications,
    fetchUnreadCount,
    fetchRecent,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    clearRead,
    startPolling,
    stopPolling,
  }
})
