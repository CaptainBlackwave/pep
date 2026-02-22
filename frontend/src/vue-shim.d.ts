declare module '*.vue' {
  import type { DefineComponent } from 'vue'
   
  const component: DefineComponent<object, object, unknown>
  export default component
}

declare module '@/services/api' {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const api: any
  export default api
}

declare module './stores/auth' {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  export function useAuthStore(): any
}
