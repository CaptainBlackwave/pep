<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">
          Community Lab Results
        </h1>
        <p class="text-slate-400 mt-1">
          Review, verify, publish, and hide submitted Peptide Vendors lab reports.
        </p>
      </div>
      <button
        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors"
        @click="fetchLabResults"
      >
        Refresh
      </button>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
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
          placeholder="Search compound, vendor, batch, or lab..."
          class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
          @keydown.enter="fetchLabResults"
        >
        <select
          v-model="filters.status"
          class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
        >
          <option value="all">
            All statuses
          </option>
          <option value="pending">
            Pending
          </option>
          <option value="published">
            Published
          </option>
          <option value="hidden">
            Hidden
          </option>
        </select>
        <button
          class="px-5 py-3 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-medium hover:from-violet-700 hover:to-indigo-700 transition-all"
          @click="fetchLabResults"
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
        Loading lab results...
      </div>

      <div
        v-else-if="labResults.length === 0"
        class="p-10 text-center text-slate-400"
      >
        No lab results found.
      </div>

      <div
        v-else
        class="overflow-x-auto"
      >
        <table class="w-full">
          <thead class="bg-slate-900/80 border-b border-slate-700">
            <tr>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                Report
              </th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                Lab
              </th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                Results
              </th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                Status
              </th>
              <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/60">
            <tr
              v-for="result in labResults"
              :key="result.id"
              class="hover:bg-slate-700/25"
            >
              <td class="px-5 py-4">
                <div class="max-w-xl">
                  <p class="font-semibold text-white">
                    {{ result.compound_name }}
                  </p>
                  <p class="text-sm text-slate-400">
                    {{ result.vendor_name }} · Batch {{ result.batch_code }}
                  </p>
                  <p v-if="result.submitted_by?.name" class="text-xs text-slate-500 mt-2">
                    Submitted by {{ result.submitted_by.name }}
                  </p>
                </div>
              </td>
              <td class="px-5 py-4 text-sm text-slate-300">
                <div>{{ result.lab_name }}</div>
                <div class="text-slate-500">
                  {{ result.tested_date ?? '' }}
                </div>
              </td>
              <td class="px-5 py-4 text-sm text-slate-300">
                <div class="text-emerald-300 font-semibold">
                  {{ result.purity ? `${result.purity} purity` : '' }}
                </div>
                <div class="text-slate-500">
                  {{ result.identity_result ?? '' }}
                </div>
              </td>
              <td class="px-5 py-4">
                <div class="flex flex-wrap gap-2">
                  <span :class="statusClass(result.status)">
                    {{ result.status }}
                  </span>
                  <span
                    v-if="result.is_verified"
                    class="px-2 py-1 rounded-lg bg-emerald-500/15 text-emerald-300 text-xs"
                  >Verified</span>
                </div>
              </td>
              <td class="px-5 py-4">
                <div class="flex flex-wrap justify-end gap-2">
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25"
                    @click="publishResult(result)"
                  >
                    Publish
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-violet-500/15 text-violet-300 hover:bg-violet-500/25"
                    @click="toggleVerified(result)"
                  >
                    {{ result.is_verified ? 'Unverify' : 'Verify' }}
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/15 text-red-300 hover:bg-red-500/25"
                    @click="hideResult(result)"
                  >
                    Hide
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
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../services/api'

const loading = ref(false)
const error = ref('')
const labResults = ref([])
const stats = ref({
  total: 0,
  pending: 0,
  published: 0,
  hidden: 0,
  verified: 0,
})

const filters = reactive({
  search: '',
  status: 'all',
})

const statCards = computed(() => [
  { label: 'Total', value: stats.value.total },
  { label: 'Pending', value: stats.value.pending },
  { label: 'Published', value: stats.value.published },
  { label: 'Hidden', value: stats.value.hidden },
  { label: 'Verified', value: stats.value.verified },
])

async function fetchLabResults() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/community/lab-results', {
      params: {
        search: filters.search || undefined,
        status: filters.status,
      },
    })
    labResults.value = response.data.data || []
    stats.value = response.data.meta?.stats || stats.value
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load community lab results.'
  } finally {
    loading.value = false
  }
}

async function updateResult(result, payload) {
  const response = await api.patch(`/admin/community/lab-results/${result.slug}`, payload)
  const updated = response.data.data
  const index = labResults.value.findIndex(item => item.id === updated.id)

  if (index >= 0) {
    labResults.value[index] = updated
  }

  await fetchLabResults()
}

function publishResult(result) {
  void updateResult(result, {
    status: 'published',
    is_verified: true,
    overall_result: result.overall_result && result.overall_result !== 'Pending Review'
      ? result.overall_result
      : 'Pass',
  })
}

function toggleVerified(result) {
  void updateResult(result, { is_verified: !result.is_verified })
}

function hideResult(result) {
  void updateResult(result, {
    status: 'hidden',
    is_verified: false,
  })
}

function statusClass(status) {
  if (status === 'published') {
    return 'px-2 py-1 rounded-lg bg-emerald-500/15 text-emerald-300 text-xs capitalize'
  }

  if (status === 'pending') {
    return 'px-2 py-1 rounded-lg bg-amber-500/15 text-amber-300 text-xs capitalize'
  }

  return 'px-2 py-1 rounded-lg bg-slate-500/20 text-slate-300 text-xs capitalize'
}

onMounted(fetchLabResults)
</script>
