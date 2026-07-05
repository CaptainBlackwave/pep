<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">
          Vendor Access Requests
        </h1>
        <p class="mt-1 text-slate-400">
          Review and manage vendor access requests from users.
        </p>
      </div>
      <button
        class="inline-flex items-center justify-center rounded-xl bg-slate-700 px-4 py-2 text-slate-200 transition-colors hover:bg-slate-600"
        @click="fetchRequests"
      >
        Refresh
      </button>
    </div>

    <div
      v-if="error"
      class="rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300"
    >
      {{ error }}
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-700/50 bg-slate-800/50">
      <div
        v-if="loading"
        class="p-10 text-center text-slate-400"
      >
        Loading requests...
      </div>
      <div
        v-else-if="requests.length === 0"
        class="p-10 text-center text-slate-400"
      >
        No vendor access requests found.
      </div>
      <div
        v-else
        class="divide-y divide-slate-700/60"
      >
        <article
          v-for="req in requests"
          :key="req.id"
          class="p-5 hover:bg-slate-700/25"
        >
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span :class="statusClass(req.status)">
                  {{ req.status }}
                </span>
                <span class="text-xs text-slate-500">
                  #{{ req.id }}
                </span>
              </div>
              <h2 class="mt-3 text-lg font-semibold text-white">
                {{ req.user?.username || 'Unknown user' }}
              </h2>
              <div class="mt-3 grid gap-1 text-sm text-slate-400 sm:grid-cols-2">
                <p>Email: {{ req.user?.email || 'N/A' }}</p>
                <p>Requested: {{ formatDate(req.created_at) }}</p>
              </div>
              <p
                v-if="req.admin_note"
                class="mt-2 text-sm text-slate-400"
              >
                Note: {{ req.admin_note }}
              </p>
            </div>
            <div
              v-if="req.status === 'pending'"
              class="flex shrink-0 gap-2"
            >
              <button
                class="rounded-lg bg-emerald-500/15 px-3 py-1.5 text-xs font-medium text-emerald-300 transition-colors hover:bg-emerald-500/25"
                @click="approveRequest(req)"
              >
                Approve
              </button>
              <button
                class="rounded-lg bg-red-500/15 px-3 py-1.5 text-xs font-medium text-red-300 transition-colors hover:bg-red-500/25"
                @click="denyRequest(req)"
              >
                Deny
              </button>
            </div>
          </div>
        </article>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const requests = ref([])
const loading = ref(false)
const error = ref('')

async function fetchRequests() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/vendor-access/requests')
    requests.value = response.data.data || []
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load requests.'
  } finally {
    loading.value = false
  }
}

async function approveRequest(req) {
  error.value = ''

  try {
    await api.patch(`/admin/vendor-access/requests/${req.id}`, {
      status: 'approved',
    })
    await fetchRequests()
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to approve request.'
  }
}

async function denyRequest(req) {
  error.value = ''

  try {
    await api.patch(`/admin/vendor-access/requests/${req.id}`, {
      status: 'denied',
    })
    await fetchRequests()
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to deny request.'
  }
}

function statusClass(status) {
  const classes = {
    pending: 'bg-amber-500/15 text-amber-300',
    approved: 'bg-emerald-500/15 text-emerald-300',
    denied: 'bg-red-500/15 text-red-300',
  }

  return `rounded-lg px-2 py-1 text-xs capitalize ${classes[status] || 'bg-slate-500/20 text-slate-300'}`
}

function formatDate(value) {
  if (!value) return 'N/A'
  return new Date(value).toLocaleString()
}

onMounted(() => {
  fetchRequests()
})
</script>
