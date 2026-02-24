/**
 * Bank Plugin - Frontend Module
 *
 * This is the main entry point for the Bank plugin's frontend.
 * It exports the plugin's metadata and capabilities.
 */

export const name = 'Bank'
export const slug = 'bank'
export const version = '1.0.0'
export const description = 'Banking system for depositing, withdrawing, and transferring money'

/**
 * Plugin configuration
 */
export const config = {
  icon: '🏦',
  color: 'green',
  menu: {
    enabled: true,
    order: 10,
    section: 'actions',
  },
}

/**
 * Plugin slots - components that can be injected into UI areas
 */
export const slots = {
  'dashboard-widget': ['components/BankWidget.vue'],
  'sidebar-widget': ['components/QuickBalance.vue'],
}

/**
 * Plugin initialization function
 */
export async function initialize() {
  console.log(`[${slug}] Plugin initialized`)
}

export default {
  name,
  slug,
  version,
  description,
  config,
  slots,
  initialize,
}
