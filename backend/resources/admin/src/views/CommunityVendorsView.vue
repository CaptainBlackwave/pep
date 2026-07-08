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
          <label class="block">
            <span class="text-sm font-medium text-slate-300">Tier</span>
            <select v-model="form.tier" class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white">
              <option value="free">Free</option>
              <option value="premium">Premium</option>
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

        <label class="block">
          <span class="text-sm font-medium text-slate-300">Country</span>
          <input
            v-model="form.country"
            maxlength="100"
            class="mt-1 w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
            placeholder="e.g. China, USA, UK"
          >
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

        <div v-if="editingItem" class="border-t border-slate-700/50 pt-4 mt-2">
          <div class="flex items-center justify-between mb-3">
            <div>
              <h3 class="text-base font-semibold text-white">Products</h3>
              <p v-if="products.length" class="text-xs text-slate-400">{{ products.length }} product{{ products.length !== 1 ? 's' : '' }}</p>
            </div>
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg text-xs font-medium bg-sky-500/15 text-sky-300 hover:bg-sky-500/25"
              @click="openProductForm()"
            >
              + Add
            </button>
          </div>

          <div v-if="productsLoading" class="py-3 text-xs text-slate-400 text-center">Loading products...</div>

          <div v-else-if="products.length === 0" class="py-3 text-xs text-slate-400 text-center">No products yet.</div>

          <div v-else class="divide-y divide-slate-700/40 text-sm">
            <div
              v-for="product in products"
              :key="product.id"
              class="py-2.5 flex items-start justify-between gap-2"
            >
              <div class="min-w-0">
                <p class="text-white truncate">{{ product.name }}</p>
                <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-slate-400 mt-0.5">
                  <span v-if="product.category">{{ product.category }}</span>
                  <span v-if="product.variants?.length" class="text-sky-400">{{ product.variants.length }} variants</span>
                  <span v-else-if="product.price_label">{{ product.price_label }}</span>
                  <span>{{ product.availability_label }}</span>
                  <span :class="product.status === 'published' ? 'text-emerald-400' : 'text-red-400'">{{ product.status }}</span>
                </div>
              </div>
              <div class="flex gap-1 shrink-0">
                <button
                  type="button"
                  class="px-2 py-1 rounded text-xs font-medium bg-slate-500/15 text-slate-300 hover:bg-slate-500/25"
                  @click="openProductForm(product)"
                >
                  Edit
                </button>
                <button
                  type="button"
                  class="px-2 py-1 rounded text-xs font-medium bg-red-500/15 text-red-300 hover:bg-red-500/25"
                  @click="deleteProduct(product)"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>

          <div v-if="showProductForm" class="mt-3 pt-3 border-t border-slate-700/40 space-y-3">
            <h4 class="text-sm font-medium text-white">{{ productEditing ? 'Edit Product' : 'New Product' }}</h4>

            <label class="block">
              <span class="text-xs text-slate-300">Name</span>
              <input
                v-model="productForm.name"
                required
                maxlength="255"
                class="mt-1 w-full px-3 py-2 bg-slate-900/60 border border-slate-700 rounded-lg text-white text-sm"
                placeholder="Product name"
              >
            </label>

            <div class="grid grid-cols-2 gap-3">
              <label class="block">
                <span class="text-xs text-slate-300">Category</span>
                <input
                  v-model="productForm.category"
                  maxlength="120"
                  class="mt-1 w-full px-3 py-2 bg-slate-900/60 border border-slate-700 rounded-lg text-white text-sm"
                  placeholder="e.g. Powder"
                >
              </label>
              <label class="block">
                <span class="text-xs text-slate-300">Strength</span>
                <input
                  v-model="productForm.strength"
                  maxlength="80"
                  class="mt-1 w-full px-3 py-2 bg-slate-900/60 border border-slate-700 rounded-lg text-white text-sm"
                  placeholder="e.g. 100mg"
                >
              </label>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <label class="block">
                <span class="text-xs text-slate-300">Price</span>
                <input
                  v-model.number="productForm.price"
                  type="number"
                  step="0.01"
                  min="0"
                  class="mt-1 w-full px-3 py-2 bg-slate-900/60 border border-slate-700 rounded-lg text-white text-sm"
                  placeholder="0.00"
                >
              </label>
              <label class="block">
                <span class="text-xs text-slate-300">Availability</span>
                <select
                  v-model="productForm.availability"
                  class="mt-1 w-full px-3 py-2 bg-slate-900/60 border border-slate-700 rounded-lg text-white text-sm"
                >
                  <option value="in_stock">In stock</option>
                  <option value="limited">Limited</option>
                  <option value="out_of_stock">Out of stock</option>
                </select>
              </label>
            </div>

            <div class="border-t border-slate-700/40 pt-3">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-300">Variants</span>
                <button
                  type="button"
                  class="px-2 py-1 rounded text-xs font-medium bg-sky-500/15 text-sky-300 hover:bg-sky-500/25"
                  @click="addVariant"
                >
                  + Add Variant
                </button>
              </div>
              <div v-if="productForm.variants.length === 0" class="text-xs text-slate-500">No variants. Main price is used.</div>
              <div
                v-for="(variant, vi) in productForm.variants"
                :key="vi"
                class="grid grid-cols-[1fr_80px_100px_auto] gap-2 mb-2 items-end"
              >
                <label class="block">
                  <span class="text-xs text-slate-400">Label</span>
                  <input
                    v-model="variant.label"
                    maxlength="80"
                    class="mt-0.5 w-full px-2 py-1.5 bg-slate-900/60 border border-slate-700 rounded text-white text-xs"
                    placeholder="10mg"
                  >
                </label>
                <label class="block">
                  <span class="text-xs text-slate-400">Price</span>
                  <input
                    v-model.number="variant.price"
                    type="number"
                    step="0.01"
                    min="0"
                    class="mt-0.5 w-full px-2 py-1.5 bg-slate-900/60 border border-slate-700 rounded text-white text-xs"
                    placeholder="0.00"
                  >
                </label>
                <label class="block">
                  <span class="text-xs text-slate-400">Avail.</span>
                  <select
                    v-model="variant.availability"
                    class="mt-0.5 w-full px-2 py-1.5 bg-slate-900/60 border border-slate-700 rounded text-white text-xs"
                  >
                    <option value="in_stock">In stock</option>
                    <option value="limited">Limited</option>
                    <option value="out_of_stock">OOS</option>
                  </select>
                </label>
                <button
                  type="button"
                  class="px-2 py-1.5 rounded text-xs font-medium bg-red-500/15 text-red-300 hover:bg-red-500/25"
                  @click="removeVariant(vi)"
                >
                  Remove
                </button>
              </div>
            </div>

            <label class="block">
              <span class="text-xs text-slate-300">Status</span>
              <select
                v-model="productForm.status"
                class="mt-1 w-full px-3 py-2 bg-slate-900/60 border border-slate-700 rounded-lg text-white text-sm"
              >
                <option value="published">Published</option>
                <option value="hidden">Hidden</option>
              </select>
            </label>

            <div class="grid grid-cols-2 gap-3">
              <label class="block">
                <span class="text-xs text-slate-300">Purity Label</span>
                <input v-model="productForm.purity_label" maxlength="80" class="mt-1 w-full px-3 py-2 bg-slate-900/60 border border-slate-700 rounded-lg text-white text-sm" placeholder=">98%">
              </label>
              <label class="block">
                <span class="text-xs text-slate-300">Package Size</span>
                <input v-model="productForm.package_size" maxlength="80" class="mt-1 w-full px-3 py-2 bg-slate-900/60 border border-slate-700 rounded-lg text-white text-sm" placeholder="1 vial">
              </label>
            </div>

            <label class="block">
              <span class="text-xs text-slate-300">Description</span>
              <textarea v-model="productForm.description" rows="3" maxlength="50000" class="mt-1 w-full px-3 py-2 bg-slate-900/60 border border-slate-700 rounded-lg text-white text-sm" placeholder="Product description"></textarea>
            </label>

            <div v-if="productError" class="rounded-lg bg-red-500/10 border border-red-500/30 p-3 text-xs text-red-300">
              {{ productError }}
            </div>

            <div class="flex gap-2">
              <button
                type="button"
                :disabled="productSaving"
                class="px-4 py-2 rounded-lg bg-gradient-to-r from-violet-600 to-indigo-600 text-white text-sm font-medium disabled:opacity-60"
                @click="saveProduct"
              >
                {{ productSaving ? 'Saving...' : productEditing ? 'Update' : 'Create' }}
              </button>
              <button
                type="button"
                class="px-4 py-2 rounded-lg bg-slate-700 text-slate-200 text-sm hover:bg-slate-600"
                @click="closeProductForm"
              >
                Cancel
              </button>
            </div>
          </div>
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
import { computed, onMounted, reactive, ref, watch } from 'vue'
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
  country: '',
  status: 'published',
  tier: 'free',
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
    country: form.country || null,
    website_url: form.website_url || null,
    image_url: form.image_url || null,
    contact_email: form.contact_email || null,
    contact_telegram: form.contact_telegram || null,
    contact_signal: form.contact_signal || null,
    contact_discord: form.contact_discord || null,
    support_url: form.support_url || null,
    tags: tagsInput.value ? tagsInput.value.split(',').map(t => t.trim()).filter(Boolean) : null,
    status: form.status,
    tier: form.tier,
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
    country: vendor.country || '',
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

const products = ref([])
const productsLoading = ref(false)
const showProductForm = ref(false)
const productEditing = ref(null)
const productSaving = ref(false)
const productError = ref('')

function emptyVariant() {
  return { label: '', price: null, availability: 'in_stock' }
}

const emptyProductForm = () => ({
  name: '',
  category: '',
  strength: '',
  price: null,
  availability: 'in_stock',
  variants: [],
  status: 'published',
  purity_label: '',
  package_size: '',
  description: '',
})

function addVariant() {
  productForm.variants.push(emptyVariant())
}

function removeVariant(index) {
  productForm.variants.splice(index, 1)
}

const productForm = reactive(emptyProductForm())

async function fetchProducts() {
  if (!editingItem.value) return
  productsLoading.value = true
  try {
    const response = await api.get('/admin/community/vendor-products', {
      params: { vendor_id: editingItem.value.id },
    })
    products.value = response.data.data || []
  } catch {
    products.value = []
  } finally {
    productsLoading.value = false
  }
}

 function openProductForm(product) {
   productEditing.value = product || null
   if (product) {
     productForm.name = product.name || ''
     productForm.category = product.category || ''
     productForm.strength = product.strength || ''
     productForm.price = product.price ?? null
     productForm.availability = product.availability || 'in_stock'
     productForm.variants.splice(0, productForm.variants.length)
     ;(product.variants || []).forEach(v => productForm.variants.push({ ...v }))
     productForm.status = product.status || 'published'
     productForm.purity_label = product.purity_label || ''
     productForm.package_size = product.package_size || ''
     productForm.description = product.description || ''
   } else {
     productForm.name = ''
     productForm.category = ''
     productForm.strength = ''
     productForm.price = null
     productForm.availability = 'in_stock'
     productForm.variants.splice(0, productForm.variants.length)
     productForm.status = 'published'
     productForm.purity_label = ''
     productForm.package_size = ''
     productForm.description = ''
   }
   showProductForm.value = true
   productError.value = ''
 }

function closeProductForm() {
  showProductForm.value = false
  productEditing.value = null
  Object.assign(productForm, emptyProductForm())
  productError.value = ''
}

async function saveProduct() {
  productSaving.value = true
  productError.value = ''

  const payload = {
    vendor_id: editingItem.value.id,
    name: productForm.name,
    category: productForm.category || null,
    strength: productForm.strength || null,
    price: productForm.price ?? null,
    availability: productForm.availability,
    variants: productForm.variants.length ? productForm.variants : null,
    status: productForm.status,
    purity_label: productForm.purity_label || null,
    package_size: productForm.package_size || null,
    description: productForm.description || null,
  }

  try {
    if (productEditing.value) {
      await api.patch(`/admin/community/vendor-products/${productEditing.value.id}`, payload)
    } else {
      await api.post('/admin/community/vendor-products', payload)
    }
    closeProductForm()
    await fetchProducts()
  } catch (err) {
    const errors = err.response?.data?.errors
    productError.value = errors ? Object.values(errors)[0]?.[0] || 'Unable to save product.' : err.response?.data?.message || 'Unable to save product.'
  } finally {
    productSaving.value = false
  }
}

async function deleteProduct(product) {
  if (!confirm(`Delete "${product.name}"?`)) return
  productError.value = ''

  try {
    await api.delete(`/admin/community/vendor-products/${product.id}`)
    await fetchProducts()
  } catch (err) {
    productError.value = err.response?.data?.message || 'Unable to delete product.'
  }
}

// Re-fetch products when editing vendor changes
watch(editingItem, (vendor) => {
  if (vendor) {
    fetchProducts()
  } else {
    products.value = []
    closeProductForm()
  }
})

onMounted(fetchVendors)
</script>
