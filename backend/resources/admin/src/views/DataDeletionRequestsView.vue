<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <h1 class="text-2xl font-bold text-white">Data Deletion Requests</h1>
      <div class="flex gap-2">
        <select v-model="statusFilter" @change="fetchRequests" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white text-sm">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="rounded-2xl bg-slate-800/50 border border-slate-700/50 p-6">
      <TableSkeleton :rows="5" />
    </div>

    <div v-else class="rounded-2xl bg-slate-800/50 border border-slate-700/50 overflow-hidden">
      <table class="w-full">
        <thead class="bg-slate-700/50 border-b border-slate-600/50">
          <tr>
            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Email</th>
            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Reason</th>
            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Status</th>
            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Submitted</th>
            <th class="px-6 py-4 text-center text-sm font-semibold text-slate-300">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/50">
          <tr v-for="item in items" :key="item.id" class="hover:bg-slate-700/25 transition-colors">
            <td class="px-6 py-4 text-sm text-slate-300">{{ item.email }}</td>
            <td class="px-6 py-4 text-sm text-slate-300 max-w-xs truncate">{{ item.reason || '-' }}</td>
            <td class="px-6 py-4 text-sm">
              <span :class="['inline-flex items-center px-2.5 py-1 rounded text-xs font-medium', statusClass(item.status)]">{{ item.status }}</span>
            </td>
            <td class="px-6 py-4 text-sm text-slate-300">{{ new Date(item.created_at).toLocaleString() }}</td>
            <td class="px-6 py-4 text-center">
              <button class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 transition-colors" @click="openModal(item)">Review</button>
            </td>
          </tr>
          <tr v-if="items.length === 0">
            <td colspan="5" class="px-6 py-8 text-center text-slate-500">No requests found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="pagination" class="flex items-center justify-between">
      <span class="text-sm text-slate-400">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
      <div class="flex gap-2">
        <button :disabled="!pagination.prev_page_url" class="px-3 py-1.5 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 disabled:opacity-50 text-sm" @click="changePage(pagination.current_page - 1)">Previous</button>
        <button :disabled="!pagination.next_page_url" class="px-3 py-1.5 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 disabled:opacity-50 text-sm" @click="changePage(pagination.current_page + 1)">Next</button>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModal = false">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
        <div class="relative bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl max-w-lg w-full p-6">
          <h2 class="text-xl font-bold text-white mb-4">Review Request</h2>
          <div class="space-y-3 mb-6">
            <div><span class="text-sm text-slate-400">Email:</span><p class="text-white">{{ editingItem.email }}</p></div>
            <div><span class="text-sm text-slate-400">Reason:</span><p class="text-white">{{ editingItem.reason || 'No reason provided' }}</p></div>
            <div><span class="text-sm text-slate-400">Submitted:</span><p class="text-white">{{ new Date(editingItem.created_at).toLocaleString() }}</p></div>
          </div>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
              <select v-model="formData.status" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg text-white">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2">Admin Notes</label>
              <textarea v-model="formData.admin_notes" rows="3" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg text-white" placeholder="Notes about this request..."></textarea>
            </div>
          </div>
          <div class="flex items-center gap-3 justify-end mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition-colors" @click="showModal = false">Cancel</button>
            <button class="px-4 py-2 rounded-lg bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium hover:from-amber-600 hover:to-orange-700 transition-all" @click="saveRequest">Save</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import TableSkeleton from '@/components/TableSkeleton.vue'

const toast = useToast()
const items = ref([])
const loading = ref(true)
const pagination = ref(null)
const statusFilter = ref('')
const showModal = ref(false)
const editingItem = ref(null)
const formData = ref({ status: 'pending', admin_notes: '' })

onMounted(() => fetchRequests())

async function fetchRequests(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/admin/data-deletion-requests', { params: { page, status: statusFilter.value || undefined } })
    items.value = res.data.data
    pagination.value = res.data.meta
  } catch {
    items.value = []
    pagination.value = null
  } finally {
    loading.value = false
  }
}

function changePage(page) {
  if (page < 1 || (pagination.value && page > pagination.value.last_page)) return
  fetchRequests(page)
}

function openModal(item) {
  editingItem.value = item
  formData.value = { status: item.status, admin_notes: item.admin_notes || '' }
  showModal.value = true
}

async function saveRequest() {
  try {
    await api.patch(`/admin/data-deletion-requests/${editingItem.value.id}`, formData.value)
    toast.success('Request updated!')
    showModal.value = false
    await fetchRequests()
  } catch {
    toast.error('Failed to update request.')
  }
}

function statusClass(status) {
  return {
    pending: 'bg-amber-500/20 text-amber-300',
    approved: 'bg-emerald-500/20 text-emerald-300',
    rejected: 'bg-red-500/20 text-red-300',
  }[status] || ''
}
</script>
