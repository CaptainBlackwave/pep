<template>
  <div class="space-y-6">
    <!-- Header & Stats -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-white">Jail Management</h1>
        <p class="text-slate-400 text-sm mt-1">
          <span class="text-amber-400 font-semibold">{{ total }}</span> player{{ total !== 1 ? 's' : '' }} currently in jail
        </p>
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

    <!-- Table -->
    <div v-else class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-700/50 border-b border-slate-600/50">
            <tr>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Username</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Level</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Jail Until</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Time Remaining</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Type</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Bust Chance</th>
              <th class="px-6 py-4 text-center text-sm font-semibold text-slate-300">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50">
            <tr v-if="players.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-slate-500">No players currently in jail</td>
            </tr>
            <tr v-for="player in players" :key="player.id" class="hover:bg-slate-700/25 transition-colors">
              <td class="px-6 py-4 text-sm text-white font-medium">{{ player.username }}</td>
              <td class="px-6 py-4 text-sm text-slate-300">{{ player.level }}</td>
              <td class="px-6 py-4 text-sm text-slate-300">{{ formatDate(player.jail_until) }}</td>
              <td class="px-6 py-4 text-sm text-amber-400 font-medium">{{ player.time_remaining }}</td>
              <td class="px-6 py-4">
                <span v-if="player.is_super_max" class="px-2 py-1 rounded-md text-xs font-bold bg-red-500/20 text-red-400">Super Max</span>
                <span v-else class="px-2 py-1 rounded-md text-xs font-medium bg-slate-500/20 text-slate-400">Regular</span>
              </td>
              <td class="px-6 py-4 text-sm text-slate-300">
                <span v-if="player.is_super_max" class="text-red-400">—</span>
                <span v-else>{{ player.bust_chance }}%</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center">
                  <button @click="releasePlayer(player)" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-green-500/20 text-green-400 hover:bg-green-500/30 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    Release
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const toast = useToast()
const confirm = useConfirm()

const players = ref([])
const total = ref(0)
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
    const response = await api.get('/admin/jailed-players')
    players.value = response.data.players || []
    total.value = response.data.total || players.value.length
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load jailed players'
  } finally {
    loading.value = false
  }
}

const releasePlayer = async (player) => {
  const confirmed = await confirm.confirm(
    `Release "${player.username}" from jail? They will be freed immediately.`,
    'Release Player'
  )
  if (!confirmed) return
  try {
    const response = await api.post(`/admin/jailed-players/${player.id}/release`)
    toast.success(response.data.message || `${player.username} released from jail`)
    fetchData()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to release player')
  }
}
</script>
