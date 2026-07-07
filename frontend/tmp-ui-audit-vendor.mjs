import { chromium } from '@playwright/test'

const baseUrl = 'http://127.0.0.1:5173'
const screenshotsDir = '/private/tmp/forum-vendor-ui-audit'

const adminUser = {
  id: 9,
  name: 'Administrator',
  username: 'admin',
  email: 'admin@example.com',
  is_approved_vendor: true,
  tier: 'paid',
  roles: ['admin'],
}

const adminToken = process.env.ADMIN_TOKEN ?? ''

const sampleVendorDetail = {
  data: {
    id: 1,
    owner_user_id: null,
    claim_status: 'unclaimed',
    is_owned_by_viewer: false,
    name: 'Linyi Xinhao Peptides',
    slug: 'linyi-xinhao-peptides',
    logo_initials: 'LX',
    logo_text: 'LX',
    logo_class: 'purple',
    status_label: 'Trusted',
    status_class: 'trusted',
    country: null,
    tone: 'green',
    description: 'International peptide vendor profile with community-submitted reviews, product listings, and public support details.',
    website_url: 'https://example.com',
    image_url: null,
    contact: {
      email: 'support@example.com',
      telegram: '@linyipeptides',
      signal: null,
      discord: null,
      support_url: 'https://example.com/support',
      response_policy: 'Replies within 1-2 working days.',
      public_notes: 'Product listings are informational only.',
    },
    member_since: '2026-07-04',
    member_since_label: 'Jul 2026',
    last_active_at: '2026-07-07T20:00:00+00:00',
    last_active_label: 'today',
    review_count: 1,
    average_rating: 5,
    rating_label: '5.0',
    would_buy_again_percent: 100,
    would_buy_again_label: '100%',
    response_rate_percent: 92,
    response_rate_label: '92%',
    avg_response_time: '1 day',
    tags: ['China Vendor', 'Lab Tested', 'Bulk Options'],
    product_count: 2,
    products: [
      {
        id: 101,
        vendor_id: 1,
        name: 'Retatrutide Research Peptide',
        slug: 'retatrutide-research-peptide',
        category: 'Peptide',
        strength: '10mg',
        package_size: '1 vial',
        purity_label: '>98%',
        description: 'Public product listing for research-use inquiries with variant pricing and availability.',
        variants: [
          { label: '10mg', price: 85, availability: 'in_stock' },
          { label: '20mg', price: 150, availability: 'limited' },
        ],
        price: 85,
        price_label: '$85.00',
        currency_code: 'USD',
        availability: 'in_stock',
        availability_label: 'In stock',
        image_url: null,
        tags: ['GLP-1', 'Research', 'Vial'],
        average_rating: 4.8,
        rating_label: '4.8',
        review_count: 7,
        sort_order: 1,
        status: 'published',
        href: null,
      },
      {
        id: 102,
        vendor_id: 1,
        name: 'BPC-157 Lab Reference',
        slug: 'bpc-157-lab-reference',
        category: 'Reference',
        strength: '5mg',
        package_size: 'Kit',
        purity_label: 'COA attached',
        description: 'Secondary listing to test wrapping, filters, and dense comparison cards.',
        variants: [],
        price: null,
        price_label: 'Contact for price',
        currency_code: 'USD',
        availability: 'limited',
        availability_label: 'Limited',
        image_url: null,
        tags: ['Reference', 'COA'],
        average_rating: 0,
        rating_label: '0.0',
        review_count: 0,
        sort_order: 2,
        status: 'published',
        href: null,
      },
    ],
    top_products: [],
    status: 'published',
    profile_submitted_at: null,
    href: '/vendor-reviews/linyi-xinhao-peptides',
    rating_distribution: [
      { rating: 5, count: 1, percent: 100 },
      { rating: 4, count: 0, percent: 0 },
      { rating: 3, count: 0, percent: 0 },
      { rating: 2, count: 0, percent: 0 },
      { rating: 1, count: 0, percent: 0 },
    ],
    review_items: [
      {
        id: 1,
        vendor_id: 1,
        title: 'awesome',
        body: 'awesome',
        rating: 5,
        product_name: 'reta',
        helpful_count: 0,
        vendor_response: null,
        responded_at: null,
        would_buy_again: true,
        is_verified_buyer: false,
        tags: [],
        photo_urls: [],
        status: 'published',
        reviewed_at: '2026-07-04',
        reviewed_date: 'Jul 4, 2026',
        author: { id: 9, name: 'admin', username: 'admin', initial: 'A' },
      },
    ],
    documents: [
      {
        id: 4,
        vendor_id: 1,
        title: 'Certificate of Analysis - Sample Batch',
        file_path: '/documents/sample-coa.pdf',
        file_type: 'pdf',
        category: 'coa',
        description: 'Public quality document example.',
        status: 'published',
        url: 'https://example.com/sample-coa.pdf',
      },
    ],
  },
}

const routes = [
  ['/vendor-reviews', 'vendors'],
  ['/vendor-reviews/linyi-xinhao-peptides', 'vendor-detail'],
  ['/vendor-reviews/linyi-xinhao-peptides/review', 'vendor-review-modal'],
  ['/vendor-portal', 'vendor-portal'],
  ['/discussions', 'discussions'],
  ['/messages', 'messages'],
]

async function captureState(stateName, storage) {
  const browser = await chromium.launch()
  const viewports = [
    ['mobile', { width: 390, height: 844 }],
    ['desktop', { width: 1440, height: 1000 }],
  ]

  for (const [viewportName, viewport] of viewports) {
    const context = await browser.newContext({ viewport, deviceScaleFactor: 1 })
    const page = await context.newPage()

    if (storage) {
      await page.route('**/api/v1/user', route => {
        route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(storage.user),
        })
      })
      await page.route('**/api/v1/membership/status', route => {
        route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ tier: 'paid', subscription: { status: 'active' } }),
        })
      })
      await page.route('**/api/v1/community/vendors/linyi-xinhao-peptides', route => {
        route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(sampleVendorDetail),
        })
      })

      await page.addInitScript(({ token, user }) => {
        localStorage.setItem('auth_token', token)
        localStorage.setItem('user', JSON.stringify(user))
        localStorage.setItem('cookie_consent', 'true')
      }, storage)
    } else {
      await page.addInitScript(() => localStorage.setItem('cookie_consent', 'true'))
    }

    for (const [route, label] of routes) {
      await page.goto(`${baseUrl}${route}`, { waitUntil: 'networkidle' })
      await page.screenshot({
        path: `${screenshotsDir}/${stateName}-${viewportName}-${label}.png`,
        fullPage: true,
      })
    }

    await context.close()
  }

  await browser.close()
}

await captureState('guest', null)

if (adminToken) {
  await captureState('admin', { token: adminToken, user: adminUser })
}
