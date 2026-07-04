<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Discussion Categories</h1>
        <p class="text-slate-400 mt-1">Manage Peptide Vendors discussion categories.</p>
      </div>
      <div class="flex gap-3">
        <button
          class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors"
          @click="fetchCategories"
        >Refresh</button>
        <button
          class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-amber-500 text-black font-medium hover:bg-amber-400 transition-colors"
          @click="openCreate"
        >New Category</button>
      </div>
    </div>

    <div class="rounded-2xl bg-slate-800/50 border border-slate-700/50 overflow-hidden">
      <table class="w-full text-left" v-if="categories.length > 0">
        <thead>
          <tr class="border-b border-slate-700/50 text-sm text-slate-400">
            <th class="px-5 py-4 font-medium">Order</th>
            <th class="px-5 py-4 font-medium">Name</th>
            <th class="px-5 py-4 font-medium">Slug</th>
            <th class="px-5 py-4 font-medium">Icon</th>
            <th class="px-5 py-4 font-medium">Color</th>
            <th class="px-5 py-4 font-medium text-right">Discussions</th>
            <th class="px-5 py-4 font-medium text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="cat in categories" :key="cat.id" class="border-b border-slate-700/30 text-white hover:bg-slate-700/20">
            <td class="px-5 py-4 text-slate-400">{{ cat.sort_order }}</td>
            <td class="px-5 py-4 font-medium">{{ cat.name }}</td>
            <td class="px-5 py-4 text-slate-400 font-mono text-sm">{{ cat.slug }}</td>
            <td class="px-5 py-4">
              <code class="text-xs bg-slate-900/60 px-2 py-1 rounded">{{ cat.icon }}</code>
            </td>
            <td class="px-5 py-4">
              <span class="inline-flex items-center gap-2">
                <span class="w-4 h-4 rounded-full border border-slate-600" :style="{ backgroundColor: cat.color }"></span>
                <code class="text-xs text-slate-400">{{ cat.color }}</code>
              </span>
            </td>
            <td class="px-5 py-4 text-right text-slate-400">{{ cat.discussions_count }}</td>
            <td class="px-5 py-4 text-right">
              <button class="text-amber-400 hover:text-amber-300 text-sm mr-3" @click="openEdit(cat)">Edit</button>
              <button class="text-red-400 hover:text-red-300 text-sm" @click="confirmDelete(cat)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else-if="loading" class="text-slate-400 text-center py-12">Loading categories...</p>
      <p v-else class="text-slate-400 text-center py-12">No categories yet. Create your first one.</p>
    </div>

    <div v-if="showForm" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" @click.self="closeForm">
      <form class="bg-slate-800 rounded-2xl border border-slate-700 p-6 w-full max-w-lg mx-4 space-y-5" @submit.prevent="saveCategory">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-bold text-white">{{ editing ? 'Edit Category' : 'New Category' }}</h2>
          <button type="button" class="text-slate-400 hover:text-white text-xl" @click="closeForm">&times;</button>
        </div>

        <p v-if="formError" class="text-red-400 text-sm bg-red-900/20 px-4 py-2 rounded-lg">{{ formError }}</p>

        <label class="block">
          <span class="text-sm text-slate-300 block mb-1">Name</span>
          <input v-model="form.name" required maxlength="100"
            class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50">
        </label>

        <label class="block">
          <span class="text-sm text-slate-300 block mb-1">Description</span>
          <input v-model="form.description" maxlength="255"
            class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50">
        </label>

        <div class="grid grid-cols-2 gap-4">
          <label class="block">
            <span class="text-sm text-slate-300 block mb-1">Slug (leave blank to auto-generate)</span>
            <input v-model="form.slug" maxlength="100"
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50">
          </label>
          <label class="block">
            <span class="text-sm text-slate-300 block mb-1">Sort Order</span>
            <input v-model.number="form.sort_order" type="number" min="0" max="999"
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50">
          </label>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <label class="block">
            <span class="text-sm text-slate-300 block mb-1">Icon</span>
            <select v-model="form.icon"
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-amber-500/50">
              <option value="discussions">discussions</option>
              <option value="box">box</option>
              <option value="flask">flask</option>
              <option value="document">document</option>
              <option value="share">share</option>
              <option value="library">library</option>
              <option value="question">question</option>
            </select>
          </label>
          <label class="block">
            <span class="text-sm text-slate-300 block mb-1">Color</span>
            <select v-model="form.color"
              class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-amber-500/50">
              <option value="purple">Purple</option>
              <option value="blue">Blue</option>
              <option value="green">Green</option>
              <option value="orange">Orange</option>
              <option value="teal">Teal</option>
              <option value="pink">Pink</option>
              <option value="gray">Gray</option>
            </select>
          </label>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" class="px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600 transition-colors" @click="closeForm">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 text-black font-medium hover:bg-amber-400 transition-colors" :disabled="saving">
            {{ saving ? 'Saving...' : editing ? 'Update' : 'Create' }}
          </button>
        </div>
      </form>
    </div>

    <div v-if="showDelete" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" @click.self="showDelete = false">
      <div class="bg-slate-800 rounded-2xl border border-slate-700 p-6 w-full max-w-sm mx-4 space-y-4">
        <h2 class="text-xl font-bold text-white">Delete Category</h2>
        <p class="text-slate-300">Delete <strong class="text-white">{{ deleting?.name }}</strong>? Existing discussions will be uncategorized.</p>
        <div class="flex justify-end gap-3">
          <button class="px-4 py-2 rounded-xl bg-slate-700 text-slate-200 hover:bg-slate-600" @click="showDelete = false">Cancel</button>
          <button class="px-4 py-2 rounded-xl bg-red-500 text-white font-medium hover:bg-red-400" @click="deleteCategory">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'

const categories = ref([])
const loading = ref(false)
const showForm = ref(false)
const showDelete = ref(false)
const editing = ref(null)
const deleting = ref(null)
const saving = ref(false)
const formError = ref('')
const form = ref({ name: '', slug: '', description: '', icon: 'discussions', color: 'purple', sort_order: 0 })

async function fetchCategories() {
  loading.value = true
  try {
    const res = await api.get('/admin/community/categories')
    categories.value = res.data
  } catch {
    categories.value = []
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  form.value = { name: '', slug: '', description: '', icon: 'discussions', color: 'purple', sort_order: 0 }
  formError.value = ''
  showForm.value = true
}

function openEdit(cat) {
  editing.value = cat
  form.value = { name: cat.name, slug: cat.slug, description: cat.description ?? '', icon: cat.icon, color: cat.color, sort_order: cat.sort_order }
  formError.value = ''
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editing.value = null
}

async function saveCategory() {
  saving.value = true
  formError.value = ''
  try {
    if (editing.value) {
      await api.patch(`/admin/community/categories/${editing.value.id}`, form.value)
    } else {
      await api.post('/admin/community/categories', form.value)
    }
    closeForm()
    await fetchCategories()
  } catch (err) {
    formError.value = err.response?.data?.message ?? err.response?.data?.errors?.name?.[0] ?? 'Failed to save category.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(cat) {
  deleting.value = cat
  showDelete.value = true
}

async function deleteCategory() {
  try {
    await api.delete(`/admin/community/categories/${deleting.value.id}`)
    showDelete.value = false
    deleting.value = null
    await fetchCategories()
  } catch {
    // silently fail
  }
}

onMounted(fetchCategories)
</script>
