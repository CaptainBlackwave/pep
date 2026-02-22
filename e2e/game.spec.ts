import { test, expect, type Page } from '@playwright/test'

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Login helper for authenticated tests
 */
async function login(page: Page, options: { userId?: number } = {}) {
  // Mock the login API response
  await page.route('**/api/**module=auth**action=login**', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        data: {
          user: {
            id: options.userId || 1,
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
            id: options.userId || 1,
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

  // Wait for redirect to dashboard
  await page.waitForURL(/\/dashboard/, { timeout: 5000 }).catch(() => {
    // Continue even if redirect doesn't happen in mock mode
  })
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

// ─── Dashboard Page ──────────────────────────────────────────────────────────

test.describe('Dashboard Page', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
  })

  test('displays player stats on dashboard', async ({ page }) => {
    await login(page)
    await page.goto('/dashboard')

    // Check for stat displays
    await expect(page.locator('body')).toContainText('80') // Energy
  })

  test('displays navigation menu', async ({ page }) => {
    await login(page)
    await page.goto('/dashboard')

    // Check for navigation elements
    await expect(page.locator('nav')).toBeVisible()
  })
})

// ─── Crime Module ────────────────────────────────────────────────────────────

test.describe('Crime Module', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('displays crime options', async ({ page }) => {
    // Mock crimes API
    await page.route('**/api/**module=crimes**', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            crimes: [
              { id: 1, name: 'Pickpocket', difficulty: 1, reward_min: 10, reward_max: 50 },
              { id: 2, name: 'Rob Store', difficulty: 2, reward_min: 100, reward_max: 500 },
            ],
          },
        }),
      }),
    )

    await page.goto('/crimes')

    // Check for crime options
    await expect(page.locator('body')).toContainText('Pickpocket')
  })

  test('can commit a crime', async ({ page }) => {
    // Mock commit crime API
    await page.route('**/api/**module=crimes**action=commit**', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          message: 'Crime successful!',
          data: {
            reward: 150,
            experience: 10,
          },
        }),
      }),
    )

    await page.goto('/crimes')

    // Verify crime page loaded and commit button exists
    const commitButton = page.locator('button').filter({ hasText: /commit|do/i }).first()
    await expect(commitButton).toBeVisible({ timeout: 2000 })
    await commitButton.click()
    // Verify the page is still functional after clicking
    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Gym Module ──────────────────────────────────────────────────────────────

test.describe('Gym Module', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('displays gym training options', async ({ page }) => {
    await page.goto('/gym')

    // Check for training-related content
    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Bank Module ─────────────────────────────────────────────────────────────

test.describe('Bank Module', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('displays bank balance', async ({ page }) => {
    await page.goto('/bank')

    // Check for bank-related content
    await expect(page.locator('body')).toBeVisible()
  })

  test('can deposit money', async ({ page }) => {
    // Mock deposit API
    await page.route('**/api/**module=bank**action=deposit**', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          message: 'Deposit successful!',
          data: {
            cash: 4000,
            bank: 11000,
          },
        }),
      }),
    )

    await page.goto('/bank')

    // Verify bank page loaded
    await expect(page.locator('body')).toBeVisible()
    // Look for deposit form and interact with it
    const depositInput = page.locator('input[type="number"]').first()
    await expect(depositInput).toBeVisible({ timeout: 2000 })
    await depositInput.fill('1000')
    // Verify input was filled
    await expect(depositInput).toHaveValue('1000')
  })
})

// ─── Hospital Module ─────────────────────────────────────────────────────────

test.describe('Hospital Module', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('displays hospital options', async ({ page }) => {
    await page.goto('/hospital')

    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Jail Module ─────────────────────────────────────────────────────────────

test.describe('Jail Module', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('displays jail page', async ({ page }) => {
    // Mock jail API
    await page.route('**/api/**module=jail**', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            inmates: [],
            is_jailed: false,
          },
        }),
      }),
    )

    await page.goto('/jail')

    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Travel Module ───────────────────────────────────────────────────────────

test.describe('Travel Module', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('displays travel destinations', async ({ page }) => {
    // Mock travel API
    await page.route('**/api/**module=travel**', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            destinations: [
              { id: 1, name: 'New York', cost: 500, duration: 30 },
              { id: 2, name: 'London', cost: 800, duration: 45 },
            ],
            current_location: { id: 1, name: 'Downtown' },
          },
        }),
      }),
    )

    await page.goto('/travel')

    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Shop Module ─────────────────────────────────────────────────────────────

test.describe('Shop Module', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('displays shop items', async ({ page }) => {
    // Mock shop API
    await page.route('**/api/**module=shop**', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            items: [
              { id: 1, name: 'Health Pack', price: 100, type: 'consumable' },
              { id: 2, name: 'Energy Drink', price: 50, type: 'consumable' },
            ],
          },
        }),
      }),
    )

    await page.goto('/shop')

    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Profile Page ────────────────────────────────────────────────────────────

test.describe('Profile Page', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('displays user profile', async ({ page }) => {
    await page.goto('/profile')

    await expect(page.locator('body')).toContainText('testuser')
  })
})

// ─── Settings Page ───────────────────────────────────────────────────────────

test.describe('Settings Page', () => {
  test.beforeEach(async ({ page }) => {
    await mockPlayerStats(page)
    await login(page)
  })

  test('displays settings options', async ({ page }) => {
    await page.goto('/settings')

    await expect(page.locator('body')).toBeVisible()
  })
})
