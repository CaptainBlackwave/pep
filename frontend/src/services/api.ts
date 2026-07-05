import axios, { type AxiosInstance, type AxiosResponse, type InternalAxiosRequestConfig, type AxiosRequestConfig } from 'axios'

/**
 * API Service Configuration
 * Handles HTTP communication with the backend with:
 * - Request cancellation (AbortController)
 * - Request deduplication
 * - Response caching
 */

// The Vite dev server proxies /api/* to the backend (see vite.config.ts).
// In production, VITE_API_URL can be set to the backend's full URL.
const baseURL = import.meta.env.VITE_API_URL ?? ''

/**
 * Cache entry structure
 */
interface CacheEntry<T = unknown> {
  data: T
  timestamp: number
  etag?: string
}

/**
 * Pending request tracker for deduplication
 */
const pendingRequests = new Map<string, Promise<AxiosResponse>>()

/**
 * Response cache for static data
 */
const responseCache = new Map<string, CacheEntry>()

/**
 * Default cache TTL in milliseconds (5 minutes)
 */
const DEFAULT_CACHE_TTL = 5 * 60 * 1000

/**
 * Active AbortControllers for request cancellation
 */
const abortControllers = new Map<string, AbortController>()

/**
 * Axios instance with default configuration
 */
const axiosInstance: AxiosInstance = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: true
})

function normalizeRequestValue(value: unknown): unknown {
  if (value === null || value === undefined) {
    return value
  }

  if (value instanceof URLSearchParams) {
    return Array.from(value.entries()).sort(([a], [b]) => a.localeCompare(b))
  }

  if (typeof FormData !== 'undefined' && value instanceof FormData) {
    return Array.from(value.entries()).map(([key, entry]) => {
      if (entry instanceof File) {
        return [key, { name: entry.name, size: entry.size, type: entry.type, lastModified: entry.lastModified }]
      }

      return [key, entry]
    })
  }

  if (Array.isArray(value)) {
    return value.map(normalizeRequestValue)
  }

  if (typeof value === 'object') {
    return Object.fromEntries(
      Object.entries(value as Record<string, unknown>)
        .filter(([, entry]) => entry !== undefined)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([key, entry]) => [key, normalizeRequestValue(entry)])
    )
  }

  return value
}

function stableSerialize(value: unknown): string {
  if (value === undefined || value === null || value === '') {
    return ''
  }

  if (typeof value === 'string') {
    return value
  }

  return JSON.stringify(normalizeRequestValue(value))
}

/**
 * Generate a unique key for request deduplication and caching.
 */
function generateRequestKey(method: string, url: string, params?: unknown, data?: unknown): string {
  return [
    method.toUpperCase(),
    url,
    stableSerialize(params),
    stableSerialize(data),
  ].join(':')
}

function requestKeyFromConfig(config: AxiosRequestConfig): string {
  return generateRequestKey(config.method || 'GET', config.url || '', config.params, config.data)
}

function cacheKeyFor(url: string, params?: unknown): string {
  return generateRequestKey('CACHE', url, params)
}

function invalidateCacheForUrl(url: string): void {
  for (const key of responseCache.keys()) {
    if (key.startsWith(`CACHE:${url}:`)) {
      responseCache.delete(key)
    }
  }
}

/**
 * Check if cache entry is still valid
 */
function isCacheValid(entry: CacheEntry, ttl: number = DEFAULT_CACHE_TTL): boolean {
  return Date.now() - entry.timestamp < ttl
}

/**
 * Request interceptor for adding auth tokens, etc.
 */
axiosInstance.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    // Add auth token to requests if available
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error: Error) => {
    return Promise.reject(error)
  }
)

/**
 * Response interceptor for handling errors globally
 */
axiosInstance.interceptors.response.use(
  (response: AxiosResponse) => {
    // Clear pending request on success
    const key = requestKeyFromConfig(response.config)
    pendingRequests.delete(key)

    // Store ETag if present for future cache validation
    const etag = response.headers['etag']
    if (etag && response.config.url) {
      const cacheKey = cacheKeyFor(response.config.url, response.config.params)
      const existingEntry = responseCache.get(cacheKey)
      if (existingEntry) {
        existingEntry.etag = etag
      }
    }

    return response
  },
  (error: { response?: { status?: number }; config?: InternalAxiosRequestConfig }) => {
    // Clear pending request on error
    if (error.config) {
      pendingRequests.delete(requestKeyFromConfig(error.config))
    }

    // Handle 401 unauthorized responses
    if (error.response?.status === 401) {
      localStorage.removeItem('user')
      localStorage.removeItem('auth_token')
      localStorage.removeItem('2fa_challenge')
      responseCache.clear()
      pendingRequests.clear()
      // Only redirect if not already on login page
      if (!window.location.pathname.includes('/login')) {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

/**
 * Request configuration options
 */
interface RequestOptions {
  /** Skip request deduplication */
  skipDeduplication?: boolean
  /** Cache TTL in milliseconds (0 to disable caching) */
  cacheTTL?: number
  /** Force refresh cache */
  forceRefresh?: boolean
  /** Abort signal for cancellation */
  signal?: AbortSignal
  /** Request timeout in milliseconds */
  timeout?: number
}

/**
 * Typed API wrapper with request cancellation, deduplication, and caching
 */
const api = {
  /**
   * Cancel all pending requests with optional tag
   */
  cancelAll(tag?: string): void {
    if (tag) {
      // Cancel only requests matching the tag
      for (const [key, controller] of abortControllers) {
        if (key.startsWith(tag)) {
          controller.abort()
          abortControllers.delete(key)
        }
      }
    } else {
      // Cancel all requests
      for (const controller of abortControllers.values()) {
        controller.abort()
      }
      abortControllers.clear()
      pendingRequests.clear()
    }
  },

  /**
   * Cancel a specific request by key
   */
  cancel(key: string): void {
    const controller = abortControllers.get(key)
    if (controller) {
      controller.abort()
      abortControllers.delete(key)
      pendingRequests.delete(key)
    }
  },

  /**
   * Clear the response cache
   */
  clearCache(pattern?: string): void {
    if (pattern) {
      for (const key of responseCache.keys()) {
        if (key.includes(pattern)) {
          responseCache.delete(key)
        }
      }
    } else {
      responseCache.clear()
    }
  },

  /**
   * GET request with caching and deduplication
   */
  async get<T = unknown>(
    url: string,
    config?: AxiosRequestConfig & RequestOptions
  ): Promise<AxiosResponse<T>> {
    const options = config || {}
    const {
      skipDeduplication = false,
      cacheTTL = DEFAULT_CACHE_TTL,
      forceRefresh = false,
      signal,
    } = options
    const timeout = options.timeout
    const axiosConfig: AxiosRequestConfig = {
      url: options.url,
      method: options.method,
      baseURL: options.baseURL,
      headers: options.headers,
      params: options.params,
      data: options.data,
      transformRequest: options.transformRequest,
      transformResponse: options.transformResponse,
      withCredentials: options.withCredentials,
      responseType: options.responseType,
      responseEncoding: options.responseEncoding,
      xsrfCookieName: options.xsrfCookieName,
      xsrfHeaderName: options.xsrfHeaderName,
      onUploadProgress: options.onUploadProgress,
      onDownloadProgress: options.onDownloadProgress,
      maxContentLength: options.maxContentLength,
      maxBodyLength: options.maxBodyLength,
      validateStatus: options.validateStatus,
      maxRedirects: options.maxRedirects,
      proxy: options.proxy,
    }

    const key = generateRequestKey('GET', url, options.params)
    const cacheKey = cacheKeyFor(url, options.params)

    // Check cache first
    if (cacheTTL > 0 && !forceRefresh) {
      const cached = responseCache.get(cacheKey)
      if (cached && isCacheValid(cached, cacheTTL)) {
        // Return cached response wrapped in AxiosResponse format
        return {
          data: cached.data as T,
          status: 200,
          statusText: 'OK (cached)',
          headers: {},
          config: axiosConfig as InternalAxiosRequestConfig,
        }
      }
    }

    // Check for duplicate pending request
    if (!skipDeduplication && pendingRequests.has(key)) {
      return pendingRequests.get(key) as Promise<AxiosResponse<T>>
    }

    // Create AbortController for this request
    const controller = new AbortController()
    abortControllers.set(key, controller)

    // Combine signals if provided
    if (signal) {
      signal.addEventListener('abort', () => controller.abort())
    }

    const requestConfig: AxiosRequestConfig = {
      ...axiosConfig,
      signal: controller.signal,
      timeout,
    }

    // Add If-None-Match header for cache validation
    const cachedEntry = responseCache.get(cacheKey)
    if (cachedEntry?.etag) {
      requestConfig.headers = {
        ...requestConfig.headers,
        'If-None-Match': cachedEntry.etag,
      }
    }

    const request = axiosInstance.get<T>(url, requestConfig)

    // Track pending request
    if (!skipDeduplication) {
      pendingRequests.set(key, request)
    }

    const response = await request

    // Cache successful response
    if (cacheTTL > 0 && response.status === 200) {
      responseCache.set(cacheKey, {
        data: response.data,
        timestamp: Date.now(),
        etag: response.headers['etag'],
      })
    }

    // Cleanup
    abortControllers.delete(key)

    return response
  },

  /**
   * POST request (no caching, but deduplication available)
   */
  async post<T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig & RequestOptions
  ): Promise<AxiosResponse<T>> {
    const options = config || {}
    const {
      skipDeduplication = false,
      signal,
    } = options
    const timeout = options.timeout

    const key = generateRequestKey('POST', url, options.params, data)

    // Check for duplicate pending request
    if (!skipDeduplication && pendingRequests.has(key)) {
      return pendingRequests.get(key) as Promise<AxiosResponse<T>>
    }

    // Create AbortController for this request
    const controller = new AbortController()
    abortControllers.set(key, controller)

    // Combine signals if provided
    if (signal) {
      signal.addEventListener('abort', () => controller.abort())
    }

    const axiosConfig: AxiosRequestConfig = {
      url: options.url,
      method: options.method,
      baseURL: options.baseURL,
      headers: options.headers,
      params: options.params,
      transformRequest: options.transformRequest,
      transformResponse: options.transformResponse,
      withCredentials: options.withCredentials,
      responseType: options.responseType,
      responseEncoding: options.responseEncoding,
      xsrfCookieName: options.xsrfCookieName,
      xsrfHeaderName: options.xsrfHeaderName,
      onUploadProgress: options.onUploadProgress,
      onDownloadProgress: options.onDownloadProgress,
      maxContentLength: options.maxContentLength,
      maxBodyLength: options.maxBodyLength,
      validateStatus: options.validateStatus,
      maxRedirects: options.maxRedirects,
      proxy: options.proxy,
    }

    const request = axiosInstance.post<T>(url, data, {
      ...axiosConfig,
      signal: controller.signal,
      timeout,
    })

    // Track pending request
    if (!skipDeduplication) {
      pendingRequests.set(key, request)
    }

    // Invalidate cache for this URL on POST
    invalidateCacheForUrl(url)

    try {
      const response = await request
      abortControllers.delete(key)
      pendingRequests.delete(key)
      return response
    } catch (error) {
      abortControllers.delete(key)
      pendingRequests.delete(key)
      throw error
    }
  },

  /**
   * PUT request (no caching)
   */
  async put<T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig & RequestOptions
  ): Promise<AxiosResponse<T>> {
    const options = config || {}
    const { signal } = options
    const timeout = options.timeout

    const key = generateRequestKey('PUT', url, options.params, data)

    const controller = new AbortController()
    abortControllers.set(key, controller)

    if (signal) {
      signal.addEventListener('abort', () => controller.abort())
    }

    // Invalidate cache for this URL on PUT
    invalidateCacheForUrl(url)

    const axiosConfig: AxiosRequestConfig = {
      url: options.url,
      method: options.method,
      baseURL: options.baseURL,
      headers: options.headers,
      params: options.params,
      transformRequest: options.transformRequest,
      transformResponse: options.transformResponse,
      withCredentials: options.withCredentials,
      responseType: options.responseType,
      responseEncoding: options.responseEncoding,
      xsrfCookieName: options.xsrfCookieName,
      xsrfHeaderName: options.xsrfHeaderName,
      onUploadProgress: options.onUploadProgress,
      onDownloadProgress: options.onDownloadProgress,
      maxContentLength: options.maxContentLength,
      maxBodyLength: options.maxBodyLength,
      validateStatus: options.validateStatus,
      maxRedirects: options.maxRedirects,
      proxy: options.proxy,
    }

    try {
      const response = await axiosInstance.put<T>(url, data, {
        ...axiosConfig,
        signal: controller.signal,
        timeout,
      })
      abortControllers.delete(key)
      pendingRequests.delete(key)
      return response
    } catch (error) {
      abortControllers.delete(key)
      pendingRequests.delete(key)
      throw error
    }
  },

  /**
   * PATCH request (no caching)
   */
  async patch<T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig & RequestOptions
  ): Promise<AxiosResponse<T>> {
    const options = config || {}
    const { signal } = options
    const timeout = options.timeout

    const key = generateRequestKey('PATCH', url, options.params, data)

    const controller = new AbortController()
    abortControllers.set(key, controller)

    if (signal) {
      signal.addEventListener('abort', () => controller.abort())
    }

    // Invalidate cache for this URL on PATCH
    invalidateCacheForUrl(url)

    const axiosConfig: AxiosRequestConfig = {
      url: options.url,
      method: options.method,
      baseURL: options.baseURL,
      headers: options.headers,
      params: options.params,
      transformRequest: options.transformRequest,
      transformResponse: options.transformResponse,
      withCredentials: options.withCredentials,
      responseType: options.responseType,
      responseEncoding: options.responseEncoding,
      xsrfCookieName: options.xsrfCookieName,
      xsrfHeaderName: options.xsrfHeaderName,
      onUploadProgress: options.onUploadProgress,
      onDownloadProgress: options.onDownloadProgress,
      maxContentLength: options.maxContentLength,
      maxBodyLength: options.maxBodyLength,
      validateStatus: options.validateStatus,
      maxRedirects: options.maxRedirects,
      proxy: options.proxy,
    }

    try {
      const response = await axiosInstance.patch<T>(url, data, {
        ...axiosConfig,
        signal: controller.signal,
        timeout,
      })
      abortControllers.delete(key)
      pendingRequests.delete(key)
      return response
    } catch (error) {
      abortControllers.delete(key)
      pendingRequests.delete(key)
      throw error
    }
  },

  /**
   * DELETE request (no caching)
   */
  async delete<T = unknown>(
    url: string,
    config?: AxiosRequestConfig & RequestOptions
  ): Promise<AxiosResponse<T>> {
    const options = config || {}
    const { signal } = options
    const timeout = options.timeout

    const key = generateRequestKey('DELETE', url, options.params)

    const controller = new AbortController()
    abortControllers.set(key, controller)

    if (signal) {
      signal.addEventListener('abort', () => controller.abort())
    }

    // Invalidate cache for this URL on DELETE
    invalidateCacheForUrl(url)

    const axiosConfig: AxiosRequestConfig = {
      url: options.url,
      method: options.method,
      baseURL: options.baseURL,
      headers: options.headers,
      params: options.params,
      transformRequest: options.transformRequest,
      transformResponse: options.transformResponse,
      withCredentials: options.withCredentials,
      responseType: options.responseType,
      responseEncoding: options.responseEncoding,
      xsrfCookieName: options.xsrfCookieName,
      xsrfHeaderName: options.xsrfHeaderName,
      onUploadProgress: options.onUploadProgress,
      onDownloadProgress: options.onDownloadProgress,
      maxContentLength: options.maxContentLength,
      maxBodyLength: options.maxBodyLength,
      validateStatus: options.validateStatus,
      maxRedirects: options.maxRedirects,
      proxy: options.proxy,
    }

    try {
      const response = await axiosInstance.delete<T>(url, {
        ...axiosConfig,
        signal: controller.signal,
        timeout,
      })
      abortControllers.delete(key)
      pendingRequests.delete(key)
      return response
    } catch (error) {
      abortControllers.delete(key)
      pendingRequests.delete(key)
      throw error
    }
  },

  /**
   * Get the underlying axios instance for advanced usage
   */
  get instance(): AxiosInstance {
    return axiosInstance
  },

  /**
   * Get cache statistics
   */
  getCacheStats(): { size: number; keys: string[] } {
    return {
      size: responseCache.size,
      keys: Array.from(responseCache.keys()),
    }
  },

  /**
   * Get pending request count
   */
  getPendingCount(): number {
    return pendingRequests.size
  },
}

export default api
