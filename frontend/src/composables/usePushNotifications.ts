import { ref } from 'vue'
import api from '@/services/api'

const pushSupported = ref(false)
const pushGranted = ref(false)

export function usePushNotifications() {
  let pendingInit = false

  async function init(_userId?: number | string) {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      return
    }

    pushSupported.value = true

    if (Notification.permission === 'granted') {
      await doSubscribe()
    }
  }

  async function requestPermission(_userId?: number | string) {
    if (pendingInit) return
    pendingInit = true
    try {
      const permission = await Notification.requestPermission()
      if (permission === 'granted') {
        pushGranted.value = true
        await doSubscribe()
      }
    } finally {
      pendingInit = false
    }
  }

  async function doSubscribe() {
    try {
      const registration = await navigator.serviceWorker.ready
      const existing = await registration.pushManager.getSubscription()
      if (existing) {
        pushGranted.value = true
        return
      }

      const { data } = await api.get<{ public_key: string }>('/api/v1/push/vapid-key')
      if (!data.public_key) return

      const key = urlBase64ToArrayBuffer(data.public_key)
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: key,
      })

      await api.post('/api/v1/community/push/subscribe', {
        endpoint: subscription.endpoint,
        public_key: arrayBufferToBase64(subscription.getKey('p256dh')),
        auth_token: arrayBufferToBase64(subscription.getKey('auth')),
      })
    } catch {
      // push setup failed silently
    }
  }

  return { pushSupported, pushGranted, init, requestPermission }
}

function urlBase64ToArrayBuffer(base64String: string): ArrayBuffer {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = window.atob(base64)
  const bytes = Uint8Array.from(rawData.split('').map((c) => c.charCodeAt(0)))

  return bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength) as ArrayBuffer
}

function arrayBufferToBase64(buffer: ArrayBuffer | null): string {
  if (!buffer) return ''
  const bytes = new Uint8Array(buffer)
  let binary = ''
  for (let i = 0; i < bytes.byteLength; i++) {
    binary += String.fromCharCode(bytes[i] ?? 0)
  }
  return btoa(binary)
}
