/**
 * API Type Definitions for OpenPBBG
 * Central location for all API-related types
 */

/**
 * Generic API response wrapper
 */
export interface ApiResponse<T = unknown> {
  success: boolean
  data?: T
  message?: string
  alerts?: ApiAlert[]
}

/**
 * API alert/notification message
 */
export interface ApiAlert {
  text: string
  type?: 'info' | 'success' | 'warning' | 'error'
}

/**
 * Paginated API response
 */
export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  per_page: number
  total: number
  last_page: number
  from: number
  to: number
  has_more_pages: boolean
}

/**
 * API error response
 */
export interface ApiError {
  message: string
  errors?: Record<string, string[]>
  code?: string | number
  status?: number
}

/**
 * Generic API request options
 */
export interface ApiRequestOptions {
  params?: Record<string, unknown>
  headers?: Record<string, string>
  signal?: AbortSignal
}

/**
 * Login request payload
 */
export interface LoginRequest {
  email: string
  password: string
  remember?: boolean
}

/**
 * Login response data
 */
export interface LoginResponse {
  user: User
  token?: string
}

/**
 * Registration request payload
 */
export interface RegisterRequest {
  username: string
  email: string
  password: string
  password_confirmation: string
}

/**
 * Password reset request payload
 */
export interface ForgotPasswordRequest {
  email: string
}

/**
 * Password reset confirmation payload
 */
export interface ResetPasswordRequest {
  token: string
  email: string
  password: string
  password_confirmation: string
}

/**
 * User data returned from API
 */
export interface User {
  id: number
  username: string
  name?: string
  email: string
  avatar?: string
  roles?: string[]
  permissions?: string[]
}

/**
 * Generic single item response
 */
export interface SingleItemResponse<T> {
  item: T
}

/**
 * Generic list response
 */
export interface ListResponse<T> {
  items: T[]
  total?: number
}

/**
 * Generic count response
 */
export interface CountResponse {
  count: number
  total?: number
}

/**
 * Success response with message
 */
export interface SuccessResponse {
  success: true
  message: string
}

/**
 * Delete response
 */
export interface DeleteResponse {
  success: boolean
  message?: string
}

/**
 * Re-export user type for convenience
 */
export type { User as ApiUser }
