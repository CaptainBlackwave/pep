<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">
          Community Announcements
        </h1>
        <p class="text-slate-400 mt-1">
          Create, publish, pin, and hide Peptide Vendors announcements.
        </p>
      </div>
      <button
        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors"
        @click="fetchAnnouncements"
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

    <div class="grid grid-cols-1 xl:grid-cols-[420px_1fr] gap-6">
      <form
        class="rounded-2xl bg-slate-800/50 border border-slate-700/50 p-5 space-y-4"
        @submit.prevent="saveAnnouncement"
      >
        <div>
          <h2 class="text-lg font-semibold text-white">
            {{ editingAnnouncement ? 'Edit Announcement' : 'New Announcement' }}
          </h2>
          <p class="text-sm text-slate-400">
            Published announcements appear on the public site immediately.
          </p>
        </div>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Title</span>
          <input
            v-model="form.title"
            required
            maxlength="180"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="Announcement title"
          >
        </label>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Category</span>
            <select
              v-model="form.category"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            >
              <option value="General">
                General
              </option>
              <option value="Platform Update">
                Platform Update
              </option>
              <option value="Safety">
                Safety
              </option>
              <option value="Community">
                Community
              </option>
              <option value="Event">
                Event
              </option>
            </select>
          </label>
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Status</span>
            <select
              v-model="form.status"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            >
              <option value="draft">
                Draft
              </option>
              <option value="published">
                Published
              </option>
              <option value="hidden">
                Hidden
              </option>
            </select>
          </label>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Icon</span>
            <select
              v-model="form.icon"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            >
              <option value="megaphone">
                Megaphone
              </option>
              <option value="shield">
                Shield
              </option>
              <option value="settings">
                Settings
              </option>
              <option value="calendar">
                Calendar
              </option>
              <option value="users">
                Users
              </option>
            </select>
          </label>
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Tone</span>
            <select
              v-model="form.tone"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            >
              <option value="purple">
                Purple
              </option>
              <option value="green">
                Green
              </option>
              <option value="amber">
                Amber
              </option>
              <option value="blue">
                Blue
              </option>
              <option value="red">
                Red
              </option>
            </select>
          </label>
        </div>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Excerpt</span>
          <textarea
            v-model="form.excerpt"
            maxlength="320"
            rows="3"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="Short public summary"
          />
        </label>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Body</span>
          <textarea
            v-model="form.body"
            required
            rows="8"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="Full announcement body"
          />
        </label>

        <label class="flex items-center gap-3 rounded-xl border border-slate-700 bg-slate-900/40 px-4 py-3 text-slate-300">
          <input
            v-model="form.is_pinned"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-violet-500 focus:ring-violet-500"
          >
          Pin announcement
        </label>

        <div
          v-if="error"
          class="rounded-xl bg-red-500/10 border border-red-500/30 p-4 text-sm text-red-300"
        >
          {{ error }}
        </div>

        <div class="flex flex-wrap gap-3">
          <button
            type="submit"
            :disabled="saving"
            class="px-5 py-3 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-medium hover:from-violet-700 hover:to-indigo-700 transition-all disabled:opacity-60"
          >
            {{ saving ? 'Saving...' : editingAnnouncement ? 'Save Changes' : 'Create Announcement' }}
          </button>
          <button
            v-if="editingAnnouncement"
            type="button"
            class="px-5 py-3 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors"
            @click="resetForm"
          >
            Cancel
          </button>
        </div>
      </form>

      <div class="space-y-4">
        <div class="rounded-2xl bg-slate-800/50 border border-slate-700/50 p-5">
          <div class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-3">
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search announcements..."
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
              @keydown.enter="fetchAnnouncements"
            >
            <select
              v-model="filters.status"
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            >
              <option value="all">
                All statuses
              </option>
              <option value="draft">
                Draft
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
              @click="fetchAnnouncements"
            >
              Apply
            </button>
          </div>
        </div>

        <div class="rounded-2xl bg-slate-800/50 border border-slate-700/50 overflow-hidden">
          <div
            v-if="loading"
            class="p-10 text-center text-slate-400"
          >
            Loading announcements...
          </div>
          <div
            v-else-if="announcements.length === 0"
            class="p-10 text-center text-slate-400"
          >
            No announcements found.
          </div>
          <div
            v-else
            class="divide-y divide-slate-700/60"
          >
            <article
              v-for="announcement in announcements"
              :key="announcement.id"
              class="p-5 hover:bg-slate-700/25"
            >
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                  <div class="flex flex-wrap items-center gap-2">
                    <span :class="toneClass(announcement.tone)">
                      {{ announcement.category }}
                    </span>
                    <span :class="publishClass(announcement.status)">
                      {{ announcement.status }}
                    </span>
                    <span
                      v-if="announcement.is_pinned"
                      class="px-2 py-1 rounded-lg bg-violet-500/15 text-violet-300 text-xs"
                    >
                      pinned
                    </span>
                  </div>
                  <h2 class="mt-3 text-lg font-semibold text-white">
                    {{ announcement.title }}
                  </h2>
                  <p class="mt-2 text-sm text-slate-400 line-clamp-2">
                    {{ announcement.excerpt || announcement.body }}
                  </p>
                  <p class="mt-3 text-xs text-slate-500">
                    {{ announcement.published_label || 'Not published' }} · {{ announcement.views }} views · {{ announcement.comments }} comments
                  </p>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-500/15 text-slate-300 hover:bg-slate-500/25"
                    @click="editAnnouncement(announcement)"
                  >
                    Edit
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-violet-500/15 text-violet-300 hover:bg-violet-500/25"
                    @click="updateAnnouncement(announcement, { is_pinned: !announcement.is_pinned })"
                  >
                    {{ announcement.is_pinned ? 'Unpin' : 'Pin' }}
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25"
                    @click="updateAnnouncement(announcement, { status: 'published' })"
                  >
                    Publish
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/15 text-red-300 hover:bg-red-500/25"
                    @click="updateAnnouncement(announcement, { status: 'hidden' })"
                  >
                    Hide
                  </button>
                </div>
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

const announcements = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const editingAnnouncement = ref(null)
const stats = ref({
  total: 0,
  published: 0,
  draft: 0,
  hidden: 0,
  pinned: 0,
})

const filters = reactive({
  search: '',
  status: 'all',
})

const emptyForm = () => ({
  title: '',
  category: 'General',
  icon: 'megaphone',
  tone: 'purple',
  excerpt: '',
  body: '',
  status: 'draft',
  is_pinned: false,
})

const form = reactive(emptyForm())

const statCards = computed(() => [
  { label: 'Total', value: stats.value.total },
  { label: 'Published', value: stats.value.published },
  { label: 'Draft', value: stats.value.draft },
  { label: 'Hidden', value: stats.value.hidden },
  { label: 'Pinned', value: stats.value.pinned },
])

async function fetchAnnouncements() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/community/announcements', {
      params: {
        search: filters.search || undefined,
        status: filters.status,
      },
    })
    announcements.value = response.data.data || []
    stats.value = response.data.meta?.stats || stats.value
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load announcements.'
  } finally {
    loading.value = false
  }
}

async function saveAnnouncement() {
  saving.value = true
  error.value = ''

  const payload = {
    title: form.title,
    category: form.category,
    icon: form.icon,
    tone: form.tone,
    excerpt: form.excerpt || null,
    body: form.body,
    status: form.status,
    is_pinned: form.is_pinned,
  }

  try {
    if (editingAnnouncement.value) {
      await api.patch(`/admin/community/announcements/${editingAnnouncement.value.slug}`, payload)
    } else {
      await api.post('/admin/community/announcements', payload)
    }

    resetForm()
    await fetchAnnouncements()
  } catch (err) {
    const errors = err.response?.data?.errors
    error.value = errors ? Object.values(errors)[0]?.[0] || 'Unable to save announcement.' : err.response?.data?.message || 'Unable to save announcement.'
  } finally {
    saving.value = false
  }
}

function editAnnouncement(announcement) {
  editingAnnouncement.value = announcement
  Object.assign(form, {
    title: announcement.title,
    category: announcement.category,
    icon: announcement.icon,
    tone: announcement.tone,
    excerpt: announcement.excerpt || '',
    body: announcement.body || '',
    status: announcement.status,
    is_pinned: announcement.is_pinned,
  })
}

function resetForm() {
  editingAnnouncement.value = null
  Object.assign(form, emptyForm())
}

async function updateAnnouncement(announcement, payload) {
  error.value = ''

  try {
    await api.patch(`/admin/community/announcements/${announcement.slug}`, payload)
    await fetchAnnouncements()
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to update announcement.'
  }
}

function toneClass(tone) {
  const tones = {
    green: 'bg-emerald-500/15 text-emerald-300',
    amber: 'bg-amber-500/15 text-amber-300',
    blue: 'bg-sky-500/15 text-sky-300',
    red: 'bg-red-500/15 text-red-300',
  }

  return `px-2 py-1 rounded-lg text-xs ${tones[tone] || 'bg-violet-500/15 text-violet-300'}`
}

function publishClass(status) {
  if (status === 'published') {
    return 'px-2 py-1 rounded-lg bg-sky-500/15 text-sky-300 text-xs capitalize'
  }

  if (status === 'hidden') {
    return 'px-2 py-1 rounded-lg bg-red-500/15 text-red-300 text-xs capitalize'
  }

  return 'px-2 py-1 rounded-lg bg-slate-500/20 text-slate-300 text-xs capitalize'
}

onMounted(fetchAnnouncements)
</script>
