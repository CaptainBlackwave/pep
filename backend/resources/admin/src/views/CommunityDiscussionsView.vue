<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">
          Community Discussions
        </h1>
        <p class="text-slate-400 mt-1">
          Moderate Peptide Vendors discussion content.
        </p>
      </div>
      <button
        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors"
        @click="fetchDiscussions"
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
          placeholder="Search title, body, or author..."
          class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50"
          @keydown.enter="fetchDiscussions"
        >
        <select
          v-model="filters.status"
          class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-amber-500/50"
        >
          <option value="all">
            All statuses
          </option>
          <option value="published">
            Published
          </option>
          <option value="hidden">
            Hidden
          </option>
        </select>
        <button
          class="px-5 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium hover:from-amber-600 hover:to-orange-700 transition-all"
          @click="fetchDiscussions"
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
        Loading discussions...
      </div>

      <div
        v-else-if="discussions.length === 0"
        class="p-10 text-center text-slate-400"
      >
        No discussions found.
      </div>

      <div
        v-else
        class="overflow-x-auto"
      >
        <table class="w-full">
          <thead class="bg-slate-900/80 border-b border-slate-700">
            <tr>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                Discussion
              </th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                Category
              </th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                Status
              </th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                Activity
              </th>
              <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/60">
            <tr
              v-for="discussion in discussions"
              :key="discussion.id"
              class="hover:bg-slate-700/25"
            >
              <td class="px-5 py-4">
                <div class="max-w-xl">
                  <p class="font-semibold text-white">
                    {{ discussion.title }}
                  </p>
                  <p class="text-sm text-slate-400 line-clamp-2">
                    {{ discussion.excerpt || discussion.body }}
                  </p>
                  <p class="text-xs text-slate-500 mt-2">
                    {{ [discussion.author?.name, discussion.time_ago].filter(Boolean).join(' · ') }}
                  </p>
                </div>
              </td>
              <td class="px-5 py-4 text-sm text-slate-300">
                {{ discussion.category?.name ?? '' }}
              </td>
              <td class="px-5 py-4">
                <div class="flex flex-wrap gap-2">
                  <span :class="statusClass(discussion.status)">
                    {{ discussion.status }}
                  </span>
                  <span
                    v-if="discussion.is_pinned"
                    class="px-2 py-1 rounded-lg bg-amber-500/15 text-amber-300 text-xs"
                  >Pinned</span>
                  <span
                    v-if="discussion.is_locked"
                    class="px-2 py-1 rounded-lg bg-red-500/15 text-red-300 text-xs"
                  >Locked</span>
                </div>
              </td>
              <td class="px-5 py-4 text-sm text-slate-300">
                <div>{{ discussion.replies }} replies</div>
                <div class="text-slate-500">
                  {{ discussion.views }} views
                </div>
              </td>
              <td class="px-5 py-4">
                <div class="flex flex-wrap justify-end gap-2">
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-500/15 text-amber-300 hover:bg-amber-500/25"
                    @click="togglePinned(discussion)"
                  >
                    {{ discussion.is_pinned ? 'Unpin' : 'Pin' }}
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-sky-500/15 text-sky-300 hover:bg-sky-500/25"
                    @click="toggleLocked(discussion)"
                  >
                    {{ discussion.is_locked ? 'Unlock' : 'Lock' }}
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/15 text-red-300 hover:bg-red-500/25"
                    @click="toggleStatus(discussion)"
                  >
                    {{ discussion.status === 'published' ? 'Hide' : 'Publish' }}
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
const discussions = ref([])
const stats = ref({
  total: 0,
  published: 0,
  hidden: 0,
  locked: 0,
  pinned: 0,
})

const filters = reactive({
  search: '',
  status: 'all',
})

const statCards = computed(() => [
  { label: 'Total', value: stats.value.total },
  { label: 'Published', value: stats.value.published },
  { label: 'Hidden', value: stats.value.hidden },
  { label: 'Locked', value: stats.value.locked },
  { label: 'Pinned', value: stats.value.pinned },
])

async function fetchDiscussions() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/community/discussions', {
      params: {
        search: filters.search || undefined,
        status: filters.status,
      },
    })
    discussions.value = response.data.data || []
    stats.value = response.data.meta?.stats || stats.value
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load community discussions.'
  } finally {
    loading.value = false
  }
}

async function updateDiscussion(discussion, payload) {
  const response = await api.patch(`/admin/community/discussions/${discussion.slug}`, payload)
  const updated = response.data.data
  const index = discussions.value.findIndex(item => item.id === updated.id)

  if (index >= 0) {
    discussions.value[index] = updated
  }

  await fetchDiscussions()
}

function togglePinned(discussion) {
  void updateDiscussion(discussion, { is_pinned: !discussion.is_pinned })
}

function toggleLocked(discussion) {
  void updateDiscussion(discussion, { is_locked: !discussion.is_locked })
}

function toggleStatus(discussion) {
  void updateDiscussion(discussion, {
    status: discussion.status === 'published' ? 'hidden' : 'published',
  })
}

function statusClass(status) {
  return status === 'published'
    ? 'px-2 py-1 rounded-lg bg-emerald-500/15 text-emerald-300 text-xs capitalize'
    : 'px-2 py-1 rounded-lg bg-slate-500/20 text-slate-300 text-xs capitalize'
}

onMounted(fetchDiscussions)
</script>
