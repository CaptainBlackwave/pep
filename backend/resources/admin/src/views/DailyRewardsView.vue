<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Daily Rewards</h1>
        <p class="text-slate-400 text-sm mt-1">Overview of player daily login streaks</p>
      </div>
      <button @click="fetchData" :disabled="loading" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-700 text-slate-300 hover:bg-slate-600 transition-colors">
        <svg :class="loading ? 'animate-spin' : ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        Refresh
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-12 flex justify-center">
      <div class="w-8 h-8 border-2 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-2xl bg-red-500/10 border border-red-500/30 p-6">
      <div class="flex items-center gap-3">
        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        <p class="text-red-400 font-medium">{{ error }}</p>
      </div>
    </div>

    <template v-else-if="stats">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-5">
          <p class="text-slate-400 text-xs font-medium uppercase tracking-wider">Total Claimants</p>
          <p class="text-3xl font-bold text-white mt-2">{{ stats.total_claimants?.toLocaleString() }}</p>
        </div>
        <div class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-5">
          <p class="text-slate-400 text-xs font-medium uppercase tracking-wider">Claimed Today</p>
          <p class="text-3xl font-bold text-amber-400 mt-2">{{ stats.claimed_today?.toLocaleString() }}</p>
        </div>
        <div class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-5">
          <p class="text-slate-400 text-xs font-medium uppercase tracking-wider">30+ Day Streak</p>
          <p class="text-3xl font-bold text-green-400 mt-2">{{ stats.streak_breakdown?.month_plus ?? 0 }}</p>
        </div>
        <div class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-5">
          <p class="text-slate-400 text-xs font-medium uppercase tracking-wider">7+ Day Streak</p>
          <p class="text-3xl font-bold text-blue-400 mt-2">{{ stats.streak_breakdown?.week_plus ?? 0 }}</p>
        </div>
        <div class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-5">
          <p class="text-slate-400 text-xs font-medium uppercase tracking-wider">3-6 Day Streak</p>
          <p class="text-3xl font-bold text-purple-400 mt-2">{{ stats.streak_breakdown?.few_days ?? 0 }}</p>
        </div>
        <div class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-5">
          <p class="text-slate-400 text-xs font-medium uppercase tracking-wider">New Players</p>
          <p class="text-3xl font-bold text-slate-400 mt-2">{{ stats.streak_breakdown?.new_players ?? 0 }}</p>
        </div>
      </div>

      <!-- Top Streaks Table -->
      <div class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-700/50">
          <h2 class="text-lg font-semibold text-white">Top Streaks</h2>
          <p class="text-slate-400 text-sm">Top 10 players by current streak</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-700/50 border-b border-slate-600/50">
              <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">#</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Username</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Streak</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Last Claimed</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
              <tr v-if="!stats.top_streaks || stats.top_streaks.length === 0">
                <td colspan="4" class="px-6 py-12 text-center text-slate-500">No data available</td>
              </tr>
              <tr v-for="(entry, index) in stats.top_streaks" :key="entry.id" class="hover:bg-slate-700/25 transition-colors">
                <td class="px-6 py-4 text-sm font-bold" :class="index === 0 ? 'text-amber-400' : index === 1 ? 'text-slate-300' : index === 2 ? 'text-orange-400' : 'text-slate-500'">
                  #{{ index + 1 }}
                </td>
                <td class="px-6 py-4 text-sm text-white font-medium">{{ entry.user?.username ?? 'Unknown' }}</td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-bold" :class="entry.streak >= 30 ? 'bg-green-500/20 text-green-400' : entry.streak >= 7 ? 'bg-blue-500/20 text-blue-400' : 'bg-slate-500/20 text-slate-400'">
                    {{ entry.streak }} days
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-slate-300">{{ formatDate(entry.last_claimed_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const stats = ref(null)
const loading = ref(false)
const error = ref(null)

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleString()
}

onMounted(() => fetchData())

const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/admin/daily-rewards')
    stats.value = response.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load daily rewards data'
  } finally {
    loading.value = false
  }
}
</script>
