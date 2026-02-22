<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Leaderboards</h1>
        <p class="text-slate-400 text-sm mt-1">Top players across all categories</p>
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

    <template v-else-if="data">
      <!-- Tab Selector -->
      <div class="flex flex-wrap gap-2">
        <button
          v-for="type in data.types"
          :key="type.id"
          @click="activeTab = type.id"
          :class="activeTab === type.id
            ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/25'
            : 'bg-slate-800/50 border border-slate-700/50 text-slate-300 hover:bg-slate-700/50'"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-all"
        >
          {{ type.name }}
        </button>
      </div>

      <!-- Active Board -->
      <div v-if="activeBoard" class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-white">{{ activeType?.name }} Leaderboard</h2>
            <p class="text-slate-400 text-sm">{{ activeType?.description }}</p>
          </div>
          <span class="text-slate-500 text-sm">Top 10</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-700/50 border-b border-slate-600/50">
              <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Rank</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Player / Name</th>
                <th v-for="col in boardColumns" :key="col" class="px-6 py-4 text-left text-sm font-semibold text-slate-300">{{ formatColLabel(col) }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
              <tr v-if="boardRows.length === 0">
                <td :colspan="3 + boardColumns.length" class="px-6 py-12 text-center text-slate-500">No data available</td>
              </tr>
              <tr v-for="(row, index) in boardRows" :key="index" class="hover:bg-slate-700/25 transition-colors">
                <td class="px-6 py-4">
                  <span class="text-sm font-bold" :class="index === 0 ? 'text-amber-400' : index === 1 ? 'text-slate-300' : index === 2 ? 'text-orange-400' : 'text-slate-500'">#{{ index + 1 }}</span>
                </td>
                <td class="px-6 py-4 text-sm text-white font-medium">{{ row.username ?? row.name }}</td>
                <td v-for="col in boardColumns" :key="col" class="px-6 py-4 text-sm text-slate-300">
                  {{ formatValue(row[col]) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const data = ref(null)
const loading = ref(false)
const error = ref(null)
const activeTab = ref('level')

const activeType = computed(() => data.value?.types?.find(t => t.id === activeTab.value))

const activeBoard = computed(() => {
  if (!data.value?.boards) return null
  const board = data.value.boards[activeTab.value]
  if (!board) return null
  // Handle paginated or plain array
  return Array.isArray(board) ? board : (board.data || [])
})

const boardRows = computed(() => activeBoard.value || [])

const boardColumns = computed(() => {
  if (!boardRows.value.length) return []
  const skip = new Set(['id', 'username', 'name', 'avatar', 'rank_title', 'tag'])
  return Object.keys(boardRows.value[0]).filter(k => !skip.has(k))
})

const formatColLabel = (col) => col.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())

const formatValue = (val) => {
  if (val === null || val === undefined) return '—'
  if (typeof val === 'number') return val.toLocaleString()
  return val
}

onMounted(() => fetchData())

const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/admin/leaderboards')
    data.value = response.data
    if (data.value.types?.length) {
      activeTab.value = data.value.types[0].id
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load leaderboards'
  } finally {
    loading.value = false
  }
}
</script>
