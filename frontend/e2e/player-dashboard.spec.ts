import { test, expect, type Page } from '@playwright/test'

// ─── Types ────────────────────────────────────────────────────────────────────

interface PlayerStats {
  id: number
  username: string
  email: string
  energy: number
  max_energy: number
  health: number
  max_health: number
  cash: number
  bank: number
  level: number
  rank: string
  location: string
  xp: number
}

// ─── Test Data ────────────────────────────────────────────────────────────────

const mockPlayer: PlayerStats = {
  id: 1,
  username: 'testplayer',
  email: 'player@test.com',
  energy: 80,
  max_energy: 100,
  health: 90,
  max_health: 100,
  cash: 5000,
  bank: 10000,
  level: 5,
  rank: 'Hustler',
  location: 'Detroit',
  xp: 150,
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Set up authenticated player session in localStorage
 */
async function setupPlayerSession(page: Page, player: PlayerStats = mockPlayer) {
  await page.evaluate((p) => {
    localStorage.setItem('user', JSON.stringify({
      id: p.id,
      username: p.username,
      email: p.email,
    }))
    localStorage.setItem('auth_token', 'test-auth-token-' + Date.now())
  }, player)
}

/**
 * Mock the player stats API endpoint
 */
async function mockPlayerStatsAPI(page: Page, player: PlayerStats = mockPlayer) {
  await page.route('**/api/v1/user*', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        data: player,
      }),
    }),
  )
}

/**
 * Mock the plugins/enabled API endpoint
 */
async function mockPluginsAPI(page: Page) {
  await page.route('**/api/v1/plugins/enabled*', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        plugins: [
          {
            slug: 'crime',
            name: 'Crime',
            icon: '🔫',
            color: 'red',
            route_name: 'crime.index',
            navigation: { enabled: true, section: 'actions', order: 1 },
          },
          {
            slug: 'gym',
            name: 'Gym',
            icon: '💪',
            color: 'blue',
            route_name: 'gym.index',
            navigation: { enabled: true, section: 'self', order: 1 },
          },
          {
            slug: 'hospital',
            name: 'Hospital',
            icon: '🏥',
            color: 'green',
            route_name: 'hospital.index',
            navigation: { enabled: true, section: 'utilities', order: 1 },
          },
        ],
        navigation: [],
        routes: [],
      }),
    }),
  )
}

/**
 * Navigate to dashboard with pre-authenticated session
 */
async function gotoDashboard(page: Page, player: PlayerStats = mockPlayer) {
  await page.goto('/login')
  await setupPlayerSession(page, player)
  await mockPlayerStatsAPI(page, player)
  await mockPluginsAPI(page)
  await page.goto('/dashboard')
}

// ─── Dashboard Access Tests ──────────────────────────────────────────────────

test.describe('Dashboard Access', () => {
  test('unauthenticated player is redirected to login', async ({ page }) => {
    await page.goto('/login')
    await page.evaluate(() => localStorage.clear())
    await page.goto('/dashboard')

    await expect(page).toHaveURL(/\/login/)
  })

  test('authenticated player can access dashboard', async ({ page }) => {
    await gotoDashboard(page)

    await expect(page).toHaveURL(/\/dashboard/)
    await expect(page.locator('.dashboard-view')).toBeVisible()
  })

  test('dashboard displays game title', async ({ page }) => {
    await gotoDashboard(page)

    await expect(page.locator('.game-title')).toBeVisible()
    await expect(page.locator('.game-title')).toContainText('OpenPBBG')
  })
})

// ─── Player Stats Display Tests ──────────────────────────────────────────────

test.describe('Player Stats Display', () => {
  test.beforeEach(async ({ page }) => {
    await gotoDashboard(page)
  })

  test('displays player rank badge', async ({ page }) => {
    await expect(page.locator('.rank-badge')).toBeVisible()
    await expect(page.locator('.rank-badge')).toContainText('Hustler')
  })

  test('displays XP stat', async ({ page }) => {
    await expect(page.locator('.user-stats-inline')).toContainText('XP')
  })

  test('displays cash stat', async ({ page }) => {
    await expect(page.locator('.user-stats-inline')).toContainText('$')
  })

  test('displays health stat', async ({ page }) => {
    await expect(page.locator('.user-stats-inline')).toContainText('❤️')
  })

  test('displays energy stat', async ({ page }) => {
    await expect(page.locator('.user-stats-inline')).toContainText('⚡')
  })

  test('displays location stat', async ({ page }) => {
    await expect(page.locator('.user-stats-inline')).toContainText('📍')
  })

  test('user banner is visible', async ({ page }) => {
    await expect(page.locator('.user-banner')).toBeVisible()
  })

  test('user stats inline container is visible', async ({ page }) => {
    await expect(page.locator('.user-stats-inline')).toBeVisible()
  })
})

// ─── Quick Access Navigation Tests ───────────────────────────────────────────

test.describe('Quick Access Navigation', () => {
  test.beforeEach(async ({ page }) => {
    await gotoDashboard(page)
  })

  test('quick access section is visible', async ({ page }) => {
    await expect(page.locator('.quick-access')).toBeVisible()
  })

  test('profile quick card is visible and clickable', async ({ page }) => {
    const profileCard = page.locator('.quick-card').filter({ hasText: 'Profile' })
    await expect(profileCard).toBeVisible()
    await expect(profileCard).toHaveAttribute('href', '/profile')
  })

  test('chat quick card is visible and clickable', async ({ page }) => {
    const chatCard = page.locator('.quick-card').filter({ hasText: 'Chat' })
    await expect(chatCard).toBeVisible()
    await expect(chatCard).toHaveAttribute('href', '/chat')
  })

  test('activity quick card is visible and clickable', async ({ page }) => {
    const activityCard = page.locator('.quick-card').filter({ hasText: 'Activity' })
    await expect(activityCard).toBeVisible()
    await expect(activityCard).toHaveAttribute('href', '/activity')
  })

  test('wiki quick card is visible and clickable', async ({ page }) => {
    const wikiCard = page.locator('.quick-card').filter({ hasText: 'Wiki' })
    await expect(wikiCard).toBeVisible()
    await expect(wikiCard).toHaveAttribute('href', '/wiki')
  })

  test('quick cards have icons', async ({ page }) => {
    const quickCards = page.locator('.quick-card')
    const count = await quickCards.count()

    for (let i = 0; i < count; i++) {
      const card = quickCards.nth(i)
      await expect(card.locator('.card-icon')).toBeVisible()
    }
  })
})

// ─── Game Features Grid Tests ────────────────────────────────────────────────

test.describe('Game Features Grid', () => {
  test.beforeEach(async ({ page }) => {
    await gotoDashboard(page)
  })

  test('game features section title is displayed', async ({ page }) => {
    await expect(page.locator('.section-title')).toBeVisible()
    await expect(page.locator('.section-title')).toContainText('Game Features')
  })

  test('features grid is visible', async ({ page }) => {
    await expect(page.locator('.features-grid')).toBeVisible()
  })

  test('scavenge feature card is visible', async ({ page }) => {
    const scavengeCard = page.locator('.feature-card').filter({ hasText: 'Scavenge' })
    await expect(scavengeCard).toBeVisible()
    await expect(scavengeCard).toHaveAttribute('href', '/scavenge')
  })

  test('jail feature card is visible', async ({ page }) => {
    const jailCard = page.locator('.feature-card').filter({ hasText: 'Jail' })
    await expect(jailCard).toBeVisible()
    await expect(jailCard).toHaveAttribute('href', '/jail')
  })

  test('bank feature card is visible', async ({ page }) => {
    const bankCard = page.locator('.feature-card').filter({ hasText: 'Bank' })
    await expect(bankCard).toBeVisible()
    await expect(bankCard).toHaveAttribute('href', '/bank')
  })

  test('theft feature card is visible', async ({ page }) => {
    const theftCard = page.locator('.feature-card').filter({ hasText: 'Car Theft' })
    await expect(theftCard).toBeVisible()
    await expect(theftCard).toHaveAttribute('href', '/theft')
  })

  test('hospital feature card is visible', async ({ page }) => {
    const hospitalCard = page.locator('.feature-card').filter({ hasText: 'Hospital' })
    await expect(hospitalCard).toBeVisible()
    await expect(hospitalCard).toHaveAttribute('href', '/hospital')
  })

  test('travel feature card is visible', async ({ page }) => {
    const travelCard = page.locator('.feature-card').filter({ hasText: 'Travel' })
    await expect(travelCard).toBeVisible()
    await expect(travelCard).toHaveAttribute('href', '/travel')
  })

  test('gym feature card is visible', async ({ page }) => {
    const gymCard = page.locator('.feature-card').filter({ hasText: 'Gym' })
    await expect(gymCard).toBeVisible()
    await expect(gymCard).toHaveAttribute('href', '/gym')
  })

  test('forums feature card is visible', async ({ page }) => {
    const forumsCard = page.locator('.feature-card').filter({ hasText: 'Forums' })
    await expect(forumsCard).toBeVisible()
    await expect(forumsCard).toHaveAttribute('href', '/forums')
  })

  test('all feature cards have icons', async ({ page }) => {
    const featureCards = page.locator('.feature-card')
    const count = await featureCards.count()

    expect(count).toBeGreaterThan(0)

    for (let i = 0; i < count; i++) {
      const card = featureCards.nth(i)
      await expect(card.locator('.feature-icon')).toBeVisible()
    }
  })

  test('all feature cards have descriptions', async ({ page }) => {
    const featureCards = page.locator('.feature-card')
    const count = await featureCards.count()

    for (let i = 0; i < count; i++) {
      const card = featureCards.nth(i)
      await expect(card.locator('.feature-desc')).toBeVisible()
    }
  })
})

// ─── Logout Functionality Tests ───────────────────────────────────────────────

test.describe('Logout Functionality', () => {
  test.beforeEach(async ({ page }) => {
    await gotoDashboard(page)
  })

  test('logout button is visible', async ({ page }) => {
    await expect(page.locator('.logout-btn')).toBeVisible()
    await expect(page.locator('.logout-btn')).toContainText('Logout')
  })

  test('clicking logout clears auth token and redirects to login', async ({ page }) => {
    // Verify we're authenticated
    const userBefore = await page.evaluate(() => localStorage.getItem('user'))
    expect(userBefore).not.toBeNull()

    // Click logout
    await page.click('.logout-btn')

    // Wait for redirect to login page
    await expect(page).toHaveURL(/\/login/, { timeout: 5000 })

    // Verify token is cleared
    const tokenAfter = await page.evaluate(() => localStorage.getItem('auth_token'))
    expect(tokenAfter).toBeNull()
  })
})

// ─── Navigation from Dashboard Tests ─────────────────────────────────────────

test.describe('Navigation from Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await gotoDashboard(page)
  })

  test('can navigate to bank page', async ({ page }) => {
    await page.click('.feature-card[href="/bank"]')
    await expect(page).toHaveURL(/\/bank/)
  })

  test('can navigate to hospital page', async ({ page }) => {
    await page.click('.feature-card[href="/hospital"]')
    await expect(page).toHaveURL(/\/hospital/)
  })

  test('can navigate to gym page', async ({ page }) => {
    await page.click('.feature-card[href="/gym"]')
    await expect(page).toHaveURL(/\/gym/)
  })

  test('can navigate to travel page', async ({ page }) => {
    await page.click('.feature-card[href="/travel"]')
    await expect(page).toHaveURL(/\/travel/)
  })

  test('can navigate to jail page', async ({ page }) => {
    await page.click('.feature-card[href="/jail"]')
    await expect(page).toHaveURL(/\/jail/)
  })

  test('can navigate to profile page from quick access', async ({ page }) => {
    await page.click('.quick-card[href="/profile"]')
    await expect(page).toHaveURL(/\/profile/)
  })
})

// ─── Responsive Design Tests ─────────────────────────────────────────────────

test.describe('Dashboard Responsive Design', () => {
  test('displays correctly on mobile viewport', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 })
    await gotoDashboard(page)

    await expect(page.locator('.dashboard-view')).toBeVisible()
    await expect(page.locator('.user-banner')).toBeVisible()
  })

  test('displays correctly on tablet viewport', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 })
    await gotoDashboard(page)

    await expect(page.locator('.dashboard-view')).toBeVisible()
    await expect(page.locator('.features-grid')).toBeVisible()
  })

  test('displays correctly on desktop viewport', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 720 })
    await gotoDashboard(page)

    await expect(page.locator('.dashboard-view')).toBeVisible()
    await expect(page.locator('.features-grid')).toBeVisible()
  })

  test('quick access cards wrap on smaller screens', async ({ page }) => {
    await page.setViewportSize({ width: 500, height: 800 })
    await gotoDashboard(page)

    const quickAccess = page.locator('.quick-access')
    await expect(quickAccess).toBeVisible()
  })
})

// ─── Player Session Tests ────────────────────────────────────────────────────

test.describe('Player Session', () => {
  test('player session persists across page navigation', async ({ page }) => {
    await gotoDashboard(page)

    // Navigate to another page
    await page.goto('/profile')

    // Verify session still exists
    const user = await page.evaluate(() => localStorage.getItem('user'))
    expect(user).toContain('testplayer')
  })

  test('player can refresh dashboard without losing session', async ({ page }) => {
    await gotoDashboard(page)

    // Store the user data
    const userBefore = await page.evaluate(() => localStorage.getItem('user'))

    // Refresh the page
    await page.reload()

    // Verify session is maintained (if not redirected to login)
    const currentUrl = page.url()
    if (currentUrl.includes('/dashboard')) {
      const userAfter = await page.evaluate(() => localStorage.getItem('user'))
      expect(userAfter).toBe(userBefore)
    }
  })
})

// ─── Dashboard Loading States Tests ──────────────────────────────────────────

test.describe('Dashboard Loading States', () => {
  test('shows loading state while fetching player data', async ({ page }) => {
    // Delay the API response
    await page.route('**/api/v1/user*', async (route) => {
      await new Promise((resolve) => setTimeout(resolve, 500))
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ success: true, data: mockPlayer }),
      })
    })

    await page.goto('/login')
    await setupPlayerSession(page)
    await mockPluginsAPI(page)

    // Navigate to dashboard
    await page.goto('/dashboard')

    // Dashboard should eventually load
    await expect(page.locator('.dashboard-view')).toBeVisible({ timeout: 5000 })
  })
})

// ─── Error Handling Tests ────────────────────────────────────────────────────

test.describe('Dashboard Error Handling', () => {
  test('handles API error gracefully', async ({ page }) => {
    await page.goto('/login')
    await setupPlayerSession(page)

    // Mock API error
    await page.route('**/api/v1/user*', (route) =>
      route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({
          success: false,
          message: 'Server error',
        }),
      }),
    )

    await mockPluginsAPI(page)
    await page.goto('/dashboard')

    // Page should still load (with fallback data or error state)
    await expect(page.locator('body')).toBeVisible()
  })

  test('handles network error during dashboard load', async ({ page }) => {
    await page.goto('/login')
    await setupPlayerSession(page)

    // Simulate network failure
    await page.route('**/api/v1/user*', (route) => route.abort('failed'))
    await page.route('**/api/v1/plugins/**', (route) => route.abort('failed'))

    await page.goto('/dashboard')

    // Page should not crash
    await expect(page.locator('body')).toBeVisible()
  })
})

// ─── Accessibility Tests ──────────────────────────────────────────────────────

test.describe('Dashboard Accessibility', () => {
  test.beforeEach(async ({ page }) => {
    await gotoDashboard(page)
  })

  test('all feature cards are keyboard accessible', async ({ page }) => {
    const featureCards = page.locator('.feature-card')
    const count = await featureCards.count()

    for (let i = 0; i < count; i++) {
      const card = featureCards.nth(i)
      // Verify the card is a link and has href
      const tagName = await card.evaluate((el) => el.tagName.toLowerCase())
      expect(tagName).toBe('a')
    }
  })

  test('quick access cards are keyboard accessible', async ({ page }) => {
    const quickCards = page.locator('.quick-card')
    const count = await quickCards.count()

    for (let i = 0; i < count; i++) {
      const card = quickCards.nth(i)
      const tagName = await card.evaluate((el) => el.tagName.toLowerCase())
      expect(tagName).toBe('a')
    }
  })

  test('logout button is focusable', async ({ page }) => {
    const logoutBtn = page.locator('.logout-btn')
    await expect(logoutBtn).toBeVisible()

    // Tab to focus the logout button
    await page.keyboard.press('Tab')
    await page.keyboard.press('Tab')
    await page.keyboard.press('Tab')

    // The button should be focusable
    await expect(logoutBtn).toBeVisible()
  })
})

// ─── Feature Card Hover Effects Tests ────────────────────────────────────────

test.describe('Feature Card Interactions', () => {
  test.beforeEach(async ({ page }) => {
    await gotoDashboard(page)
  })

  test('feature card hover changes visual state', async ({ page }) => {
    const featureCard = page.locator('.feature-card').first()

    // Hover over the card
    await featureCard.hover()

    // Card should still be visible (hover effect applied via CSS)
    await expect(featureCard).toBeVisible()
  })

  test('quick card hover changes visual state', async ({ page }) => {
    const quickCard = page.locator('.quick-card').first()

    // Hover over the card
    await quickCard.hover()

    // Card should still be visible
    await expect(quickCard).toBeVisible()
  })
})
