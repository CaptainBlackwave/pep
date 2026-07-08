import { chromium } from './frontend/node_modules/playwright/index.mjs'
import { mkdir } from 'node:fs/promises'

const baseUrl = process.env.BASE_URL ?? 'http://localhost:5173'
const outDir = '/private/tmp/forum-content-studio-specific-audit'

const user = {
  id: 99,
  name: 'Content Editor',
  username: 'content-editor',
  email: 'content@example.test',
  roles: ['content-editor'],
  permissions: [
    'community-content.create',
    'community-content.update',
    'community-content.publish',
    'community-content.manage',
  ],
}

const permissions = {
  can_create: true,
  can_update: true,
  can_publish: true,
  can_manage: true,
  default_status: 'draft',
  allowed_statuses: ['draft', 'published', 'hidden'],
}

const contentItems = [
  {
    id: 1,
    type: 'research',
    title: 'Retatrutide Safety Profile',
    slug: 'retatrutide-safety-profile',
    status: 'draft',
    category: 'Safety',
    category_slug: 'safety',
    tag: 'Retatrutide',
    excerpt: 'Draft research overview.',
    body: '<p>Article body</p>',
    read_minutes: 8,
    read_label: '8 min',
    metadata: {
      compound: 'Retatrutide',
      research_focus: 'Safety profile',
      figures_data: 'Dose response table and adverse event summary.',
      references: 'Example citation.',
    },
    author: { name: 'Content Editor', initial: 'CE', badge: 'Editor' },
  },
  {
    id: 2,
    type: 'guide',
    title: 'Storage Basics',
    slug: 'storage-basics',
    status: 'published',
    category: 'Storage',
    category_slug: 'storage',
    tag: 'Beginner',
    excerpt: 'Guide overview.',
    body: '<p>Guide body</p>',
    read_minutes: 5,
    read_label: '5 min',
    metadata: { difficulty: 'Beginner', guide_type: 'Checklist' },
    author: { name: 'Content Editor', initial: 'CE', badge: 'Editor' },
  },
  {
    id: 3,
    type: 'faq',
    title: 'How should peptides be stored?',
    slug: 'how-should-peptides-be-stored',
    status: 'draft',
    category: 'Storage',
    category_slug: 'storage',
    tag: 'Storage',
    excerpt: 'Keep them cold and follow label guidance.',
    body: '<p>Full FAQ answer</p>',
    read_minutes: 3,
    read_label: '3 min',
    metadata: {},
    author: { name: 'Content Editor', initial: 'CE', badge: 'Editor' },
  },
]

function json(route, body) {
  return route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify(body),
  })
}

async function registerApiMocks(page) {
  await page.route('**/api/**', route => {
    const request = route.request()
    const url = new URL(request.url())
    const path = url.pathname

    if (path === '/api/v1/user') {
      return json(route, user)
    }

    if (path === '/api/v1/community/content/permissions') {
      return json(route, { data: permissions })
    }

    if (path === '/api/v1/community/content' && request.method() === 'GET') {
      const type = url.searchParams.get('type')
      const data = type ? contentItems.filter(item => item.type === type) : contentItems
      return json(route, { data, meta: { permissions } })
    }

    if (path.startsWith('/api/v1/community/content') && ['POST', 'PATCH'].includes(request.method())) {
      return json(route, { data: contentItems[0] })
    }

    if (path.includes('/notifications')) {
      return json(route, { data: [], meta: { unread: 0, counts: {} } })
    }

    if (path.includes('/messages')) {
      return json(route, { data: [], meta: {} })
    }

    if (path.includes('/online') || path.includes('/heartbeat')) {
      return json(route, { data: {} })
    }

    return json(route, { data: [], meta: {} })
  })
}

async function auditRoute(browser, routePath, label, viewport) {
  const context = await browser.newContext({ viewport })
  await context.addInitScript(currentUser => {
    localStorage.setItem('auth_token', 'audit-token')
    localStorage.setItem('user', JSON.stringify(currentUser))
    localStorage.setItem('cookie_consent', '1')
    localStorage.setItem('cookie_preferences', JSON.stringify({ essential: true, preferences: true, analytics: false }))
  }, user)

  const page = await context.newPage()
  const errors = []
  page.on('pageerror', error => errors.push(error.message))
  page.on('console', message => {
    if (message.type() === 'error') errors.push(message.text())
  })
  await registerApiMocks(page)

  await page.goto(`${baseUrl}${routePath}`, { waitUntil: 'domcontentloaded' })
  await page.waitForSelector('.pv-content-studio-form, .pv-empty-inline', { timeout: 10000 })
  await page.waitForTimeout(350)

  const screenshotPath = `${outDir}/${label}-${viewport.width}.png`
  await page.screenshot({ path: screenshotPath, fullPage: true })

  const metrics = await page.evaluate(() => {
    const text = (selector) => document.querySelector(selector)?.textContent?.replace(/\s+/g, ' ').trim() ?? ''
    const hasText = (value) => document.body.textContent?.includes(value) ?? false
    const overflow = Array.from(document.querySelectorAll('body *'))
      .filter(el => el instanceof HTMLElement && el.scrollWidth > el.clientWidth + 2)
      .slice(0, 8)
      .map(el => ({
        tag: el.tagName.toLowerCase(),
        className: typeof el.className === 'string' ? el.className : '',
        text: el.textContent?.replace(/\s+/g, ' ').trim().slice(0, 80) ?? '',
        scrollWidth: el.scrollWidth,
        clientWidth: el.clientWidth,
      }))

    return {
      title: text('h1'),
      subtitle: text('.pv-page-header p'),
      typePickerButtons: document.querySelectorAll('.pv-content-type-picker button').length,
      hasArticleTitle: hasText('Article Title'),
      hasFiguresData: hasText('Figures & Data'),
      hasReferences: hasText('References'),
      hasQuestion: hasText('Question'),
      hasGuideTitle: hasText('Guide Title'),
      hasPublishButton: hasText('Publish'),
      documentWidth: document.documentElement.scrollWidth,
      viewportWidth: document.documentElement.clientWidth,
      overflow,
    }
  })

  await context.close()
  return { label, routePath, viewport, screenshotPath, errors, metrics }
}

await mkdir(outDir, { recursive: true })

const browser = await chromium.launch()
const routes = [
  ['/research-library/new', 'research'],
  ['/guides/new', 'guide'],
  ['/guides/faqs/new', 'faq'],
]
const viewports = [
  { width: 390, height: 844 },
  { width: 1440, height: 1000 },
]

const results = []
for (const [routePath, label] of routes) {
  for (const viewport of viewports) {
    results.push(await auditRoute(browser, routePath, label, viewport))
  }
}

await browser.close()
console.log(JSON.stringify(results, null, 2))
