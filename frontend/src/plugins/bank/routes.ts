/**
 * Bank Plugin - Route Definitions
 *
 * These routes will be dynamically loaded into the main router.
 */

import type { PluginRouteDefinition } from '@/types/plugin-route'

const routes: PluginRouteDefinition[] = [
  {
    path: '/bank',
    name: 'bank',
    component: 'BankView.vue',
    meta: {
      title: 'Bank',
      requiresAuth: true,
    },
  },
  {
    path: '/bank/transfer',
    name: 'bank-transfer',
    component: 'TransferView.vue',
    meta: {
      title: 'Bank Transfer',
      requiresAuth: true,
    },
  },
  {
    path: '/bank/history',
    name: 'bank-history',
    component: 'HistoryView.vue',
    meta: {
      title: 'Transaction History',
      requiresAuth: true,
    },
  },
]

export default routes
