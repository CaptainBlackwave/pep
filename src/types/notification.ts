/**
 * Notification Type Definitions for OpenPBBG
 */

/**
 * Notification types
 */
export type NotificationType = 'info' | 'success' | 'warning' | 'error'

/**
 * Notification interface
 */
export interface Notification {
  id: number
  type: NotificationType
  title: string
  message: string
  link?: string
  read_at: string | null
  created_at: string
}

/**
 * Notification response from API
 */
export interface NotificationsResponse {
  notifications: Notification[]
}

/**
 * Unread count response from API
 */
export interface NotificationUnreadCountResponse {
  count: number
  unread_count: number
}

/**
 * Notification creation payload (for system/admin use)
 */
export interface CreateNotificationRequest {
  user_id: number
  type: NotificationType
  title: string
  message: string
  link?: string
}

/**
 * Bulk notification request
 */
export interface BulkNotificationRequest {
  user_ids: number[]
  type: NotificationType
  title: string
  message: string
  link?: string
}

/**
 * Notification preferences
 */
export interface NotificationPreferences {
  enabled: boolean
  email: boolean
  push: boolean
  sound: boolean
  types: {
    system: boolean
    game: boolean
    social: boolean
    gang: boolean
    market: boolean
  }
}

/**
 * Toast notification for UI display
 */
export interface ToastNotification {
  id: string | number
  type: NotificationType
  title?: string
  message: string
  duration?: number
  dismissible?: boolean
}

/**
 * Notification category for filtering
 */
export type NotificationCategory = 'system' | 'game' | 'social' | 'gang' | 'market' | 'combat'

/**
 * Categorized notification
 */
export interface CategorizedNotification extends Notification {
  category: NotificationCategory
}
