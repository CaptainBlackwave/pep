<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">
          Vendors
        </h1>
        <p class="text-slate-400 mt-1">
          Manage vendor profiles. Admins can create and edit vendors directly.
        </p>
      </div>
      <button
        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors"
        @click="fetchVendors"
      >
        Refresh
      </button>
    </div>

    <div class="grid grid-cols-3 gap-4">
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
        @submit.prevent="saveVendor"
      >
        <div>
          <h2 class="text-lg font-semibold text-white">
            {{ editingItem ? 'Edit Vendor' : 'New Vendor' }}
          </h2>
          <p class="text-sm text-slate-400">
            Create or update a vendor profile. Published vendors appear on the public site.
          </p>
        </div>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Vendor Name</span>
          <input
            v-model="form.name"
            required
            maxlength="160"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            placeholder="Vendor name"
          >
        </label>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Slug</span>
            <input
              v-model="form.slug"
              maxlength="180"
              pattern="[a-zA-Z0-9-]+"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
              placeholder="vendor-name"
            >
          </label>
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Status</span>
            <select
              v-model="form.status"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            >
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
          <span class="text-sm font-medium text-slate-300">Owner (User ID — leave blank for self)</span>
          <input
            v-model="form.owner_user_id"
            type="number"
            min="1"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            placeholder="User ID"
          >
        </label>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Description</span>
          <textarea
            v-model="form.description"
            rows="4"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            placeholder="Vendor description"
          />
        </label>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Website URL</span>
            <input
              v-model="form.website_url"
              type="url"
              maxlength="255"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
              placeholder="https://example.com"
            >
          </label>
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Image URL</span>
            <input
              v-model="form.image_url"
              maxlength="2048"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
              placeholder="https://example.com/logo.png"
            >
          </label>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Contact Email</span>
            <input
              v-model="form.contact_email"
              type="email"
              maxlength="255"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
              placeholder="support@example.com"
            >
          </label>
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Telegram</span>
            <input
              v-model="form.contact_telegram"
              maxlength="120"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
              placeholder="@handle"
            >
          </label>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Signal</span>
            <input
              v-model="form.contact_signal"
              maxlength="120"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
              placeholder="Signal username"
            >
          </label>
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Discord</span>
            <input
              v-model="form.contact_discord"
              maxlength="120"
              class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
              placeholder="Discord handle"
            >
          </label>
        </div>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Support URL</span>
          <input
            v-model="form.support_url"
            type="url"
            maxlength="255"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            placeholder="https://example.com/support"
          >
        </label>

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Tags (comma-separated)</span>
          <input
            v-model="tagsInput"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            placeholder="Domestic, Lab Tested, Fast Shipping"
          >
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
            {{ saving ? 'Saving...' : editingItem ? 'Save Changes' : 'Create Vendor' }}
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
          <div class="grid grid-cols-1 md:grid-cols-[1fr_160px_auto] gap-3">
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search vendors..."
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
              @keydown.enter="fetchVendors"
            >
            <select
              v-model="filters.status"
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
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
              class="px-5 py-3 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-medium"
              @click="fetchVendors"
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
            Loading vendors...
          </div>
          <div
            v-else-if="items.length === 0"
            class="p-10 text-center text-slate-400"
          >
            No vendors found.
          </div>
          <div
            v-else
            class="divide-y divide-slate-700/60"
          >
            <article
              v-for="vendor in items"
              :key="vendor.id"
              class="p-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between hover:bg-slate-700/25"
            >
              <div class="max-w-3xl">
                <div class="flex flex-wrap gap-2">
                  <span :class="publishClass(vendor.status)">{{ vendor.status }}</span>
                  <span class="px-2 py-1 rounded-lg bg-slate-500/20 text-slate-300 text-xs">{{ vendor.claim_status }}</span>
                </div>
                <h2 class="mt-3 text-lg font-semibold text-white">
                  {{ vendor.name }}
                </h2>
                <p class="mt-2 text-sm text-slate-400 line-clamp-2">
                  {{ vendor.description }}
                </p>
                <p class="mt-3 text-xs text-slate-500">
                  {{ vendor.member_since_label || 'N/A' }} · {{ vendor.review_count }} reviews · {{ vendor.average_rating }} rating · Owner ID: {{ vendor.owner_user_id || 'none' }}
                </p>
              </div>
              <div class="flex flex-wrap gap-2 lg:justify-end">
                <button
                  class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-500/15 text-slate-300 hover:bg-slate-500/25"
                  @click="editItem(vendor)"
                >
                  Edit
                </button>
                <button
                  v-if="vendor.status !== 'published'"
                  class="px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25"
                  @click="updateItem(vendor, { status: 'published' })"
                >
                  Publish
                </button>
                <button
                  v-if="vendor.status !== 'hidden'"
                  class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/15 text-red-300 hover:bg-red-500/25"
                  @click="updateItem(vendor, { status: 'hidden' })"
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
  published: 0,
  hidden: 0,
})

const filters = reactive({
  search: '',
  status: 'all',
})

const emptyForm = () => ({
  name: '',
  slug: '',
  owner_user_id: '',
  description: '',
  website_url: '',
  image_url: '',
  contact_email: '',
  contact_telegram: '',
  contact_signal: '',
  contact_discord: '',
  support_url: '',
  status: 'published',
})

const form = reactive(emptyForm())

const tagsInput = ref('')

const statCards = computed(() => [
  { label: 'Total', value: stats.value.total },
  { label: 'Published', value: stats.value.published },
  { label: 'Hidden', value: stats.value.hidden },
])

async function fetchVendors() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/community/vendors', {
      params: {
        search: filters.search || undefined,
        status: filters.status,
      },
    })
    items.value = response.data.data || []
    stats.value = response.data.meta?.stats || stats.value
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load vendors.'
  } finally {
    loading.value = false
  }
}

async function saveVendor() {
  saving.value = true
  error.value = ''

  const payload = {
    name: form.name,
    slug: form.slug || undefined,
    owner_user_id: form.owner_user_id ? Number(form.owner_user_id) : undefined,
    description: form.description || null,
    website_url: form.website_url || null,
    image_url: form.image_url || null,
    contact_email: form.contact_email || null,
    contact_telegram: form.contact_telegram || null,
    contact_signal: form.contact_signal || null,
    contact_discord: form.contact_discord || null,
    support_url: form.support_url || null,
    tags: tagsInput.value ? tagsInput.value.split(',').map(t => t.trim()).filter(Boolean) : null,
    status: form.status,
  }

  try {
    if (editingItem.value) {
      await api.patch(`/admin/community/vendors/${editingItem.value.id}`, payload)
    } else {
      await api.post('/admin/community/vendors', payload)
    }

    resetForm()
    await fetchVendors()
  } catch (err) {
    const errors = err.response?.data?.errors
    error.value = errors ? Object.values(errors)[0]?.[0] || 'Unable to save vendor.' : err.response?.data?.message || 'Unable to save vendor.'
  } finally {
    saving.value = false
  }
}

function editItem(vendor) {
  editingItem.value = vendor
  Object.assign(form, {
    name: vendor.name,
    slug: vendor.slug || '',
    owner_user_id: vendor.owner_user_id ? String(vendor.owner_user_id) : '',
    description: vendor.description || '',
    website_url: vendor.website_url || '',
    image_url: vendor.image_url || '',
    contact_email: vendor.contact?.email || '',
    contact_telegram: vendor.contact?.telegram || '',
    contact_signal: vendor.contact?.signal || '',
    contact_discord: vendor.contact?.discord || '',
    support_url: vendor.contact?.support_url || '',
    status: vendor.status,
  })
  tagsInput.value = Array.isArray(vendor.tags) ? vendor.tags.join(', ') : ''
}

function resetForm() {
  editingItem.value = null
  Object.assign(form, emptyForm())
  tagsInput.value = ''
}

async function updateItem(vendor, payload) {
  error.value = ''

  try {
    await api.patch(`/admin/community/vendors/${vendor.id}`, payload)
    await fetchVendors()
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to update vendor.'
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

onMounted(fetchVendors)
</script>
