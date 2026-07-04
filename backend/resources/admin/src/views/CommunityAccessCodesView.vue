<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">
          Access Codes
        </h1>
        <p class="mt-1 text-slate-400">
          Generate one-use invite codes for private community registration.
        </p>
      </div>
      <button
        class="inline-flex items-center justify-center rounded-xl bg-slate-700 px-4 py-2 text-slate-200 transition-colors hover:bg-slate-600"
        @click="fetchCodes"
      >
        Refresh
      </button>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-2xl border border-slate-700/50 bg-slate-800/50 p-5"
      >
        <p class="text-sm text-slate-400">
          {{ card.label }}
        </p>
        <strong class="mt-2 block text-2xl text-white">{{ card.value }}</strong>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[420px_1fr]">
      <form
        class="space-y-4 rounded-2xl border border-slate-700/50 bg-slate-800/50 p-5"
        @submit.prevent="generateCode"
      >
        <div>
          <h2 class="text-lg font-semibold text-white">
            New Access Code
          </h2>
          <p class="text-sm text-slate-400">
            The generated code is shown once and stored only as a secure hash.
          </p>
        </div>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Label</span>
          <input
            v-model="form.label"
            maxlength="160"
            class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-900/60 px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="e.g. July approved member"
          >
        </label>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Expires At</span>
          <input
            v-model="form.expires_at"
            type="datetime-local"
            class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-900/60 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
          >
        </label>

        <div
          v-if="generatedCode"
          class="rounded-xl border border-violet-500/40 bg-violet-500/10 p-4"
        >
          <p class="text-xs font-semibold uppercase tracking-wide text-violet-300">
            Copy this code now
          </p>
          <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
            <code class="flex-1 rounded-lg border border-violet-500/30 bg-slate-950/70 px-3 py-2 text-lg font-semibold text-white">
              {{ generatedCode }}
            </code>
            <button
              type="button"
              class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-violet-700"
              @click="copyGeneratedCode"
            >
              Copy
            </button>
          </div>
          <p class="mt-3 text-xs text-slate-400">
            For security, the plaintext code will disappear when you leave or generate another one.
          </p>
        </div>

        <div
          v-if="error"
          class="rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300"
        >
          {{ error }}
        </div>

        <button
          type="submit"
          :disabled="saving"
          class="w-full rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-3 font-medium text-white transition-all hover:from-violet-700 hover:to-indigo-700 disabled:opacity-60"
        >
          {{ saving ? 'Generating...' : 'Generate Access Code' }}
        </button>
      </form>

      <div class="space-y-4">
        <div class="rounded-2xl border border-slate-700/50 bg-slate-800/50 p-5">
          <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_180px_auto]">
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search labels..."
              class="w-full rounded-xl border border-slate-700 bg-slate-900/60 px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
              @keydown.enter="fetchCodes"
            >
            <select
              v-model="filters.status"
              class="w-full rounded-xl border border-slate-700 bg-slate-900/60 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            >
              <option value="all">
                All statuses
              </option>
              <option value="available">
                Available
              </option>
              <option value="used">
                Used
              </option>
              <option value="revoked">
                Revoked
              </option>
              <option value="expired">
                Expired
              </option>
            </select>
            <button
              class="rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-3 font-medium text-white transition-all hover:from-violet-700 hover:to-indigo-700"
              @click="fetchCodes"
            >
              Apply
            </button>
          </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-700/50 bg-slate-800/50">
          <div
            v-if="loading"
            class="p-10 text-center text-slate-400"
          >
            Loading access codes...
          </div>
          <div
            v-else-if="codes.length === 0"
            class="p-10 text-center text-slate-400"
          >
            No access codes found.
          </div>
          <div
            v-else
            class="divide-y divide-slate-700/60"
          >
            <article
              v-for="code in codes"
              :key="code.id"
              class="p-5 hover:bg-slate-700/25"
            >
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                  <div class="flex flex-wrap items-center gap-2">
                    <span :class="statusClass(code.status)">
                      {{ code.status }}
                    </span>
                    <span class="text-xs text-slate-500">
                      #{{ code.id }}
                    </span>
                  </div>
                  <h2 class="mt-3 text-lg font-semibold text-white">
                    {{ code.label || 'Unlabelled access code' }}
                  </h2>
                  <div class="mt-3 grid gap-1 text-sm text-slate-400 sm:grid-cols-2">
                    <p>Created: {{ formatDate(code.created_at) }}</p>
                    <p>Expires: {{ formatDate(code.expires_at) }}</p>
                    <p>Created by: {{ code.created_by?.username || 'System' }}</p>
                    <p>Used by: {{ code.used_by?.username || 'Not used' }}</p>
                  </div>
                </div>
                <button
                  v-if="code.status === 'available'"
                  class="rounded-lg bg-red-500/15 px-3 py-1.5 text-xs font-medium text-red-300 transition-colors hover:bg-red-500/25"
                  @click="revokeCode(code)"
                >
                  Revoke
                </button>
              </div>
            </article>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../services/api'

const codes = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const generatedCode = ref('')
const stats = ref({
  total: 0,
  available: 0,
  used: 0,
  revoked: 0,
  expired: 0,
})

const form = reactive({
  label: '',
  expires_at: '',
})

const filters = reactive({
  search: '',
  status: 'all',
})

const statCards = computed(() => [
  { label: 'Total', value: stats.value.total },
  { label: 'Available', value: stats.value.available },
  { label: 'Used', value: stats.value.used },
  { label: 'Revoked', value: stats.value.revoked },
  { label: 'Expired', value: stats.value.expired },
])

async function fetchCodes() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/community/access-codes', {
      params: {
        search: filters.search || undefined,
        status: filters.status,
      },
    })
    codes.value = response.data.data || []
    stats.value = response.data.meta?.stats || stats.value
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load access codes.'
  } finally {
    loading.value = false
  }
}

async function generateCode() {
  saving.value = true
  error.value = ''
  generatedCode.value = ''

  try {
    const response = await api.post('/admin/community/access-codes', {
      label: form.label || null,
      expires_at: form.expires_at || null,
    })
    generatedCode.value = response.data.data?.code || ''
    form.label = ''
    form.expires_at = ''
    await fetchCodes()
  } catch (err) {
    const errors = err.response?.data?.errors
    error.value = errors ? Object.values(errors)[0]?.[0] || 'Unable to generate access code.' : err.response?.data?.message || 'Unable to generate access code.'
  } finally {
    saving.value = false
  }
}

async function revokeCode(code) {
  error.value = ''

  try {
    await api.delete(`/admin/community/access-codes/${code.id}`)
    await fetchCodes()
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to revoke access code.'
  }
}

async function copyGeneratedCode() {
  if (!generatedCode.value || !navigator.clipboard) {
    return
  }

  await navigator.clipboard.writeText(generatedCode.value)
}

function statusClass(status) {
  const classes = {
    available: 'bg-emerald-500/15 text-emerald-300',
    used: 'bg-sky-500/15 text-sky-300',
    revoked: 'bg-red-500/15 text-red-300',
    expired: 'bg-amber-500/15 text-amber-300',
  }

  return `rounded-lg px-2 py-1 text-xs capitalize ${classes[status] || 'bg-slate-500/20 text-slate-300'}`
}

function formatDate(value) {
  if (!value) {
    return 'None'
  }

  return new Date(value).toLocaleString()
}

onMounted(fetchCodes)
</script>
