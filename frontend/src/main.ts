/**
 * PBBG Vault Frontend Application Entry Point
 */

// Global styles
import './assets/main.css'

// Vue core
import { createApp } from 'vue'
import { createPinia } from 'pinia'

// Application
import App from './App.vue'
import router from './router'

// Stores
import { useAuthStore } from './stores/auth'

// Plugins
import errorLoggerPlugin, { setupGlobalErrorHandlers, setupAxiosErrorInterceptor } from './plugins/errorLogger'
import axios from 'axios'

// Create Vue application instance
const app = createApp(App)

// Create and use Pinia store
const pinia = createPinia()
app.use(pinia)

// Use Vue Router
app.use(router)

// Setup error logging
app.use(errorLoggerPlugin)
setupGlobalErrorHandlers()
setupAxiosErrorInterceptor(axios)

// Initialize auth store and mount app
const authStore = useAuthStore()
authStore.init().finally(() => {
  app.mount('#app')
})
