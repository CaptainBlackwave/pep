import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { websocketService } from '@/services/websocket'
import type { NotificationEvent } from '@/types/websocket'
import type { Notification } from '@/types/notification'
import type { NotificationsListResponse } from '@/types/api'

// Re-export types for backward compatibility
export type { Notification } from '@/types/notification'

/**
 * Notifications store - manages notifications with WebSocket
 */
export const useNotificationsStore = defineStore('notifications', () => {
  // State
  const notifications = ref<Notification[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const connected = ref(false)

  // WebSocket cleanup functions
  const wsUnsubscribers: (() => void)[] = []

  // Computed
  const hasUnread = computed(() => unreadCount.value > 0)
  const unreadNotifications = computed(() =>
    notifications.value.filter(n => !n.read_at)
  )
  const readNotifications = computed(() =>
    notifications.value.filter(n => n.read_at)
  )

  type CommunityNotificationsResponse = {
    data?: Record<string, unknown>[]
    meta?: { stats?: { unread?: number } }
    notifications?: Notification[]
    unread_count?: number
    count?: number
  }

  function stringValue(value: unknown, fallback = ''): string {
    return typeof value === 'string' ? value : fallback
  }

  function mapCommunityNotification(item: Record<string, unknown>): Notification {
    const sourceId = String(item.id ?? item.slug ?? '0')
    const slug = stringValue(item.slug, sourceId)

    return {
      id: Number(sourceId.replace(/\D+/g, '') || 0),
      type: String(item.category_slug ?? item.type ?? 'notification'),
      title: String(item.title ?? ''),
      message: String(item.text ?? item.body ?? item.message ?? ''),
      link: String(item.href ?? item.detail_href ?? item.link ?? ''),
      data: { slug, source: stringValue(item.source), source_id: sourceId },
      read_at: item.unread ? null : String(item.read_at ?? item.date ?? new Date().toISOString()),
      created_at: String(item.date ?? item.created_at ?? new Date().toISOString()),
    }
  }

  function readKeyForNotification(notification: Notification | undefined, fallback: number): string {
    const slug = notification?.data?.slug
    const source = notification?.data?.source

    if (slug) {
      return source ? `${source}_${slug}` : String(slug)
    }

    return String(fallback)
  }

  function unreadCountFromResponse(data: CommunityNotificationsResponse, fallback: number): number {
    return data.meta?.stats?.unread ?? data.unread_count ?? data.count ?? fallback
  }

  /**
   * Fetch all notifications
   */
  async function fetchNotifications(): Promise<void> {
    if (!useAuthStore().isAuthenticated) return
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/api/v1/community/notifications', { cacheTTL: 0 })
      const data = response.data as NotificationsListResponse | CommunityNotificationsResponse | Notification[]
      if (Array.isArray(data)) {
        notifications.value = data as Notification[]
        unreadCount.value = notifications.value.filter(n => !n.read_at).length
      } else if ('data' in data && Array.isArray(data.data)) {
        notifications.value = data.data.map(mapCommunityNotification)
        unreadCount.value = unreadCountFromResponse(data, notifications.value.filter(n => !n.read_at).length)
      } else {
        const notifData = 'notifications' in data ? data.notifications : []
        notifications.value = (notifData as Notification[]) || []
        unreadCount.value = unreadCountFromResponse(data as CommunityNotificationsResponse, notifications.value.filter(n => !n.read_at).length)
      }
    } catch {
      error.value = 'Failed to fetch notifications'
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch unread count only
   */
  async function fetchUnreadCount(): Promise<void> {
    if (!useAuthStore().isAuthenticated) return
    try {
      const response = await api.get('/api/v1/community/notifications', {
        cacheTTL: 0,
        params: { status: 'unread', limit: 1 },
      })
      const data = response.data as CommunityNotificationsResponse
      unreadCount.value = unreadCountFromResponse(data, 0)
    } catch {
      // Silently fail - unread count will be updated on next fetch
    }
  }

  /**
   * Fetch recent notifications
   */
  async function fetchRecent(): Promise<void> {
    try {
      const response = await api.get('/api/v1/community/notifications', {
        cacheTTL: 0,
        params: { limit: 10 },
      })
      const data = response.data as CommunityNotificationsResponse
      notifications.value = (data.data ?? []).map(mapCommunityNotification)
      unreadCount.value = unreadCountFromResponse(data, notifications.value.filter(n => !n.read_at).length)
    } catch {
      // Silently fail - recent notifications will be fetched on next load
    }
  }

  /**
   * Mark a notification as read
   */
  async function markAsRead(id: number): Promise<void> {
    try {
      const notification = notifications.value.find(n => n.id === id)
      await api.post(`/api/v1/community/notifications/${encodeURIComponent(readKeyForNotification(notification, id))}/read`)

      if (notification && !notification.read_at) {
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch {
      // Silently fail - notification will be marked as read on server
    }
  }

  /**
   * Mark all notifications as read
   */
  async function markAllAsRead(): Promise<void> {
    try {
      await api.post('/api/v1/community/notifications/read-all')

      notifications.value.forEach(n => {
        if (!n.read_at) {
          n.read_at = new Date().toISOString()
        }
      })
      unreadCount.value = 0
    } catch {
      // Silently fail - notifications will be marked as read on server
    }
  }

  /**
   * Delete a notification
   */
  async function deleteNotification(id: number): Promise<void> {
    try {
      const notification = notifications.value.find(n => n.id === id)
      await api.delete(`/api/v1/community/notifications/${encodeURIComponent(readKeyForNotification(notification, id))}`)

      const index = notifications.value.findIndex(n => n.id === id)
      if (index !== -1) {
        const notification = notifications.value[index]
        if (notification && !notification.read_at) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
        notifications.value.splice(index, 1)
      }
    } catch {
      // Silently fail - notification will be deleted on server
    }
  }

  /**
   * Clear all read notifications
   */
  async function clearRead(): Promise<void> {
    try {
      await api.delete('/api/v1/community/notifications/read/clear')
      notifications.value = notifications.value.filter(n => !n.read_at)
    } catch {
      // Silently fail - read notifications will be cleared on server
    }
  }

  /**
   * Add a new notification (from WebSocket)
   */
  function addNotification(notification: Notification): void {
    notifications.value.unshift(notification)
    if (!notification.read_at) {
      unreadCount.value++
    }
  }

  /**
   * Handle incoming notification from WebSocket
   */
  function handleNotification(data: NotificationEvent): void {
    addNotification({
      id: data.id,
      type: data.type,
      title: data.title,
      message: data.message,
      link: data.link,
      read_at: null,
      created_at: data.created_at
    })
  }

  /**
   * Handle unread count update from WebSocket
   */
  function handleUnreadCount(data: { count: number }): void {
    unreadCount.value = data.count
  }

  /**
   * Initialize WebSocket listeners
   */
  function initWebSocket(): void {
    // Listen for new notifications
    const unsubNotification = websocketService.on<NotificationEvent>('notification', handleNotification)
    wsUnsubscribers.push(unsubNotification)

    // Listen for unread count updates
    const unsubUnread = websocketService.on<{ count: number }>('unread-count', handleUnreadCount)
    wsUnsubscribers.push(unsubUnread)

    connected.value = true
  }

  /**
   * Connect to WebSocket and start real-time updates
   */
  async function connect(userId: number): Promise<void> {
    try {
      await websocketService.connect()

      // Subscribe to user's private channel
      websocketService.subscribe(`private-user.${userId}`)

      // Initialize WebSocket listeners
      initWebSocket()

      // Fetch initial data
      await fetchNotifications()
    } catch {
      // Fall back to polling if WebSocket fails
      startPolling()
    }
  }

  /**
   * Start polling for notifications (fallback)
   */
  function startPolling(intervalMs = 60000): void {
    const pollInterval = setInterval(() => {
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
    connected.value = false
  }

  /**
   * Clear all state
   */
  function clearAll(): void {
    disconnect()
    notifications.value = []
    unreadCount.value = 0
    error.value = null
  }

  return {
    // State
    notifications,
    unreadCount,
    loading,
    error,
    connected,

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
    addNotification,
    connect,
    disconnect,
    clearAll,
  }
})
