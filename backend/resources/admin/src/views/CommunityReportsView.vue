<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Moderation Queue</h1>
        <p class="text-slate-400 mt-1">Review reported discussions and replies.</p>
      </div>
      <button class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors" @click="fetchReports">
        Refresh
      </button>
    </div>

    <div class="grid grid-cols-3 gap-4">
      <div v-for="card in statCards" :key="card.label" class="rounded-2xl bg-slate-800/50 border border-slate-700/50 p-5">
        <p class="text-sm text-slate-400">{{ card.label }}</p>
        <strong class="block text-2xl text-white mt-2">{{ card.value }}</strong>
      </div>
    </div>

    <div class="rounded-2xl bg-slate-800/50 border border-slate-700/50 p-5">
      <div class="grid grid-cols-1 md:grid-cols-[180px_auto] gap-3">
        <select v-model="filters.status" class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-amber-500/50">
          <option value="open">Open</option>
          <option value="reviewed">Reviewed</option>
          <option value="dismissed">Dismissed</option>
          <option value="all">All</option>
        </select>
        <button class="px-5 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium hover:from-amber-600 hover:to-orange-700 transition-all" @click="fetchReports">
          Apply
        </button>
      </div>
    </div>

    <div v-if="error" class="rounded-2xl bg-red-500/10 border border-red-500/30 p-5 text-red-300">{{ error }}</div>

    <div class="rounded-2xl bg-slate-800/50 border border-slate-700/50 overflow-hidden">
      <div v-if="loading" class="p-10 text-center text-slate-400">Loading reports...</div>
      <div v-else-if="reports.length === 0" class="p-10 text-center text-slate-400">No reports found.</div>
      <table v-else class="w-full">
        <thead>
          <tr class="border-b border-slate-700/50 text-left text-sm text-slate-400">
            <th class="p-4">Type</th>
            <th class="p-4">Reason</th>
            <th class="p-4">Target</th>
            <th class="p-4">Reported By</th>
            <th class="p-4">Date</th>
            <th class="p-4">Status</th>
            <th class="p-4">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="report in reports" :key="report.id" class="border-b border-slate-700/30 hover:bg-slate-700/20 transition-colors">
            <td class="p-4">
              <span class="px-2 py-1 rounded text-xs font-medium" :class="report.target_type === 'discussion' ? 'bg-blue-500/20 text-blue-300' : 'bg-purple-500/20 text-purple-300'">
                {{ report.target_type }}
              </span>
            </td>
            <td class="p-4">
              <span class="text-white font-medium">{{ report.reason }}</span>
              <p v-if="report.details" class="text-slate-400 text-xs mt-1 max-w-xs truncate">{{ report.details }}</p>
            </td>
            <td class="p-4">
              <p class="text-white text-sm font-medium truncate max-w-[200px]">{{ report.target?.title || 'N/A' }}</p>
              <p class="text-slate-400 text-xs">by {{ report.target?.author || 'unknown' }}</p>
            </td>
            <td class="p-4 text-slate-300 text-sm">{{ report.reporter?.name || 'Anonymous' }}</td>
            <td class="p-4 text-slate-400 text-sm">{{ formatDate(report.created_at) }}</td>
            <td class="p-4">
              <span class="px-2 py-1 rounded text-xs font-medium" :class="statusClass(report.status)">
                {{ report.status }}
              </span>
            </td>
            <td class="p-4">
              <div v-if="report.status === 'open'" class="flex gap-2">
                <button class="px-3 py-1.5 rounded-lg bg-emerald-600/20 text-emerald-300 text-xs font-medium hover:bg-emerald-600/40 transition-colors" @click="actionReport(report.id, 'reviewed')">
                  Reviewed
                </button>
                <button class="px-3 py-1.5 rounded-lg bg-slate-600/20 text-slate-300 text-xs font-medium hover:bg-slate-600/40 transition-colors" @click="actionReport(report.id, 'dismissed')">
                  Dismiss
                </button>
              </div>
              <span v-else class="text-slate-500 text-xs">No action needed</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import api from '../services/api.js'

export default {
  name: 'CommunityReportsView',
  data() {
    return {
      loading: false,
      error: '',
      reports: [],
      stats: { open: 0, reviewed: 0, dismissed: 0 },
      filters: { status: 'open' },
    }
  },
  computed: {
    statCards() {
      return [
        { label: 'Open', value: this.stats.open },
        { label: 'Reviewed', value: this.stats.reviewed },
        { label: 'Dismissed', value: this.stats.dismissed },
      ]
    },
  },
  mounted() {
    this.fetchReports()
  },
  methods: {
    async fetchReports() {
      this.loading = true
      this.error = ''
      try {
        const res = await api.get('/admin/community/reports', {
          params: { status: this.filters.status === 'all' ? undefined : this.filters.status },
        })
        this.reports = res.data.data
        this.stats = res.data.meta.stats
      } catch (e) {
        this.error = e.response?.data?.message || 'Failed to load reports.'
      } finally {
        this.loading = false
      }
    },
    async actionReport(id, status) {
      try {
        await api.patch(`/admin/community/reports/${id}`, { status })
        await this.fetchReports()
      } catch (e) {
        this.error = e.response?.data?.message || 'Failed to update report.'
      }
    },
    statusClass(status) {
      return {
        open: 'bg-amber-500/20 text-amber-300',
        reviewed: 'bg-emerald-500/20 text-emerald-300',
        dismissed: 'bg-slate-500/20 text-slate-300',
      }[status] || 'bg-slate-500/20 text-slate-300'
    },
    formatDate(date) {
      if (!date) return ''
      const d = new Date(date)
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    },
  },
}
</script>
