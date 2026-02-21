import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api.ts'
import type { User, LoginCredentials, RegisterData } from '@/types/user'
import type { ApiResponse } from '@/types/api'

// Re-export types for backward compatibility
export type { User, LoginCredentials, RegisterData } from '@/types/user'

/**
 * Auth store - manages authentication state and actions
 */
export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const isAuthenticated = computed(() => !!user.value)

  /**
   * Login user with credentials
   * Uses form-encoded POST to match backend API format
   */
  async function login(credentials: LoginCredentials): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      // Backend uses form-encoded POST to /api/?module=auth&action=login
      const params = new URLSearchParams()
      params.append('email', credentials.login || credentials.email || '')
      params.append('password', credentials.password)

      const response = await api.post<ApiResponse<{ user: User }>>(
        '/api/?module=auth&action=login',
        params
      )

      const data = response.data

      if (data.success && data.data?.user) {
        user.value = data.data.user
        localStorage.setItem('user', JSON.stringify(user.value))
        return true
      } else {
        error.value = data.message || data.alerts?.[0]?.text || 'Login failed'
        return false
      }
    } catch (err) {
      const axiosError = err as { response?: { data?: ApiResponse } }
      error.value = axiosError.response?.data?.message
        || axiosError.response?.data?.alerts?.[0]?.text
        || 'Login failed'
      return false
    } finally {
      loading.value = false
    }
  }

  /**
   * Register a new user
   * Uses form-encoded POST to match backend API format
   */
  async function register(userData: RegisterData): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      // Backend uses form-encoded POST to /api/?module=register&action=register
      const params = new URLSearchParams()
      params.append('username', userData.username)
      params.append('email', userData.email)
      params.append('password', userData.password)
      params.append('cpassword', userData.password_confirmation)

      const response = await api.post<ApiResponse>(
        '/api/?module=register&action=register',
        params
      )

      const data = response.data

      if (data.success) {
        // After registration, fetch the current user from session
        await fetchUser()
        return true
      } else {
        error.value = data.message || data.alerts?.[0]?.text || 'Registration failed'
        return false
      }
    } catch (err) {
      const axiosError = err as { response?: { data?: ApiResponse } }
      error.value = axiosError.response?.data?.message
        || axiosError.response?.data?.alerts?.[0]?.text
        || 'Registration failed'
      return false
    } finally {
      loading.value = false
    }
  }

  /**
   * Logout the current user
   */
  async function logout(): Promise<void> {
    try {
      await api.post('/api/?module=auth&action=logout')
    } catch {
      // Proceed with local logout regardless of API response
    } finally {
      user.value = null
      localStorage.removeItem('user')
    }
  }

  /**
   * Fetch the current authenticated user
   */
  async function fetchUser(): Promise<void> {
    try {
      const response = await api.get<ApiResponse<{ user: User }>>('/api/?module=auth&action=me')
      const data = response.data

      if (data.success && data.data?.user) {
        user.value = data.data.user
        localStorage.setItem('user', JSON.stringify(user.value))
      } else {
        user.value = null
        localStorage.removeItem('user')
      }
    } catch {
      user.value = null
      localStorage.removeItem('user')
    }
  }

  /**
   * Initialize auth state from localStorage and verify session
   */
  async function init(): Promise<void> {
    const storedUser = localStorage.getItem('user')
    if (storedUser) {
      try {
        user.value = JSON.parse(storedUser) as User
        // Verify the session is still active
        await fetchUser()
      } catch {
        user.value = null
        localStorage.removeItem('user')
      }
    }
  }

  /**
   * Clear any error message
   */
  function clearError(): void {
    error.value = null
  }

  return {
    // State
    user,
    loading,
    error,
    // Computed
    isAuthenticated,
    // Actions
    login,
    register,
    logout,
    fetchUser,
    init,
    clearError
  }
})
