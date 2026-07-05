<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">
          Vendor Claims
        </h1>
        <p class="text-slate-400 mt-1">
          Approve vendor access requests and ownership claims without managing vendor profiles.
        </p>
      </div>
      <button
        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors"
        @click="fetchClaims"
      >
        Refresh
      </button>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-2xl bg-slate-800/50 border border-slate-700/50 p-5"
      >
        <p class="text-sm text-slate-400">
          {{ card.label }}
        </p>
        <strong class="block text-2xl text-white mt-2">{{ card.value }}</strong>
      </div>
    </div>

    <div class="rounded-2xl bg-slate-800/50 border border-slate-700/50 p-5">
      <div class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-3">
        <input
          v-model="filters.search"
          type="search"
          placeholder="Search vendor, applicant, email, or note..."
          class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
          @keydown.enter="fetchClaims"
        >
        <select
          v-model="filters.status"
          class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
        >
          <option value="pending">
            Pending
          </option>
          <option value="approved">
            Approved
          </option>
          <option value="rejected">
            Rejected
          </option>
          <option value="all">
            All
          </option>
        </select>
        <button
          class="px-5 py-3 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-medium hover:from-violet-700 hover:to-indigo-700 transition-all"
          @click="fetchClaims"
        >
          Apply
        </button>
      </div>
    </div>

    <div
      v-if="error"
      class="rounded-2xl bg-red-500/10 border border-red-500/30 p-5 text-red-300"
    >
      {{ error }}
    </div>

    <div class="rounded-2xl bg-slate-800/50 border border-slate-700/50 overflow-hidden">
      <div
        v-if="loading"
        class="p-10 text-center text-slate-400"
      >
        Loading vendor claims...
      </div>
      <div
        v-else-if="claims.length === 0"
        class="p-10 text-center text-slate-400"
      >
        No vendor claims found.
      </div>
      <div
        v-else
        class="divide-y divide-slate-700/60"
      >
        <article
          v-for="claim in claims"
          :key="claim.id"
          class="p-5 hover:bg-slate-700/25"
        >
          <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0 max-w-4xl">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-semibold text-white">
                  {{ claim.vendor?.name || 'Vendor profile' }}
                </h2>
                <span :class="statusClass(claim.status)">
                  {{ claim.status }}
                </span>
                <span :class="vendorStatusClass(claim.vendor?.status)">
                  {{ claim.vendor?.status || 'unknown' }}
                </span>
              </div>
              <p class="mt-1 text-sm text-slate-400">
                Requested by {{ claim.user?.name || 'Unknown user' }}
                <span v-if="claim.user?.email">· {{ claim.user.email }}</span>
              </p>
              <p
                v-if="claim.vendor?.description"
                class="mt-3 text-sm text-slate-300 line-clamp-3"
              >
                {{ claim.vendor.description }}
              </p>
              <p
                v-if="claim.message"
                class="mt-3 rounded-xl border border-slate-700 bg-slate-950/40 px-4 py-3 text-sm text-slate-300"
              >
                {{ claim.message }}
              </p>
              <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                <span>Claim #{{ claim.id }}</span>
                <span v-if="claim.vendor?.slug">Slug: {{ claim.vendor.slug }}</span>
                <span v-if="claim.created_at">Submitted {{ formatDate(claim.created_at) }}</span>
                <span v-if="claim.reviewed_at">Reviewed {{ formatDate(claim.reviewed_at) }}</span>
              </div>
            </div>
            <div class="flex flex-wrap gap-2 xl:justify-end">
              <a
                v-if="claim.vendor?.website_url"
                :href="claim.vendor.website_url"
                target="_blank"
                rel="noreferrer"
                class="px-3 py-2 rounded-lg text-xs font-medium bg-slate-500/15 text-slate-300 hover:bg-slate-500/25"
              >
                Website
              </a>
              <button
                class="px-3 py-2 rounded-lg text-xs font-medium bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="claim.status === 'approved' || updatingClaim === claim.id"
                @click="updateClaim(claim, 'approved')"
              >
                Approve
              </button>
              <button
                class="px-3 py-2 rounded-lg text-xs font-medium bg-red-500/15 text-red-300 hover:bg-red-500/25 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="claim.status === 'rejected' || updatingClaim === claim.id"
                @click="updateClaim(claim, 'rejected')"
              >
                Reject
              </button>
            </div>
          </div>
        </article>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../services/api'

const claims = ref([])
const loading = ref(false)
const error = ref('')
const updatingClaim = ref(null)
const stats = ref({
  total: 0,
  pending: 0,
  approved: 0,
  rejected: 0,
})

const filters = reactive({
  search: '',
  status: 'pending',
})

const statCards = computed(() => [
  { label: 'Total', value: stats.value.total },
  { label: 'Pending', value: stats.value.pending },
  { label: 'Approved', value: stats.value.approved },
  { label: 'Rejected', value: stats.value.rejected },
])

async function fetchClaims() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/community/vendor-claims', {
      params: {
        search: filters.search || undefined,
        status: filters.status,
      },
    })
    claims.value = response.data.data || []
    stats.value = response.data.meta?.stats || stats.value
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load vendor claims.'
  } finally {
    loading.value = false
  }
}

async function updateClaim(claim, status) {
  updatingClaim.value = claim.id
  error.value = ''

  try {
    const response = await api.patch(`/admin/community/vendor-claims/${claim.id}`, { status })
    const updated = response.data.data
    claims.value = claims.value.map(item => item.id === updated.id ? updated : item)
    await fetchClaims()
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to update vendor claim.'
  } finally {
    updatingClaim.value = null
  }
}

function statusClass(status) {
  if (status === 'approved') {
    return 'px-2 py-1 rounded-lg bg-emerald-500/15 text-emerald-300 text-xs capitalize'
  }

  if (status === 'rejected') {
    return 'px-2 py-1 rounded-lg bg-red-500/15 text-red-300 text-xs capitalize'
  }

  return 'px-2 py-1 rounded-lg bg-amber-500/15 text-amber-300 text-xs capitalize'
}

function vendorStatusClass(status) {
  return status === 'published'
    ? 'px-2 py-1 rounded-lg bg-sky-500/15 text-sky-300 text-xs capitalize'
    : 'px-2 py-1 rounded-lg bg-slate-500/20 text-slate-300 text-xs capitalize'
}

function formatDate(value) {
  return new Date(value).toLocaleString()
}

onMounted(fetchClaims)
</script>
