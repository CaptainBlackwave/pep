<template>
  <div class="giphy-picker-wrapper">
    <button type="button" class="pv-small-button" @click="toggle" :disabled="!apiKey">
      <PvIcon name="image" /> GIF
    </button>
    <div v-if="isOpen" class="giphy-picker-dropdown" ref="pickerRef">
      <div class="giphy-search">
        <input
          v-model="query"
          placeholder="Search GIFs..."
          @input="onSearch"
        />
      </div>
      <div class="giphy-grid" ref="gridRef">
        <div
          v-for="gif in gifs"
          :key="gif.id"
          class="giphy-item"
          @click="select(gif)"
        >
          <img
            :src="gif.images.fixed_width.webp || gif.images.fixed_width.url"
            :alt="gif.title"
            loading="lazy"
          />
        </div>
      </div>
      <p v-if="loading" class="giphy-status">Loading...</p>
      <p v-else-if="!apiKey" class="giphy-status">Set VITE_GIPHY_API_KEY in .env</p>
      <p v-else-if="gifs.length === 0 && !loading" class="giphy-status">No GIFs found</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, onBeforeUnmount } from 'vue'
import { GiphyFetch } from '@giphy/js-fetch-api'
import PvIcon from '@/components/peptide/PvIcon.vue'

const emit = defineEmits<{
  select: [url: string]
}>()

const apiKey = import.meta.env.VITE_GIPHY_API_KEY as string ?? ''

const isOpen = ref(false)
const query = ref('')
const gifs = ref<any[]>([])
const loading = ref(false)
const pickerRef = ref<HTMLDivElement>()
const gridRef = ref<HTMLDivElement>()

let gf: GiphyFetch | null = null
let searchTimer: ReturnType<typeof setTimeout> | null = null

if (apiKey) {
  gf = new GiphyFetch(apiKey)
}

async function loadTrending() {
  if (!gf) return
  loading.value = true
  try {
    const result = await gf.trending({ limit: 30 })
    gifs.value = result.data
  } finally {
    loading.value = false
  }
}

async function loadSearch(q: string) {
  if (!gf) return
  loading.value = true
  try {
    const result = await gf.search(q, { limit: 30 })
    gifs.value = result.data
  } finally {
    loading.value = false
  }
}

function onSearch() {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    if (query.value.trim()) {
      loadSearch(query.value.trim())
    } else {
      loadTrending()
    }
  }, 400)
}

function select(gif: any) {
  emit('select', gif.images.original.url)
  close()
}

function close() {
  isOpen.value = false
  query.value = ''
  gifs.value = []
}

async function toggle() {
  if (isOpen.value) {
    close()
  } else {
    isOpen.value = true
    await nextTick()
    loadTrending()
  }
}

function handleClickOutside(e: MouseEvent) {
  if (pickerRef.value && !pickerRef.value.contains(e.target as Node)) {
    close()
  }
}

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

if (apiKey) {
  document.addEventListener('click', handleClickOutside)
}
</script>

<style scoped>
.giphy-picker-wrapper {
  position: relative;
  display: inline-flex;
}

.giphy-picker-dropdown {
  position: absolute;
  bottom: 100%;
  right: 0;
  margin-bottom: 4px;
  width: 320px;
  max-height: 400px;
  background: #1a1d27;
  border: 1px solid var(--pv-border);
  border-radius: 8px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.giphy-search {
  padding: 8px;
  border-bottom: 1px solid var(--pv-border);
}

.giphy-search input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--pv-border);
  border-radius: 6px;
  background: rgba(3, 7, 14, 0.7);
  color: var(--pv-text);
  outline: 0;
  font-size: 14px;
}

.giphy-grid {
  flex: 1;
  overflow-y: auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4px;
  padding: 8px;
}

.giphy-item {
  cursor: pointer;
  border-radius: 4px;
  overflow: hidden;
  transition: opacity 0.15s;
}

.giphy-item:hover {
  opacity: 0.8;
}

.giphy-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.giphy-status {
  padding: 16px;
  text-align: center;
  color: #707789;
  font-size: 13px;
}
</style>
