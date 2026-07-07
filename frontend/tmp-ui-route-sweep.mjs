import { chromium } from '@playwright/test'

const baseUrl = 'http://127.0.0.1:5173'
const screenshotsDir = '/private/tmp/forum-route-ui-sweep'
const adminToken = process.env.ADMIN_TOKEN ?? ''

const adminUser = {
  id: 9,
  name: 'Administrator',
  username: 'admin',
  email: 'admin@example.com',
  is_approved_vendor: true,
  tier: 'paid',
  roles: ['admin'],
}

const routes = [
  ['home', '/home'],
  ['pricing', '/pricing'],
  ['discussions', '/discussions'],
  ['discussion-detail', '/discussions/hey-all'],
  ['lab-results', '/lab-results'],
  ['vendors', '/vendor-reviews'],
  ['vendor-detail', '/vendor-reviews/linyi-xinhao-peptides'],
  ['vendor-review-modal', '/vendor-reviews/linyi-xinhao-peptides/review'],
  ['vendor-portal', '/vendor-portal'],
  ['research-library', '/research-library'],
  ['guides', '/guides'],
  ['members', '/members'],
  ['messages', '/messages'],
  ['announcements', '/announcements'],
  ['notifications', '/notifications'],
  ['settings', '/settings'],
  ['search', '/search?q=vendor'],
]

const sampleVendorDetail = {
  data: {
    id: 1,
    name: 'Linyi Xinhao Peptides',
    slug: 'linyi-xinhao-peptides',
    logo_initials: 'LX',
    logo_text: 'LX',
    logo_class: 'purple',
    status_label: 'Trusted',
    status_class: 'trusted',
    country: null,
    tone: 'green',
    description: 'International peptide vendor profile with product listings.',
    website_url: 'https://example.com',
    image_url: null,
    contact: { email: 'support@example.com', telegram: '@linyipeptides', signal: null, discord: null, support_url: 'https://example.com/support', response_policy: 'Replies within 1-2 working days.', public_notes: 'Product listings are informational only.' },
    member_since: '2026-07-04',
    member_since_label: 'Jul 2026',
    last_active_label: 'today',
    review_count: 1,
    average_rating: 5,
    rating_label: '5.0',
    would_buy_again_label: '100%',
    response_rate_label: '92%',
    avg_response_time: '1 day',
    tags: ['China Vendor', 'Lab Tested', 'Bulk Options'],
    product_count: 1,
    products: [{
      id: 101,
      vendor_id: 1,
      name: 'Retatrutide Research Peptide',
      slug: 'retatrutide-research-peptide',
      category: 'Peptide',
      strength: '10mg',
      package_size: '1 vial',
      purity_label: '>98%',
      description: 'Public product listing for research-use inquiries.',
      variants: [{ label: '10mg', price: 85, availability: 'in_stock' }],
      price: 85,
      price_label: '$85.00',
      currency_code: 'USD',
      availability: 'in_stock',
      availability_label: 'In stock',
      image_url: null,
      tags: ['GLP-1', 'Research'],
      average_rating: 4.8,
      rating_label: '4.8',
      review_count: 7,
      sort_order: 1,
      status: 'published',
    }],
    top_products: [],
    status: 'published',
    href: '/vendor-reviews/linyi-xinhao-peptides',
    rating_distribution: [{ rating: 5, count: 1, percent: 100 }],
    review_items: [],
    documents: [],
  },
}

async function installState(page, stateName) {
  await page.addInitScript(() => localStorage.setItem('cookie_consent', 'true'))

  if (stateName !== 'admin' || !adminToken) return

  await page.route('**/api/v1/user', route => route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(adminUser) }))
  await page.route('**/api/v1/membership/status', route => route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ tier: 'paid', subscription: { status: 'active' } }) }))
  await page.route('**/api/v1/community/vendors/linyi-xinhao-peptides', route => route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(sampleVendorDetail) }))

  await page.addInitScript(({ token, user }) => {
    localStorage.setItem('auth_token', token)
    localStorage.setItem('user', JSON.stringify(user))
  }, { token: adminToken, user: adminUser })
}

async function runState(stateName) {
  const browser = await chromium.launch()
  const results = []
  const viewports = [
    ['mobile', { width: 390, height: 844 }],
    ['desktop', { width: 1440, height: 1000 }],
  ]

  for (const [viewportName, viewport] of viewports) {
    const context = await browser.newContext({ viewport, deviceScaleFactor: 1 })
    const page = await context.newPage()
    const consoleErrors = []
    const pageErrors = []

    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text())
    })
    page.on('pageerror', error => pageErrors.push(error.message))

    await installState(page, stateName)

    for (const [label, route] of routes) {
      const url = `${baseUrl}${route}`
      await page.goto(url, { waitUntil: 'networkidle' })
      const audit = await page.evaluate(() => {
        const text = document.body.innerText
        const brokenText = /Unable to load|undefined|null|NaN/.test(text)
        const overflow = Array.from(document.querySelectorAll('body *'))
          .map(element => {
            const rect = element.getBoundingClientRect()
            const style = window.getComputedStyle(element)
            return {
              tag: element.tagName.toLowerCase(),
              className: String(element.getAttribute('class') ?? '').slice(0, 80),
              left: Math.round(rect.left),
              right: Math.round(rect.right),
              width: Math.round(rect.width),
              position: style.position,
            }
          })
          .filter(item => item.width > 0 && item.position !== 'fixed' && (item.right > window.innerWidth + 4 || item.left < -4))
          .slice(0, 4)
        return { title: document.title, brokenText, overflow }
      })

      if (audit.brokenText || audit.overflow.length || consoleErrors.length || pageErrors.length) {
        await page.screenshot({ path: `${screenshotsDir}/${stateName}-${viewportName}-${label}.png`, fullPage: true })
      }

      results.push({
        state: stateName,
        viewport: viewportName,
        label,
        route,
        brokenText: audit.brokenText,
        overflow: audit.overflow,
        consoleErrors: consoleErrors.splice(0),
        pageErrors: pageErrors.splice(0),
      })
    }

    await context.close()
  }

  await browser.close()
  return results
}

const allResults = [
  ...(await runState('guest')),
  ...(adminToken ? await runState('admin') : []),
]

const failures = allResults.filter(result => result.brokenText || result.overflow.length || result.consoleErrors.length || result.pageErrors.length)
console.log(JSON.stringify({ checked: allResults.length, failures }, null, 2))
