<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-white">Account Duplication</h1>
    </div>

    <div v-if="loading" class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-12">
      <div class="flex flex-col items-center justify-center">
        <div class="w-12 h-12 border-4 border-amber-500/30 border-t-amber-500 rounded-full animate-spin mb-4"></div>
        <p class="text-slate-400">Checking for duplicate accounts...</p>
      </div>
    </div>

    <div v-else-if="groups.length === 0" class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-12">
      <div class="flex flex-col items-center justify-center">
        <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 flex items-center justify-center mb-4">
          <ShieldCheckIcon class="w-8 h-8 text-emerald-400" />
        </div>
        <h3 class="text-lg font-semibold text-white mb-2">No duplicates found</h3>
        <p class="text-slate-400 text-center max-w-sm">Every IP address has at most one account registered.</p>
      </div>
    </div>

    <div v-else class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-700/50 border-b border-slate-600/50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">IP Address</th>
              <th class="px-6 py-4 text-center text-xs font-semibold text-slate-400 uppercase tracking-wider">Accounts</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Usernames</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50">
            <tr v-for="group in groups" :key="group.last_ip" class="hover:bg-slate-700/25 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <GlobeAltIcon class="w-5 h-5 text-amber-400" />
                  </div>
                  <span class="font-mono text-sm text-white">{{ group.last_ip }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-500/20 text-red-400 text-sm font-bold">
                  {{ group.count }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="(username, idx) in group.usernames.split(',')"
                    :key="idx"
                    class="inline-flex items-center px-3 py-1 rounded-lg bg-slate-700/50 text-slate-200 text-sm"
                  >
                    {{ username }}
                  </span>
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
import { ShieldCheckIcon, GlobeAltIcon } from '@heroicons/vue/24/outline'

const toast = useToast()
const groups = ref([])
const loading = ref(false)

onMounted(() => fetchGroups())

const fetchGroups = async () => {
  loading.value = true
  try {
    const response = await api.get('/admin/account-duplication')
    const payload = response.data
    groups.value = Array.isArray(payload) ? payload : []
  } catch (err) {
    toast.error('Failed to load duplication data')
  } finally {
    loading.value = false
  }
}
</script>
