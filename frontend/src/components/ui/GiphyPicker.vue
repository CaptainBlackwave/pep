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
import PvIcon from '@/components/peptide/PvIcon.vue'

const emit = defineEmits<{
  select: [url: string]
}>()

interface GiphyImage {
  url: string
  webp?: string
}

interface GiphyItem {
  id: string
  title: string
  images: {
    fixed_width: GiphyImage
    original: GiphyImage
  }
}

interface GiphyResponse {
  data?: GiphyItem[]
}

const apiKey = import.meta.env.VITE_GIPHY_API_KEY as string ?? ''

const isOpen = ref(false)
const query = ref('')
const gifs = ref<GiphyItem[]>([])
const loading = ref(false)
const pickerRef = ref<HTMLDivElement>()
const gridRef = ref<HTMLDivElement>()

let searchTimer: ReturnType<typeof setTimeout> | null = null

async function fetchGifs(path: 'trending' | 'search', params: Record<string, string | number> = {}) {
  if (!apiKey) return
  loading.value = true
  try {
    const url = new URL(`https://api.giphy.com/v1/gifs/${path}`)
    url.searchParams.set('api_key', apiKey)
    url.searchParams.set('limit', '30')
    Object.entries(params).forEach(([key, value]) => {
      url.searchParams.set(key, String(value))
    })

    const response = await fetch(url)
    if (!response.ok) {
      throw new Error('Unable to load GIFs')
    }

    const payload = await response.json() as GiphyResponse
    gifs.value = payload.data ?? []
  } finally {
    loading.value = false
  }
}

async function loadTrending() {
  await fetchGifs('trending')
}

async function loadSearch(q: string) {
  await fetchGifs('search', { q })
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

function select(gif: GiphyItem) {
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
