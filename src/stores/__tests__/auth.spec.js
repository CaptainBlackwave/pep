import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

// Mock the api module
vi.mock('@/services/api', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  },
}))

import api from '@/services/api'

// Helper to build a successful login/register API response
const makeSuccessResponse = (user = { id: 1, username: 'testuser', email: 'test@example.com' }) => ({
  data: { success: true, data: { user } },
})

const makeErrorResponse = (message = 'Invalid credentials') => ({
  data: { success: false, message },
})

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  afterEach(() => {
    localStorage.clear()
  })

  // ─── Initial State ────────────────────────────────────────────────────────

  describe('initial state', () => {
    it('starts with no user', () => {
      const auth = useAuthStore()
      expect(auth.user).toBeNull()
    })

    it('starts with loading = false', () => {
      const auth = useAuthStore()
      expect(auth.loading).toBe(false)
    })

    it('starts with error = null', () => {
      const auth = useAuthStore()
      expect(auth.error).toBeNull()
    })

    it('isAuthenticated is false when no user', () => {
      const auth = useAuthStore()
      expect(auth.isAuthenticated).toBe(false)
    })
  })

  // ─── login() ─────────────────────────────────────────────────────────────

  describe('login()', () => {
    it('returns true and sets user on successful login', async () => {
      const user = { id: 1, username: 'testuser', email: 'test@example.com' }
      api.post.mockResolvedValueOnce(makeSuccessResponse(user))

      const auth = useAuthStore()
      const result = await auth.login({ email: 'test@example.com', password: 'password123' })

      expect(result).toBe(true)
      expect(auth.user).toEqual(user)
      expect(auth.isAuthenticated).toBe(true)
      expect(auth.error).toBeNull()
    })

    it('persists user to localStorage on success', async () => {
      const user = { id: 1, username: 'testuser', email: 'test@example.com' }
      api.post.mockResolvedValueOnce(makeSuccessResponse(user))

      const auth = useAuthStore()
      await auth.login({ email: 'test@example.com', password: 'password123' })

      expect(JSON.parse(localStorage.getItem('user'))).toEqual(user)
    })

    it('posts to the correct endpoint with form-encoded data', async () => {
      api.post.mockResolvedValueOnce(makeSuccessResponse())

      const auth = useAuthStore()
      await auth.login({ email: 'test@example.com', password: 'secret' })

      expect(api.post).toHaveBeenCalledWith(
        '/api/?module=auth&action=login',
        expect.any(URLSearchParams)
      )

      // Verify the URLSearchParams contain the right fields
      const params = api.post.mock.calls[0][1]
      expect(params.get('email')).toBe('test@example.com')
      expect(params.get('password')).toBe('secret')
    })

    it('returns false and sets error on failed login (API success=false)', async () => {
      api.post.mockResolvedValueOnce(makeErrorResponse('Invalid credentials'))

      const auth = useAuthStore()
      const result = await auth.login({ email: 'bad@example.com', password: 'wrong' })

      expect(result).toBe(false)
      expect(auth.user).toBeNull()
      expect(auth.error).toBe('Invalid credentials')
    })

    it('returns false and sets error on network error', async () => {
      api.post.mockRejectedValueOnce({
        response: { data: { message: 'Server error' } },
      })

      const auth = useAuthStore()
      const result = await auth.login({ email: 'test@example.com', password: 'password' })

      expect(result).toBe(false)
      expect(auth.error).toBe('Server error')
    })

    it('falls back to "Login failed" when no error message provided', async () => {
      api.post.mockResolvedValueOnce({ data: { success: false } })

      const auth = useAuthStore()
      await auth.login({ email: 'x@x.com', password: 'y' })

      expect(auth.error).toBe('Login failed')
    })

    it('reads error from alerts array when present', async () => {
      api.post.mockResolvedValueOnce({
        data: { success: false, alerts: [{ text: 'Account banned' }] },
      })

      const auth = useAuthStore()
      await auth.login({ email: 'x@x.com', password: 'y' })

      expect(auth.error).toBe('Account banned')
    })

    it('resets loading to false after success', async () => {
      api.post.mockResolvedValueOnce(makeSuccessResponse())

      const auth = useAuthStore()
      await auth.login({ email: 'test@example.com', password: 'password' })

      expect(auth.loading).toBe(false)
    })

    it('resets loading to false after failure', async () => {
      api.post.mockResolvedValueOnce(makeErrorResponse())

      const auth = useAuthStore()
      await auth.login({ email: 'bad@example.com', password: 'wrong' })

      expect(auth.loading).toBe(false)
    })

    it('clears previous error before a new login attempt', async () => {
      api.post.mockResolvedValueOnce(makeErrorResponse('First error'))
      const auth = useAuthStore()
      await auth.login({ email: 'x@x.com', password: 'y' })
      expect(auth.error).toBe('First error')

      api.post.mockResolvedValueOnce(makeSuccessResponse())
      await auth.login({ email: 'test@example.com', password: 'password' })
      expect(auth.error).toBeNull()
    })
  })

  // ─── register() ──────────────────────────────────────────────────────────

  describe('register()', () => {
    const userData = {
      username: 'newuser',
      email: 'new@example.com',
      password: 'password123',
      password_confirmation: 'password123',
    }

    it('returns true on successful registration', async () => {
      const user = { id: 2, username: 'newuser', email: 'new@example.com' }
      // register POST + fetchUser GET
      api.post.mockResolvedValueOnce({ data: { success: true } })
      api.get.mockResolvedValueOnce(makeSuccessResponse(user))

      const auth = useAuthStore()
      const result = await auth.register(userData)

      expect(result).toBe(true)
    })

    it('calls fetchUser after successful registration', async () => {
      const user = { id: 2, username: 'newuser', email: 'new@example.com' }
      api.post.mockResolvedValueOnce({ data: { success: true } })
      api.get.mockResolvedValueOnce(makeSuccessResponse(user))

      const auth = useAuthStore()
      await auth.register(userData)

      expect(api.get).toHaveBeenCalledWith('/api/?module=auth&action=me')
      expect(auth.user).toEqual(user)
    })

    it('posts to the correct endpoint with form-encoded data', async () => {
      api.post.mockResolvedValueOnce({ data: { success: true } })
      api.get.mockResolvedValueOnce(makeSuccessResponse())

      const auth = useAuthStore()
      await auth.register(userData)

      expect(api.post).toHaveBeenCalledWith(
        '/api/?module=register&action=register',
        expect.any(URLSearchParams)
      )

      const params = api.post.mock.calls[0][1]
      expect(params.get('username')).toBe('newuser')
      expect(params.get('email')).toBe('new@example.com')
      expect(params.get('password')).toBe('password123')
      expect(params.get('cpassword')).toBe('password123')
    })

    it('returns false and sets error on failed registration', async () => {
      api.post.mockResolvedValueOnce(makeErrorResponse('Email already taken'))

      const auth = useAuthStore()
      const result = await auth.register(userData)

      expect(result).toBe(false)
      expect(auth.error).toBe('Email already taken')
    })

    it('returns false and sets error on network error', async () => {
      api.post.mockRejectedValueOnce({
        response: { data: { message: 'Server unavailable' } },
      })

      const auth = useAuthStore()
      const result = await auth.register(userData)

      expect(result).toBe(false)
      expect(auth.error).toBe('Server unavailable')
    })

    it('falls back to "Registration failed" when no error message provided', async () => {
      api.post.mockResolvedValueOnce({ data: { success: false } })

      const auth = useAuthStore()
      await auth.register(userData)

      expect(auth.error).toBe('Registration failed')
    })

    it('resets loading to false after success', async () => {
      api.post.mockResolvedValueOnce({ data: { success: true } })
      api.get.mockResolvedValueOnce(makeSuccessResponse())

      const auth = useAuthStore()
      await auth.register(userData)

      expect(auth.loading).toBe(false)
    })

    it('resets loading to false after failure', async () => {
      api.post.mockResolvedValueOnce(makeErrorResponse())

      const auth = useAuthStore()
      await auth.register(userData)

      expect(auth.loading).toBe(false)
    })
  })

  // ─── logout() ────────────────────────────────────────────────────────────

  describe('logout()', () => {
    it('clears user and localStorage on logout', async () => {
      api.post.mockResolvedValueOnce(makeSuccessResponse())
      const auth = useAuthStore()
      await auth.login({ email: 'test@example.com', password: 'password' })
      expect(auth.user).not.toBeNull()

      api.post.mockResolvedValueOnce({}) // logout endpoint
      await auth.logout()

      expect(auth.user).toBeNull()
      expect(auth.isAuthenticated).toBe(false)
      expect(localStorage.getItem('user')).toBeNull()
    })

    it('calls the logout API endpoint', async () => {
      api.post.mockResolvedValueOnce({})
      const auth = useAuthStore()
      await auth.logout()

      expect(api.post).toHaveBeenCalledWith('/api/?module=auth&action=logout')
    })

    it('still clears user even if logout API call fails', async () => {
      api.post.mockResolvedValueOnce(makeSuccessResponse())
      const auth = useAuthStore()
      await auth.login({ email: 'test@example.com', password: 'password' })

      api.post.mockRejectedValueOnce(new Error('Network error'))
      await auth.logout()

      expect(auth.user).toBeNull()
      expect(localStorage.getItem('user')).toBeNull()
    })
  })

  // ─── fetchUser() ─────────────────────────────────────────────────────────

  describe('fetchUser()', () => {
    it('sets user from API response', async () => {
      const user = { id: 1, username: 'testuser', email: 'test@example.com' }
      api.get.mockResolvedValueOnce(makeSuccessResponse(user))

      const auth = useAuthStore()
      await auth.fetchUser()

      expect(auth.user).toEqual(user)
      expect(JSON.parse(localStorage.getItem('user'))).toEqual(user)
    })

    it('clears user when API returns success=false', async () => {
      api.get.mockResolvedValueOnce({ data: { success: false } })

      const auth = useAuthStore()
      auth.user = { id: 1 } // pre-set a user
      await auth.fetchUser()

      expect(auth.user).toBeNull()
      expect(localStorage.getItem('user')).toBeNull()
    })

    it('clears user on network error', async () => {
      api.get.mockRejectedValueOnce(new Error('Network error'))

      const auth = useAuthStore()
      auth.user = { id: 1 }
      await auth.fetchUser()

      expect(auth.user).toBeNull()
    })
  })

  // ─── init() ──────────────────────────────────────────────────────────────

  describe('init()', () => {
    it('restores user from localStorage and verifies session', async () => {
      const storedUser = { id: 1, username: 'testuser', email: 'test@example.com' }
      localStorage.setItem('user', JSON.stringify(storedUser))
      api.get.mockResolvedValueOnce(makeSuccessResponse(storedUser))

      const auth = useAuthStore()
      await auth.init()

      expect(auth.user).toEqual(storedUser)
    })

    it('does nothing when localStorage has no user', async () => {
      const auth = useAuthStore()
      await auth.init()

      expect(auth.user).toBeNull()
      expect(api.get).not.toHaveBeenCalled()
    })

    it('clears user when stored session is invalid (fetchUser fails)', async () => {
      localStorage.setItem('user', JSON.stringify({ id: 1 }))
      api.get.mockRejectedValueOnce(new Error('Unauthorized'))

      const auth = useAuthStore()
      await auth.init()

      expect(auth.user).toBeNull()
    })

    it('clears user when localStorage contains invalid JSON', async () => {
      localStorage.setItem('user', 'not-valid-json{{{')

      const auth = useAuthStore()
      await auth.init()

      expect(auth.user).toBeNull()
      expect(localStorage.getItem('user')).toBeNull()
    })
  })
})
