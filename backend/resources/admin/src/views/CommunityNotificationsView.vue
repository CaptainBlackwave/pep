<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">
          Community Notifications
        </h1>
        <p class="text-slate-400 mt-1">
          Publish, pin, and hide public Peptide Vendors notifications.
        </p>
      </div>
      <button
        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors"
        @click="fetchNotifications"
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
        @submit.prevent="saveNotification"
      >
        <div>
          <h2 class="text-lg font-semibold text-white">
            {{ editingNotification ? 'Edit Notification' : 'New Notification' }}
          </h2>
          <p class="text-sm text-slate-400">
            Published notifications appear on the public notifications page.
          </p>
        </div>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Title</span>
          <input
            v-model="form.title"
            required
            maxlength="180"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="Notification title"
          >
        </label>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Category</span>
            <select
              v-model="form.category"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            >
              <option value="Announcements">
                Announcements
              </option>
              <option value="Lab Results">
                Lab Results
              </option>
              <option value="Discussions">
                Discussions
              </option>
              <option value="Vendor Reviews">
                Vendor Reviews
              </option>
              <option value="Research Library">
                Research Library
              </option>
              <option value="Guides & FAQ">
                Guides &amp; FAQ
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
              <option value="bell">
                Bell
              </option>
              <option value="megaphone">
                Megaphone
              </option>
              <option value="flask">
                Flask
              </option>
              <option value="message">
                Message
              </option>
              <option value="star">
                Star
              </option>
              <option value="document">
                Document
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
          <span class="text-sm font-medium text-slate-300">Summary</span>
          <textarea
            v-model="form.excerpt"
            maxlength="420"
            rows="3"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="Short message shown in notification lists"
          />
        </label>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Full Message</span>
          <textarea
            v-model="form.body"
            rows="6"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="Optional full notification body"
          />
        </label>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Source URL</span>
          <input
            v-model="form.source_url"
            maxlength="255"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="/announcements/example"
          >
        </label>

        <label class="flex items-center gap-3 rounded-xl border border-slate-700 bg-slate-900/40 px-4 py-3 text-slate-300">
          <input
            v-model="form.is_pinned"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-violet-500 focus:ring-violet-500"
          >
          Pin notification
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
            {{ saving ? 'Saving...' : editingNotification ? 'Save Changes' : 'Create Notification' }}
          </button>
          <button
            v-if="editingNotification"
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
              placeholder="Search notifications..."
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
              @keydown.enter="fetchNotifications"
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
              @click="fetchNotifications"
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
            Loading notifications...
          </div>
          <div
            v-else-if="notifications.length === 0"
            class="p-10 text-center text-slate-400"
          >
            No notifications found.
          </div>
          <div
            v-else
            class="divide-y divide-slate-700/60"
          >
            <article
              v-for="notification in notifications"
              :key="notification.id"
              class="p-5 hover:bg-slate-700/25"
            >
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                  <div class="flex flex-wrap items-center gap-2">
                    <span :class="toneClass(notification.tone)">
                      {{ notification.category }}
                    </span>
                    <span :class="publishClass(notification.status)">
                      {{ notification.status }}
                    </span>
                    <span
                      v-if="notification.is_pinned"
                      class="px-2 py-1 rounded-lg bg-violet-500/15 text-violet-300 text-xs"
                    >
                      pinned
                    </span>
                  </div>
                  <h2 class="mt-3 text-lg font-semibold text-white">
                    {{ notification.title }}
                  </h2>
                  <p class="mt-2 text-sm text-slate-400 line-clamp-2">
                    {{ notification.excerpt || notification.body }}
                  </p>
                  <p class="mt-3 text-xs text-slate-500">
                    {{ notification.published_label || 'Not published' }} · {{ notification.views }} views
                  </p>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-500/15 text-slate-300 hover:bg-slate-500/25"
                    @click="editNotification(notification)"
                  >
                    Edit
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-violet-500/15 text-violet-300 hover:bg-violet-500/25"
                    @click="updateNotification(notification, { is_pinned: !notification.is_pinned })"
                  >
                    {{ notification.is_pinned ? 'Unpin' : 'Pin' }}
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25"
                    @click="updateNotification(notification, { status: 'published' })"
                  >
                    Publish
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/15 text-red-300 hover:bg-red-500/25"
                    @click="updateNotification(notification, { status: 'hidden' })"
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

const notifications = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const editingNotification = ref(null)
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
  category: 'Announcements',
  icon: 'bell',
  tone: 'purple',
  excerpt: '',
  body: '',
  source_url: '',
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

async function fetchNotifications() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/community/notifications', {
      params: {
        search: filters.search || undefined,
        status: filters.status,
      },
    })
    notifications.value = response.data.data || []
    stats.value = response.data.meta?.stats || stats.value
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load notifications.'
  } finally {
    loading.value = false
  }
}

async function saveNotification() {
  saving.value = true
  error.value = ''

  const payload = {
    title: form.title,
    category: form.category,
    icon: form.icon,
    tone: form.tone,
    excerpt: form.excerpt || null,
    body: form.body || null,
    source_url: form.source_url || null,
    status: form.status,
    is_pinned: form.is_pinned,
  }

  try {
    if (editingNotification.value) {
      await api.patch(`/admin/community/notifications/${editingNotification.value.slug}`, payload)
    } else {
      await api.post('/admin/community/notifications', payload)
    }

    resetForm()
    await fetchNotifications()
  } catch (err) {
    const errors = err.response?.data?.errors
    error.value = errors ? Object.values(errors)[0]?.[0] || 'Unable to save notification.' : err.response?.data?.message || 'Unable to save notification.'
  } finally {
    saving.value = false
  }
}

function editNotification(notification) {
  editingNotification.value = notification
  Object.assign(form, {
    title: notification.title,
    category: notification.category,
    icon: notification.icon,
    tone: notification.tone,
    excerpt: notification.excerpt || '',
    body: notification.body || '',
    source_url: notification.source_url || '',
    status: notification.status,
    is_pinned: notification.is_pinned,
  })
}

function resetForm() {
  editingNotification.value = null
  Object.assign(form, emptyForm())
}

async function updateNotification(notification, payload) {
  error.value = ''

  try {
    await api.patch(`/admin/community/notifications/${notification.slug}`, payload)
    await fetchNotifications()
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to update notification.'
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

onMounted(fetchNotifications)
</script>
