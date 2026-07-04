<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">
          Community Content Library
        </h1>
        <p class="text-slate-400 mt-1">
          Manage research articles, guides, and FAQ entries.
        </p>
      </div>
      <button
        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors"
        @click="fetchContent"
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
        @submit.prevent="saveContent"
      >
        <div>
          <h2 class="text-lg font-semibold text-white">
            {{ editingItem ? 'Edit Content' : 'New Content' }}
          </h2>
          <p class="text-sm text-slate-400">
            Published items appear on the public site immediately.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Type</span>
            <select
              v-model="form.type"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            >
              <option value="research">
                Research
              </option>
              <option value="guide">
                Guide
              </option>
              <option value="faq">
                FAQ
              </option>
            </select>
          </label>
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Status</span>
            <select
              v-model="form.status"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
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

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Title</span>
          <input
            v-model="form.title"
            required
            maxlength="220"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            placeholder="Content title"
          >
        </label>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Category</span>
            <input
              v-model="form.category"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
              placeholder="Peptides"
            >
          </label>
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Tag</span>
            <input
              v-model="form.tag"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
              placeholder="storage"
            >
          </label>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Read Minutes</span>
            <input
              v-model.number="form.read_minutes"
              type="number"
              min="1"
              max="240"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            >
          </label>
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Image Index</span>
            <input
              v-model.number="form.image_index"
              type="number"
              min="0"
              max="99"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            >
          </label>
        </div>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Excerpt</span>
          <textarea
            v-model="form.excerpt"
            rows="3"
            maxlength="500"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
          />
        </label>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Body</span>
          <textarea
            v-model="form.body"
            rows="10"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
          />
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
            class="px-5 py-3 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-medium disabled:opacity-60"
          >
            {{ saving ? 'Saving...' : editingItem ? 'Save Changes' : 'Create Content' }}
          </button>
          <button
            v-if="editingItem"
            type="button"
            class="px-5 py-3 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600"
            @click="resetForm"
          >
            Cancel
          </button>
        </div>
      </form>

      <div class="space-y-4">
        <div class="rounded-2xl bg-slate-800/50 border border-slate-700/50 p-5">
          <div class="grid grid-cols-1 md:grid-cols-[1fr_160px_160px_auto] gap-3">
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search content..."
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
              @keydown.enter="fetchContent"
            >
            <select
              v-model="filters.type"
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            >
              <option value="all">
                All types
              </option>
              <option value="research">
                Research
              </option>
              <option value="guide">
                Guides
              </option>
              <option value="faq">
                FAQ
              </option>
            </select>
            <select
              v-model="filters.status"
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
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
              class="px-5 py-3 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-medium"
              @click="fetchContent"
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
            Loading content...
          </div>
          <div
            v-else-if="items.length === 0"
            class="p-10 text-center text-slate-400"
          >
            No content found.
          </div>
          <div
            v-else
            class="divide-y divide-slate-700/60"
          >
            <article
              v-for="item in items"
              :key="item.id"
              class="p-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between hover:bg-slate-700/25"
            >
              <div class="max-w-3xl">
                <div class="flex flex-wrap gap-2">
                  <span class="px-2 py-1 rounded-lg bg-violet-500/15 text-violet-300 text-xs capitalize">{{ item.type }}</span>
                  <span :class="publishClass(item.status)">{{ item.status }}</span>
                  <span class="px-2 py-1 rounded-lg bg-slate-500/20 text-slate-300 text-xs">{{ item.category }}</span>
                </div>
                <h2 class="mt-3 text-lg font-semibold text-white">
                  {{ item.title }}
                </h2>
                <p class="mt-2 text-sm text-slate-400 line-clamp-2">
                  {{ item.excerpt || item.body }}
                </p>
                <p class="mt-3 text-xs text-slate-500">
                  {{ item.published_label || 'Not published' }} · {{ item.views }} views
                </p>
              </div>
              <div class="flex flex-wrap gap-2 lg:justify-end">
                <button
                  class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-500/15 text-slate-300 hover:bg-slate-500/25"
                  @click="editItem(item)"
                >
                  Edit
                </button>
                <button
                  class="px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25"
                  @click="updateItem(item, { status: 'published' })"
                >
                  Publish
                </button>
                <button
                  class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/15 text-red-300 hover:bg-red-500/25"
                  @click="updateItem(item, { status: 'hidden' })"
                >
                  Hide
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

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const editingItem = ref(null)
const stats = ref({
  total: 0,
  research: 0,
  guides: 0,
  faqs: 0,
  published: 0,
})

const filters = reactive({
  search: '',
  type: 'all',
  status: 'all',
})

const emptyForm = () => ({
  type: 'research',
  title: '',
  category: 'Peptides',
  tag: '',
  excerpt: '',
  body: '',
  read_minutes: 8,
  image_index: 0,
  status: 'draft',
})

const form = reactive(emptyForm())

const statCards = computed(() => [
  { label: 'Total', value: stats.value.total },
  { label: 'Research', value: stats.value.research },
  { label: 'Guides', value: stats.value.guides },
  { label: 'FAQ', value: stats.value.faqs },
  { label: 'Published', value: stats.value.published },
])

async function fetchContent() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/community/content', {
      params: {
        search: filters.search || undefined,
        type: filters.type,
        status: filters.status,
      },
    })
    items.value = response.data.data || []
    stats.value = response.data.meta?.stats || stats.value
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load content.'
  } finally {
    loading.value = false
  }
}

async function saveContent() {
  saving.value = true
  error.value = ''

  const payload = {
    type: form.type,
    title: form.title,
    category: form.category || 'General',
    tag: form.tag || null,
    excerpt: form.excerpt || null,
    body: form.body || null,
    read_minutes: form.read_minutes,
    image_index: form.image_index,
    status: form.status,
  }

  try {
    if (editingItem.value) {
      await api.patch(`/admin/community/content/${editingItem.value.id}`, payload)
    } else {
      await api.post('/admin/community/content', payload)
    }

    resetForm()
    await fetchContent()
  } catch (err) {
    const errors = err.response?.data?.errors
    error.value = errors ? Object.values(errors)[0]?.[0] || 'Unable to save content.' : err.response?.data?.message || 'Unable to save content.'
  } finally {
    saving.value = false
  }
}

function editItem(item) {
  editingItem.value = item
  Object.assign(form, {
    type: item.type,
    title: item.title,
    category: item.category,
    tag: item.tag || '',
    excerpt: item.excerpt || '',
    body: item.body || '',
    read_minutes: item.read_minutes || 8,
    image_index: item.image_index || 0,
    status: item.status,
  })
}

function resetForm() {
  editingItem.value = null
  Object.assign(form, emptyForm())
}

async function updateItem(item, payload) {
  error.value = ''

  try {
    await api.patch(`/admin/community/content/${item.id}`, payload)
    await fetchContent()
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to update content.'
  }
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

onMounted(fetchContent)
</script>
