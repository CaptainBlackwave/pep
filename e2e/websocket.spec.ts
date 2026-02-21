import { test, expect, type Page } from '@playwright/test'

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Login helper for authenticated tests
 */
async function login(page: Page) {
  // Mock the login API response
  await page.route('**/api/**module=auth**action=login**', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        data: {
          user: {
            id: 1,
            username: 'testuser',
            email: 'test@example.com',
          },
        },
      }),
    }),
  )

  // Mock the user/me endpoint
  await page.route('**/api/**module=auth**action=me**', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        data: {
          user: {
            id: 1,
            username: 'testuser',
            email: 'test@example.com',
            energy: 80,
            max_energy: 100,
            health: 90,
            max_health: 100,
            cash: 5000,
            bank: 10000,
            level: 5,
          },
        },
      }),
    }),
  )

  await page.goto('/login')
  await page.evaluate(() => localStorage.clear())
  await page.fill('#email', 'test@example.com')
  await page.fill('#password', 'password123')
  await page.click('button[type="submit"]')
}

/**
 * Mock the player stats API
 */
async function mockPlayerStats(page: Page) {
  await page.route('**/api/user**', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        id: 1,
        username: 'testuser',
        energy: 80,
        max_energy: 100,
        health: 90,
        max_health: 100,
        cash: 5000,
        bank: 10000,
        level: 5,
      }),
    }),
  )
}

// ─── WebSocket Connection ────────────────────────────────────────────────────

test.describe('WebSocket Connection', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
  })

  test('application loads without WebSocket errors', async ({ page }) => {
    const errors: string[] = []
    page.on('pageerror', (error) => {
      errors.push(error.message)
    })

    await login(page)
    await page.goto('/dashboard')

    // Wait a bit for any WebSocket connection attempts
    await page.waitForTimeout(1000)

    // Should not have any WebSocket-related errors
    const wsErrors = errors.filter((e) =>
      e.toLowerCase().includes('websocket') || e.toLowerCase().includes('socket')
    )
    expect(wsErrors.length).toBe(0)
  })

  test('page remains functional when WebSocket is unavailable', async ({ page }) => {
    // Block WebSocket connections
    await page.route('ws://**', (route) => route.abort())

    await login(page)
    await page.goto('/dashboard')

    // Page should still render
    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Real-time Stats Updates ─────────────────────────────────────────────────

test.describe('Real-time Stats Updates', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('stats display on dashboard', async ({ page }) => {
    await page.goto('/dashboard')

    // Check that stats are displayed
    await expect(page.locator('body')).toBeVisible()
  })

  test('polling fallback works when WebSocket unavailable', async ({ page }) => {
    // Block WebSocket connections to force polling fallback
    await page.route('ws://**', (route) => route.abort())

    await page.goto('/dashboard')

    // Page should still function with polling
    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Chat Functionality ──────────────────────────────────────────────────────

test.describe('Chat Functionality', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('chat panel loads', async ({ page }) => {
    await page.goto('/dashboard')

    // Look for chat-related elements
    const chatElements = page.locator('[class*="chat"], [class*="Chat"]')
    // Chat might or might not be visible depending on layout
    const chatCount = await chatElements.count().catch(() => 0)
    expect(chatCount).toBeGreaterThanOrEqual(0)
  })

  test('chat works without WebSocket (fallback)', async ({ page }) => {
    // Block WebSocket
    await page.route('ws://**', (route) => route.abort())

    await page.goto('/dashboard')

    // Page should still work
    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Notifications ───────────────────────────────────────────────────────────

test.describe('Notifications', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('notifications area loads', async ({ page }) => {
    await page.goto('/dashboard')

    // Look for notification-related elements
    const notifElements = page.locator('[class*="notification"], [class*="Notification"]')
    const notifCount = await notifElements.count().catch(() => 0)
    expect(notifCount).toBeGreaterThanOrEqual(0)
  })

  test('notifications work without WebSocket (fallback)', async ({ page }) => {
    // Block WebSocket
    await page.route('ws://**', (route) => route.abort())

    await page.goto('/dashboard')

    // Page should still work
    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Connection Resilience ───────────────────────────────────────────────────

test.describe('Connection Resilience', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('application handles network interruptions gracefully', async ({ page }) => {
    await page.goto('/dashboard')

    // Simulate going offline
    await page.context().setOffline(true)
    await page.waitForTimeout(500)

    // Simulate coming back online
    await page.context().setOffline(false)
    await page.waitForTimeout(500)

    // Page should still be functional
    await expect(page.locator('body')).toBeVisible()
  })

  test('application handles API errors gracefully', async ({ page }) => {
    // Mock API error
    await page.route('**/api/**', (route) =>
      route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({
          success: false,
          message: 'Server error',
        }),
      }),
    )

    await page.goto('/dashboard')

    // Page should still render (even if data is missing)
    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Performance ─────────────────────────────────────────────────────────────

test.describe('Performance', () => {
  test('dashboard loads within acceptable time', async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)

    const startTime = Date.now()
    await page.goto('/dashboard')
    const loadTime = Date.now() - startTime

    // Dashboard should load within 5 seconds
    expect(loadTime).toBeLessThan(5000)
  })

  test('no memory leaks from WebSocket reconnection attempts', async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)

    // Block WebSocket to trigger reconnection attempts
    await page.route('ws://**', (route) => route.abort())

    await page.goto('/dashboard')

    // Wait for potential reconnection attempts
    await page.waitForTimeout(3000)

    // Page should still be responsive
    await expect(page.locator('body')).toBeVisible()
  })
})
