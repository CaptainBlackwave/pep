declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

declare module '@/services/api' {
  const api: any
  export default api
}

declare module './stores/auth' {
  export function useAuthStore(): any
}
