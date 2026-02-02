<template>
  <div class="wiki-container">
    <div class="wiki-sidebar">
      <div class="sidebar-header">
        <h3>Wiki</h3>
      </div>
      
      <div class="category-list">
        <div
          v-for="category in categories"
          :key="category.id"
          :class="['category-item', {active: selectedCategory?.id === category.id}]"
          @click="selectCategory(category)"
        >
          <span class="category-icon">📁</span>
          <span class="category-name">{{ category.name }}</span>
          <span class="page-count">({{ category.pages_count || 0 }})</span>
        </div>
      </div>
    </div>

    <div class="wiki-main">
      <div v-if="!selectedPage" class="wiki-landing">
        <h1>Welcome to OpenPBBG Wiki</h1>
        <p>Select a category from the sidebar to browse articles, or search for specific topics.</p>
        
        <div class="search-box">
          <input
            v-model="searchQuery"
            @keyup.enter="searchWiki"
            type="text"
            placeholder="Search wiki..."
            class="search-input"
          />
          <button @click="searchWiki" class="btn-search">Search</button>
        </div>

        <div v-if="popularPages.length > 0" class="popular-section">
          <h2>Popular Articles</h2>
          <div class="popular-grid">
            <div
              v-for="page in popularPages"
              :key="page.id"
              class="popular-card"
              @click="loadPage(page)"
            >
              <h3>{{ page.title }}</h3>
              <p>{{ page.excerpt }}</p>
              <span class="view-count">👁️ {{ page.views || 0 }} views</span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="wiki-content">
        <div class="page-header">
          <button @click="selectedPage = null" class="btn-back">← Back</button>
          <h1>{{ selectedPage.title }}</h1>
          <div class="page-meta">
            <span>Updated: {{ formatDate(selectedPage.updated_at) }}</span>
            <span>Views: {{ selectedPage.views || 0 }}</span>
          </div>
        </div>

        <div class="page-content" v-html="selectedPage.content"></div>

        <div v-if="relatedPages.length > 0" class="related-section">
          <h3>Related Articles</h3>
          <div class="related-list">
            <div
              v-for="page in relatedPages"
              :key="page.id"
              class="related-item"
              @click="loadPage(page)"
            >
              {{ page.title }}
            </div>
          </div>
        </div>
      </div>

      <!-- Pages List -->
      <div v-if="selectedCategory && !selectedPage" class="pages-list">
        <h2>{{ selectedCategory.name }}</h2>
        <p v-if="selectedCategory.description" class="category-description">
          {{ selectedCategory.description }}
        </p>
        
        <div v-if="categoryPages.length === 0" class="empty-state">
          <p>No pages in this category yet.</p>
        </div>

        <div v-else class="page-grid">
          <div
            v-for="page in categoryPages"
            :key="page.id"
            class="page-card"
            @click="loadPage(page)"
          >
            <h3>{{ page.title }}</h3>
            <p>{{ page.excerpt }}</p>
            <span class="read-more">Read more →</span>
          </div>
        </div>
      </div>

      <!-- Search Results -->
      <div v-if="searchResults.length > 0" class="search-results">
        <h2>Search Results for "{{ searchQuery }}"</h2>
        <div class="results-list">
          <div
            v-for="page in searchResults"
            :key="page.id"
            class="result-item"
            @click="loadPage(page)"
          >
            <h3>{{ page.title }}</h3>
            <p>{{ page.excerpt }}</p>
            <span class="category-badge">{{ page.category?.name }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const categories = ref([])
const popularPages = ref([])
const selectedCategory = ref(null)
const selectedPage = ref(null)
const categoryPages = ref([])
const relatedPages = ref([])
const searchQuery = ref('')
const searchResults = ref([])

onMounted(() => {
  loadCategories()
  loadPopularPages()
})

async function loadCategories() {
  try {
    const response = await api.get('/wiki/categories')
    categories.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load categories:', error)
    categories.value = []
  }
}

async function loadPopularPages() {
  try {
    const response = await api.get('/wiki/popular')
    popularPages.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load popular pages:', error)
    popularPages.value = []
  }
}

async function selectCategory(category) {
  selectedCategory.value = category
  selectedPage.value = null
  searchResults.value = []
  
  try {
    const response = await api.get(`/wiki/categories/${category.id}/pages`)
    categoryPages.value = response.data
  } catch (error) {
    console.error('Failed to load category pages:', error)
  }
}

async function loadPage(page) {
  try {
    const response = await api.get(`/wiki/pages/${page.id}`)
    selectedPage.value = response.data
    searchResults.value = []
    
    // Load related pages
    if (response.data.category_id) {
      const relatedRes = await api.get(`/wiki/categories/${response.data.category_id}/pages`)
      relatedPages.value = relatedRes.data.filter(p => p.id !== page.id).slice(0, 5)
    }
  } catch (error) {
    console.error('Failed to load page:', error)
  }
}

async function searchWiki() {
  if (!searchQuery.value.trim()) return
  
  selectedCategory.value = null
  selectedPage.value = null
  
  try {
    const response = await api.get('/wiki/search', {
      params: { q: searchQuery.value }
    })
    searchResults.value = response.data
  } catch (error) {
    console.error('Search failed:', error)
  }
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}
</script>

<style scoped>
.wiki-container {
  display: flex;
  height: calc(100vh - 120px);
  background: #1a1a2e;
  border-radius: 8px;
  overflow: hidden;
}

.wiki-sidebar {
  width: 280px;
  background: #16213e;
  border-right: 1px solid #0f3460;
  overflow-y: auto;
}

.sidebar-header {
  padding: 20px;
  border-bottom: 1px solid #0f3460;
}

.sidebar-header h3 {
  margin: 0;
  color: #e94560;
  font-size: 24px;
}

.category-list {
  padding: 10px;
}

.category-item {
  padding: 12px 15px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  border-radius: 6px;
  margin-bottom: 5px;
  transition: all 0.3s ease;
}

.category-item:hover {
  background: #0f3460;
}

.category-item.active {
  background: #0f3460;
  border-left: 3px solid #e94560;
}

.category-icon {
  font-size: 18px;
}

.category-name {
  flex: 1;
  color: #fff;
  font-weight: 500;
}

.page-count {
  color: #8b8b8b;
  font-size: 12px;
}

.wiki-main {
  flex: 1;
  overflow-y: auto;
  padding: 30px;
}

.wiki-landing {
  max-width: 900px;
  margin: 0 auto;
}

.wiki-landing h1 {
  color: #e94560;
  margin: 0 0 15px 0;
  font-size: 36px;
}

.wiki-landing > p {
  color: #8b8b8b;
  font-size: 18px;
  margin-bottom: 30px;
}

.search-box {
  display: flex;
  gap: 10px;
  margin-bottom: 50px;
}

.search-input {
  flex: 1;
  padding: 15px;
  background: #16213e;
  border: 2px solid #0f3460;
  border-radius: 8px;
  color: white;
  font-size: 16px;
  outline: none;
}

.search-input:focus {
  border-color: #e94560;
}

.btn-search {
  padding: 15px 30px;
  background: #e94560;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-search:hover {
  background: #ff6b81;
}

.popular-section {
  margin-top: 40px;
}

.popular-section h2 {
  color: #e94560;
  margin-bottom: 20px;
}

.popular-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
}

.popular-card {
  background: #16213e;
  padding: 20px;
  border-radius: 8px;
  border: 1px solid #0f3460;
  cursor: pointer;
  transition: all 0.3s ease;
}

.popular-card:hover {
  border-color: #e94560;
  transform: translateY(-5px);
}

.popular-card h3 {
  color: #fff;
  margin: 0 0 10px 0;
  font-size: 18px;
}

.popular-card p {
  color: #8b8b8b;
  font-size: 14px;
  margin-bottom: 10px;
}

.view-count {
  color: #e94560;
  font-size: 13px;
}

.wiki-content {
  max-width: 900px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 30px;
}

.btn-back {
  padding: 8px 16px;
  background: #16213e;
  color: #e94560;
  border: 1px solid #0f3460;
  border-radius: 6px;
  cursor: pointer;
  margin-bottom: 20px;
  transition: all 0.3s ease;
}

.btn-back:hover {
  background: #0f3460;
}

.page-header h1 {
  color: #e94560;
  margin: 0 0 10px 0;
}

.page-meta {
  display: flex;
  gap: 20px;
  color: #8b8b8b;
  font-size: 14px;
}

.page-content {
  background: #16213e;
  padding: 30px;
  border-radius: 8px;
  color: #fff;
  line-height: 1.8;
  margin-bottom: 30px;
}

.page-content :deep(h1),
.page-content :deep(h2),
.page-content :deep(h3) {
  color: #e94560;
  margin-top: 1.5em;
  margin-bottom: 0.5em;
}

.page-content :deep(p) {
  margin-bottom: 1em;
}

.page-content :deep(code) {
  background: #0f3460;
  padding: 2px 6px;
  border-radius: 3px;
}

.page-content :deep(pre) {
  background: #0f3460;
  padding: 15px;
  border-radius: 6px;
  overflow-x: auto;
}

.related-section {
  background: #16213e;
  padding: 20px;
  border-radius: 8px;
}

.related-section h3 {
  color: #e94560;
  margin: 0 0 15px 0;
}

.related-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.related-item {
  padding: 12px;
  background: #0f3460;
  border-radius: 6px;
  color: #fff;
  cursor: pointer;
  transition: all 0.3s ease;
}

.related-item:hover {
  background: #1a3a5c;
  color: #e94560;
}

.pages-list {
  max-width: 900px;
  margin: 0 auto;
}

.pages-list h2 {
  color: #e94560;
  margin-bottom: 10px;
}

.category-description {
  color: #8b8b8b;
  margin-bottom: 30px;
}

.page-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.page-card {
  background: #16213e;
  padding: 20px;
  border-radius: 8px;
  border: 1px solid #0f3460;
  cursor: pointer;
  transition: all 0.3s ease;
}

.page-card:hover {
  border-color: #e94560;
  transform: translateY(-5px);
}

.page-card h3 {
  color: #fff;
  margin: 0 0 10px 0;
}

.page-card p {
  color: #8b8b8b;
  font-size: 14px;
  margin-bottom: 15px;
}

.read-more {
  color: #e94560;
  font-weight: 600;
  font-size: 14px;
}

.search-results {
  max-width: 900px;
  margin: 0 auto;
}

.search-results h2 {
  color: #e94560;
  margin-bottom: 20px;
}

.results-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.result-item {
  background: #16213e;
  padding: 20px;
  border-radius: 8px;
  border: 1px solid #0f3460;
  cursor: pointer;
  transition: all 0.3s ease;
}

.result-item:hover {
  border-color: #e94560;
}

.result-item h3 {
  color: #fff;
  margin: 0 0 10px 0;
}

.result-item p {
  color: #8b8b8b;
  margin-bottom: 10px;
}

.category-badge {
  display: inline-block;
  padding: 4px 12px;
  background: #0f3460;
  color: #e94560;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #8b8b8b;
}

@media (max-width: 768px) {
  .wiki-container {
    flex-direction: column;
  }
  
  .wiki-sidebar {
    width: 100%;
    height: auto;
    max-height: 200px;
  }
  
  .popular-grid,
  .page-grid {
    grid-template-columns: 1fr;
  }
}
</style>
