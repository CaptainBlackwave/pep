<template>
  <div class="space-y-6">
    <!-- Search & Actions -->
    <div class="flex items-center justify-between gap-4">
      <div class="relative flex-1 max-w-md">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input v-model="searchQuery" type="text" placeholder="Search events..." @input="debouncedSearch" class="w-full pl-10 pr-4 py-3 bg-slate-800/50 border border-slate-700/50 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all" />
      </div>
      <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-500/25">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        Create Event
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-12 flex justify-center">
      <div class="w-8 h-8 border-2 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-2xl bg-red-500/10 border border-red-500/30 p-6">
      <div class="flex items-center gap-3">
        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        <p class="text-red-400 font-medium">{{ error }}</p>
      </div>
    </div>

    <!-- Table -->
    <div v-else class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-700/50 border-b border-slate-600/50">
            <tr>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Name</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Type</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Status</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Starts At</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Ends At</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Participants</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Entry Fee</th>
              <th class="px-6 py-4 text-center text-sm font-semibold text-slate-300">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50">
            <tr v-if="items.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-slate-500">No events found</td>
            </tr>
            <tr v-for="item in items" :key="item.id" class="hover:bg-slate-700/25 transition-colors">
              <td class="px-6 py-4 text-sm text-white font-medium">{{ item.name }}</td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 rounded-md text-xs font-medium bg-blue-500/20 text-blue-400">{{ formatType(item.type) }}</span>
              </td>
              <td class="px-6 py-4">
                <span :class="statusBadge(item.status)" class="px-2 py-1 rounded-md text-xs font-medium">{{ item.status }}</span>
              </td>
              <td class="px-6 py-4 text-sm text-slate-300">{{ formatDate(item.starts_at) }}</td>
              <td class="px-6 py-4 text-sm text-slate-300">{{ formatDate(item.ends_at) }}</td>
              <td class="px-6 py-4 text-sm text-slate-300">{{ item.participants_count ?? 0 }}</td>
              <td class="px-6 py-4 text-sm text-slate-300">{{ item.entry_fee ? `$${Number(item.entry_fee).toLocaleString()}` : 'Free' }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2 flex-wrap">
                  <button v-if="item.status === 'scheduled'" @click="startEvent(item)" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-green-500/20 text-green-400 hover:bg-green-500/30 transition-colors">Start</button>
                  <button v-if="item.status === 'active'" @click="endEvent(item)" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-orange-500/20 text-orange-400 hover:bg-orange-500/30 transition-colors">End</button>
                  <button @click="openEdit(item)" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-500/20 text-amber-400 hover:bg-amber-500/30 transition-colors">Edit</button>
                  <button @click="deleteItem(item)" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-colors">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>
        <div class="relative bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-slate-800 border-b border-slate-700 p-6 flex items-center justify-between">
            <h2 class="text-xl font-bold text-white">{{ editingItem ? 'Edit' : 'Create' }} Event</h2>
            <button @click="closeModal" class="text-slate-400 hover:text-white transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-2 gap-6">
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-2">Name</label>
                <input v-model="formData.name" type="text" required placeholder="Event name" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white" />
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                <textarea v-model="formData.description" rows="3" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white" placeholder="Describe the event..."></textarea>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Type</label>
                <select v-model="formData.type" required class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white">
                  <option value="competition">Competition</option>
                  <option value="seasonal">Seasonal</option>
                  <option value="boss_raid">Boss Raid</option>
                  <option value="double_xp">Double XP</option>
                  <option value="double_money">Double Money</option>
                  <option value="treasure_hunt">Treasure Hunt</option>
                  <option value="pvp_tournament">PvP Tournament</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Entry Fee ($)</label>
                <input v-model.number="formData.entry_fee" type="number" min="0" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white" placeholder="0 = Free" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Starts At</label>
                <input v-model="formData.starts_at" type="datetime-local" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Ends At</label>
                <input v-model="formData.ends_at" type="datetime-local" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Min Level</label>
                <input v-model.number="formData.min_level" type="number" min="1" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Max Participants</label>
                <input v-model.number="formData.max_participants" type="number" min="1" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white" placeholder="Unlimited" />
              </div>
              <div class="col-span-2 flex items-center gap-3">
                <input v-model="formData.recurring" type="checkbox" class="w-5 h-5 accent-amber-500" />
                <label class="text-sm font-medium text-gray-300">Recurring Event</label>
              </div>
            </div>
          </div>
          <div class="sticky bottom-0 bg-slate-800 border-t border-slate-700 p-6 flex items-center gap-3 justify-end">
            <button @click="closeModal" class="px-4 py-2 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition-colors">Cancel</button>
            <button @click="saveItem" class="px-4 py-2 rounded-lg bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-500/25">Save Event</button>
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
import { useConfirm } from '@/composables/useConfirm'

const toast = useToast()
const confirm = useConfirm()

const items = ref([])
const loading = ref(false)
const error = ref(null)
const searchQuery = ref('')
const showModal = ref(false)
const editingItem = ref(null)
const formData = ref({})

const defaultItem = {
  name: '',
  description: '',
  type: 'competition',
  starts_at: '',
  ends_at: '',
  min_level: 1,
  max_participants: null,
  entry_fee: 0,
  recurring: false,
}

const formatType = (type) => {
  if (!type) return ''
  return type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleString()
}

const statusBadge = (status) => {
  const map = {
    scheduled: 'bg-blue-500/20 text-blue-400',
    active: 'bg-green-500/20 text-green-400',
    ended: 'bg-slate-500/20 text-slate-400',
    cancelled: 'bg-red-500/20 text-red-400',
  }
  return map[status] || 'bg-slate-500/20 text-slate-400'
}

onMounted(() => fetchItems())

const fetchItems = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/admin/events', { params: { search: searchQuery.value } })
    items.value = Array.isArray(response.data) ? response.data : (response.data.data || [])
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load events'
  } finally {
    loading.value = false
  }
}

const openCreate = () => { editingItem.value = null; formData.value = { ...defaultItem }; showModal.value = true }
const openEdit = (item) => { editingItem.value = item; formData.value = { ...item }; showModal.value = true }
const closeModal = () => { showModal.value = false; editingItem.value = null; formData.value = {} }

const saveItem = async () => {
  try {
    if (editingItem.value) {
      await api.put(`/admin/events/${editingItem.value.id}`, formData.value)
      toast.success('Event updated successfully!')
    } else {
      await api.post('/admin/events', formData.value)
      toast.success('Event created successfully!')
    }
    closeModal()
    fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to save event')
  }
}

const deleteItem = async (item) => {
  const confirmed = await confirm.confirm('Are you sure you want to delete this event? This action cannot be undone.', 'Delete Event')
  if (!confirmed) return
  try {
    await api.delete(`/admin/events/${item.id}`)
    toast.success('Event deleted successfully!')
    fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to delete event')
  }
}

const startEvent = async (item) => {
  try {
    await api.post(`/admin/events/${item.id}/start`)
    toast.success(`Event "${item.name}" started!`)
    fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to start event')
  }
}

const endEvent = async (item) => {
  const confirmed = await confirm.confirm(`End event "${item.name}" now?`, 'End Event')
  if (!confirmed) return
  try {
    await api.post(`/admin/events/${item.id}/end`)
    toast.success(`Event "${item.name}" ended!`)
    fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to end event')
  }
}

let searchTimeout
const debouncedSearch = () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => fetchItems(), 300) }
</script>
