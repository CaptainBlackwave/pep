<template>
  <div
    v-if="showCreateVendor"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4"
    @click.self="closeCreateVendor"
  >
    <form
      class="w-full max-w-2xl rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl"
      @submit.prevent="createVendor"
    >
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="text-xl font-semibold text-white">
            Add Vendor
          </h2>
          <p class="mt-1 text-sm text-slate-400">
            Create a vendor profile that can receive public reviews.
          </p>
        </div>
        <button
          type="button"
          class="rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-800 hover:text-white"
          @click="closeCreateVendor"
        >
          Close
        </button>
      </div>

      <p
        v-if="vendorFormError"
        class="mt-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300"
      >
        {{ vendorFormError }}
      </p>

      <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
        <label class="text-sm text-slate-300">
          Vendor name
          <input
            v-model="createVendorForm.name"
            required
            maxlength="160"
            class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="Vendor name"
          >
        </label>
        <label class="text-sm text-slate-300">
          Slug
          <input
            v-model="createVendorForm.slug"
            maxlength="180"
            class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="auto-generated"
          >
        </label>
        <label class="text-sm text-slate-300">
          Website
          <input
            v-model="createVendorForm.website_url"
            type="url"
            maxlength="255"
            class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="https://example.com"
          >
        </label>
        <label class="text-sm text-slate-300">
          Logo initials
          <input
            v-model="createVendorForm.logo_initials"
            maxlength="12"
            class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
            placeholder="auto"
          >
        </label>
        <label class="text-sm text-slate-300">
          Visibility
          <select
            v-model="createVendorForm.status"
            class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
          >
            <option value="published">
              Published
            </option>
            <option value="hidden">
              Hidden
            </option>
          </select>
        </label>
        <label class="text-sm text-slate-300">
          Trust status
          <select
            v-model="createVendorForm.status_class"
            class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
          >
            <option value="trusted">
              Trusted
            </option>
            <option value="caution">
              Caution
            </option>
            <option value="avoid">
              Avoid
            </option>
          </select>
        </label>
      </div>

      <label class="mt-4 block text-sm text-slate-300">
        Tags
        <input
          v-model="createVendorForm.tags"
          maxlength="500"
          class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
          placeholder="Fast Shipping, Lab Tested"
        >
      </label>

      <label class="mt-4 block text-sm text-slate-300">
        Description
        <textarea
          v-model="createVendorForm.description"
          rows="4"
          maxlength="4000"
          class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
          placeholder="Short vendor profile..."
        />
      </label>

      <div class="mt-6 flex flex-wrap justify-end gap-3">
        <button
          type="button"
          class="rounded-xl bg-slate-800 px-5 py-3 font-medium text-slate-200 hover:bg-slate-700"
          @click="closeCreateVendor"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-3 font-medium text-white hover:from-violet-700 hover:to-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="creatingVendor"
        >
          {{ creatingVendor ? 'Adding...' : 'Add Vendor' }}
        </button>
      </div>
    </form>
  </div>

  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">
          Community Vendors
        </h1>
        <p class="text-slate-400 mt-1">
          Manage vendor visibility, trust status, and submitted reviews.
        </p>
      </div>
      <div class="flex flex-wrap gap-3">
        <button
          class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2 font-medium text-white transition-all hover:from-violet-700 hover:to-indigo-700"
          @click="openCreateVendor"
        >
          Add Vendor
        </button>
        <button
          class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors"
          @click="refreshAll"
        >
          Refresh
        </button>
      </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
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
          v-model="vendorFilters.search"
          type="search"
          placeholder="Search vendors..."
          class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
          @keydown.enter="fetchVendors"
        >
        <select
          v-model="vendorFilters.status"
          class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50"
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
          class="px-5 py-3 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-medium hover:from-violet-700 hover:to-indigo-700 transition-all"
          @click="fetchVendors"
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
        v-if="loadingVendors"
        class="p-10 text-center text-slate-400"
      >
        Loading vendors...
      </div>
      <div
        v-else-if="vendors.length === 0"
        class="p-10 text-center text-slate-400"
      >
        No vendors found.
      </div>
      <div
        v-else
        class="overflow-x-auto"
      >
        <table class="w-full">
          <thead class="bg-slate-900/80 border-b border-slate-700">
            <tr>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                Vendor
              </th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                Rating
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
              v-for="vendor in vendors"
              :key="vendor.id"
              class="hover:bg-slate-700/25"
            >
              <td class="px-5 py-4">
                <p class="font-semibold text-white">
                  {{ vendor.name }}
                </p>
                <p class="text-sm text-slate-400 line-clamp-2">
                  {{ vendor.description }}
                </p>
              </td>
              <td class="px-5 py-4 text-sm text-slate-300">
                <div>{{ vendor.rating_label }} / 5</div>
                <div class="text-slate-500">
                  {{ vendor.review_count }} reviews
                </div>
              </td>
              <td class="px-5 py-4">
                <div class="flex flex-wrap gap-2">
                  <span :class="trustClass(vendor.status_class)">
                    {{ vendor.status_label }}
                  </span>
                  <span :class="publishClass(vendor.status)">
                    {{ vendor.status }}
                  </span>
                </div>
              </td>
              <td class="px-5 py-4">
                <div class="flex flex-wrap justify-end gap-2">
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25"
                    @click="setTrust(vendor, 'Trusted', 'trusted')"
                  >
                    Trust
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-500/15 text-amber-300 hover:bg-amber-500/25"
                    @click="setTrust(vendor, 'Caution', 'caution')"
                  >
                    Caution
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/15 text-red-300 hover:bg-red-500/25"
                    @click="setTrust(vendor, 'Avoid', 'avoid')"
                  >
                    Avoid
                  </button>
                  <button
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-500/15 text-slate-300 hover:bg-slate-500/25"
                    @click="toggleVendorStatus(vendor)"
                  >
                    {{ vendor.status === 'published' ? 'Hide' : 'Publish' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="rounded-2xl bg-slate-800/50 border border-slate-700/50 overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b border-slate-700/60">
        <div>
          <h2 class="text-lg font-semibold text-white">
            Vendor Reviews
          </h2>
          <p class="text-sm text-slate-400">
            Moderate submitted vendor reviews.
          </p>
        </div>
        <select
          v-model="reviewFilters.status"
          class="px-3 py-2 bg-slate-900/60 border border-slate-700 rounded-xl text-white"
          @change="fetchReviews"
        >
          <option value="pending">
            Pending
          </option>
          <option value="published">
            Published
          </option>
          <option value="hidden">
            Hidden
          </option>
          <option value="all">
            All
          </option>
        </select>
      </div>
      <div
        v-if="loadingReviews"
        class="p-10 text-center text-slate-400"
      >
        Loading reviews...
      </div>
      <div
        v-else-if="reviews.length === 0"
        class="p-10 text-center text-slate-400"
      >
        No reviews found.
      </div>
      <div
        v-else
        class="divide-y divide-slate-700/60"
      >
        <article
          v-for="review in reviews"
          :key="review.id"
          class="p-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between hover:bg-slate-700/25"
        >
          <div class="max-w-3xl">
            <p class="font-semibold text-white">
              {{ review.title }}
            </p>
            <p class="text-sm text-slate-400">
              {{ [review.author?.name, `${review.rating} / 5`, review.vendor?.name].filter(Boolean).join(' · ') }}
            </p>
            <p class="text-sm text-slate-300 mt-2 line-clamp-2">
              {{ review.body }}
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              class="px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25"
              @click="updateReview(review, { status: 'published', is_verified_buyer: true })"
            >
              Publish
            </button>
            <button
              class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/15 text-red-300 hover:bg-red-500/25"
              @click="updateReview(review, { status: 'hidden' })"
            >
              Hide
            </button>
          </div>
        </article>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../services/api'

const vendors = ref([])
const reviews = ref([])
const loadingVendors = ref(false)
const loadingReviews = ref(false)
const error = ref('')
const showCreateVendor = ref(false)
const creatingVendor = ref(false)
const vendorFormError = ref('')
const stats = ref({
  total: 0,
  published: 0,
  hidden: 0,
  trusted: 0,
  caution: 0,
  avoid: 0,
})

const vendorFilters = reactive({
  search: '',
  status: 'all',
})

const reviewFilters = reactive({
  status: 'pending',
})

const trustLabels = {
  trusted: 'Trusted',
  caution: 'Caution',
  avoid: 'Avoid',
}

const createVendorForm = reactive(defaultVendorForm())

const statCards = computed(() => [
  { label: 'Total', value: stats.value.total },
  { label: 'Published', value: stats.value.published },
  { label: 'Hidden', value: stats.value.hidden },
  { label: 'Trusted', value: stats.value.trusted },
  { label: 'Caution', value: stats.value.caution },
  { label: 'Avoid', value: stats.value.avoid },
])

function defaultVendorForm() {
  return {
    name: '',
    slug: '',
    website_url: '',
    logo_initials: '',
    status: 'published',
    status_class: 'trusted',
    tags: '',
    description: '',
  }
}

function resetCreateVendorForm() {
  Object.assign(createVendorForm, defaultVendorForm())
  vendorFormError.value = ''
}

function openCreateVendor() {
  resetCreateVendorForm()
  showCreateVendor.value = true
}

function closeCreateVendor() {
  if (creatingVendor.value) {
    return
  }

  showCreateVendor.value = false
}

function splitTags(value) {
  return value
    .split(',')
    .map(tag => tag.trim())
    .filter(Boolean)
    .slice(0, 12)
}

async function createVendor() {
  creatingVendor.value = true
  vendorFormError.value = ''

  try {
    await api.post('/admin/community/vendors', {
      name: createVendorForm.name,
      slug: createVendorForm.slug || undefined,
      website_url: createVendorForm.website_url || null,
      logo_initials: createVendorForm.logo_initials || null,
      status: createVendorForm.status,
      status_class: createVendorForm.status_class,
      status_label: trustLabels[createVendorForm.status_class],
      tags: splitTags(createVendorForm.tags),
      description: createVendorForm.description || null,
    })

    showCreateVendor.value = false
    resetCreateVendorForm()
    await fetchVendors()
  } catch (err) {
    vendorFormError.value = err.response?.data?.message || 'Unable to add vendor.'
  } finally {
    creatingVendor.value = false
  }
}

async function fetchVendors() {
  loadingVendors.value = true
  error.value = ''

  try {
    const response = await api.get('/admin/community/vendors', {
      params: {
        search: vendorFilters.search || undefined,
        status: vendorFilters.status,
      },
    })
    vendors.value = response.data.data || []
    stats.value = response.data.meta?.stats || stats.value
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load vendors.'
  } finally {
    loadingVendors.value = false
  }
}

async function fetchReviews() {
  loadingReviews.value = true

  try {
    const response = await api.get('/admin/community/vendor-reviews', {
      params: {
        status: reviewFilters.status,
      },
    })
    reviews.value = response.data.data || []
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load vendor reviews.'
  } finally {
    loadingReviews.value = false
  }
}

async function updateVendor(vendor, payload) {
  const response = await api.patch(`/admin/community/vendors/${vendor.slug}`, payload)
  const updated = response.data.data
  const index = vendors.value.findIndex(item => item.id === updated.id)

  if (index >= 0) {
    vendors.value[index] = updated
  }

  await fetchVendors()
}

function setTrust(vendor, label, statusClass) {
  void updateVendor(vendor, {
    status_label: label,
    status_class: statusClass,
  })
}

function toggleVendorStatus(vendor) {
  void updateVendor(vendor, {
    status: vendor.status === 'published' ? 'hidden' : 'published',
  })
}

async function updateReview(review, payload) {
  await api.patch(`/admin/community/vendor-reviews/${review.id}`, payload)
  await Promise.all([fetchReviews(), fetchVendors()])
}

function refreshAll() {
  void Promise.all([fetchVendors(), fetchReviews()])
}

function trustClass(statusClass) {
  if (statusClass === 'avoid') {
    return 'px-2 py-1 rounded-lg bg-red-500/15 text-red-300 text-xs capitalize'
  }

  if (statusClass === 'caution') {
    return 'px-2 py-1 rounded-lg bg-amber-500/15 text-amber-300 text-xs capitalize'
  }

  return 'px-2 py-1 rounded-lg bg-emerald-500/15 text-emerald-300 text-xs capitalize'
}

function publishClass(status) {
  return status === 'published'
    ? 'px-2 py-1 rounded-lg bg-sky-500/15 text-sky-300 text-xs capitalize'
    : 'px-2 py-1 rounded-lg bg-slate-500/20 text-slate-300 text-xs capitalize'
}

onMounted(refreshAll)
</script>
