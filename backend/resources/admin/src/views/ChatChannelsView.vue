<template>
  <div class="space-y-6">
    <!-- Search & Actions -->
    <div class="flex items-center justify-between gap-4">
      <div class="relative flex-1 max-w-md">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input v-model="searchQuery" type="text" placeholder="Search channels..." @input="debouncedSearch" class="w-full pl-10 pr-4 py-3 bg-slate-800/50 border border-slate-700/50 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all" />
      </div>
      <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-500/25">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        Create Channel
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
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Slug</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Type</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Active</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Messages</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Members</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Max Members</th>
              <th class="px-6 py-4 text-center text-sm font-semibold text-slate-300">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50">
            <tr v-if="items.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-slate-500">No channels found</td>
            </tr>
            <tr v-for="item in filteredItems" :key="item.id" class="hover:bg-slate-700/25 transition-colors">
              <td class="px-6 py-4 text-sm text-white font-medium">{{ item.name }}</td>
              <td class="px-6 py-4 text-sm text-slate-400 font-mono">{{ item.slug }}</td>
              <td class="px-6 py-4">
                <span :class="typeBadge(item.type)" class="px-2 py-1 rounded-md text-xs font-medium">{{ item.type }}</span>
              </td>
              <td class="px-6 py-4">
                <button @click="toggleActive(item)" :class="item.is_active ? 'bg-green-500/20 text-green-400 hover:bg-green-500/30' : 'bg-slate-500/20 text-slate-400 hover:bg-slate-500/30'" class="px-2 py-1 rounded-md text-xs font-medium transition-colors">
                  {{ item.is_active ? 'Active' : 'Inactive' }}
                </button>
              </td>
              <td class="px-6 py-4 text-sm text-slate-300">{{ item.messages_count ?? 0 }}</td>
              <td class="px-6 py-4 text-sm text-slate-300">{{ item.members_count ?? 0 }}</td>
              <td class="px-6 py-4 text-sm text-slate-300">{{ item.max_members ?? '∞' }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2">
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
        <div class="relative bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl max-w-lg w-full">
          <div class="border-b border-slate-700 p-6 flex items-center justify-between">
            <h2 class="text-xl font-bold text-white">{{ editingItem ? 'Edit' : 'Create' }} Channel</h2>
            <button @click="closeModal" class="text-slate-400 hover:text-white transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2">Name</label>
              <input v-model="formData.name" type="text" required placeholder="e.g. General" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2">Description</label>
              <textarea v-model="formData.description" rows="2" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white" placeholder="Channel description..."></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Type</label>
                <select v-model="formData.type" required class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white">
                  <option value="public">Public</option>
                  <option value="private">Private</option>
                  <option value="game">Game</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Max Members</label>
                <input v-model.number="formData.max_members" type="number" min="2" max="10000" class="w-full px-4 py-2.5 bg-slate-700/50 border-2 border-slate-600/50 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all text-white" placeholder="Unlimited" />
              </div>
            </div>
            <div class="flex items-center gap-3">
              <input v-model="formData.is_active" type="checkbox" class="w-5 h-5 accent-amber-500" />
              <label class="text-sm font-medium text-gray-300">Active</label>
            </div>
          </div>
          <div class="border-t border-slate-700 p-6 flex items-center gap-3 justify-end">
            <button @click="closeModal" class="px-4 py-2 rounded-lg bg-slate-700 text-slate-300 hover:bg-slate-600 transition-colors">Cancel</button>
            <button @click="saveItem" class="px-4 py-2 rounded-lg bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-500/25">Save Channel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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
  type: 'public',
  max_members: null,
  is_active: true,
}

const typeBadge = (type) => {
  const map = {
    public: 'bg-green-500/20 text-green-400',
    private: 'bg-purple-500/20 text-purple-400',
    game: 'bg-amber-500/20 text-amber-400',
  }
  return map[type] || 'bg-slate-500/20 text-slate-400'
}

const filteredItems = computed(() => {
  if (!searchQuery.value) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(i => i.name?.toLowerCase().includes(q) || i.slug?.toLowerCase().includes(q))
})

onMounted(() => fetchItems())

const fetchItems = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get('/admin/chat-channels')
    items.value = Array.isArray(response.data) ? response.data : (response.data.data || [])
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load channels'
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
      await api.put(`/admin/chat-channels/${editingItem.value.id}`, formData.value)
      toast.success('Channel updated successfully!')
    } else {
      await api.post('/admin/chat-channels', formData.value)
      toast.success('Channel created successfully!')
    }
    closeModal()
    fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to save channel')
  }
}

const deleteItem = async (item) => {
  const confirmed = await confirm.confirm(`Delete channel "${item.name}"? All messages will also be deleted.`, 'Delete Channel')
  if (!confirmed) return
  try {
    await api.delete(`/admin/chat-channels/${item.id}`)
    toast.success('Channel deleted successfully!')
    fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to delete channel')
  }
}

const toggleActive = async (item) => {
  try {
    await api.put(`/admin/chat-channels/${item.id}`, { is_active: !item.is_active })
    toast.success(`Channel ${item.is_active ? 'deactivated' : 'activated'}!`)
    fetchItems()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to update channel')
  }
}

let searchTimeout
const debouncedSearch = () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => {}, 300) }
</script>
