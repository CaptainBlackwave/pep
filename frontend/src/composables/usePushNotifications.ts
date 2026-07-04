import { ref } from 'vue'
import api from '@/services/api'

const pushSupported = ref(false)
const pushGranted = ref(false)

export function usePushNotifications() {
  async function init(userId: number | string) {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      return
    }

    pushSupported.value = true

    try {
      const registration = await navigator.serviceWorker.ready
      const existing = await registration.pushManager.getSubscription()

      if (existing) {
        pushGranted.value = true
        return
      }

      const { data } = await api.get<{ public_key: string }>('/api/v1/push/vapid-key')
      if (!data.public_key) return

      const permission = await Notification.requestPermission()
      if (permission !== 'granted') return

      pushGranted.value = true
      const key = urlBase64ToUint8Array(data.public_key)
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: key,
      })

      await api.post('/api/v1/push/subscribe', {
        endpoint: subscription.endpoint,
        public_key: arrayBufferToBase64(subscription.getKey('p256dh')),
        auth_token: arrayBufferToBase64(subscription.getKey('auth')),
      })
    } catch {
      // push setup failed silently
    }
  }

  return { pushSupported, pushGranted, init }
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = window.atob(base64)
  return Uint8Array.from(rawData.split('').map((c) => c.charCodeAt(0)))
}

function arrayBufferToBase64(buffer: ArrayBuffer | null): string {
  if (!buffer) return ''
  const bytes = new Uint8Array(buffer)
  let binary = ''
  for (let i = 0; i < bytes.byteLength; i++) {
    binary += String.fromCharCode(bytes[i])
  }
  return btoa(binary)
}
