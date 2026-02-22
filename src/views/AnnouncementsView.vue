<template>
  <div class="announcements-container">
    <div class="header">
      <div class="header-content">
        <router-link to="/dashboard" class="back-link">← Back</router-link>
      </div>
    </div>

    <div class="content-wrapper">
      <div class="announcements-banner">
        <div class="banner-content">
          <div>
            <h1 class="banner-title">📢 Announcements</h1>
            <p class="banner-subtitle">Stay informed with the latest updates</p>
          </div>
          <div class="banner-icon">📰</div>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
      </div>

      <div v-else-if="announcements.length === 0" class="empty-state">
        <div class="empty-icon">📭</div>
        <h3>No announcements</h3>
        <p>Check back later for updates!</p>
      </div>

      <div v-else class="announcements-list">
        <div v-for="announcement in announcements"
             :key="announcement.id"
             :class="['announcement-card', { pinned: announcement.is_pinned, unread: !announcement.is_read }]">
          <div v-if="announcement.is_pinned" class="pinned-badge">📌 Pinned</div>
          <div class="announcement-header">
            <h2 class="announcement-title">{{ announcement.title }}</h2>
            <span class="announcement-date">{{ formatDate(announcement.created_at) }}</span>
          </div>
          <div class="announcement-content" v-html="announcement.content"></div>
          <div class="announcement-footer">
            <span v-if="announcement.author" class="author">
              By {{ announcement.author.name || announcement.author.username }}
            </span>
            <button v-if="!announcement.is_read" @click="markAsRead(announcement.id)" class="mark-read-btn">
              Mark as read
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const announcements = ref([])

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const fetchAnnouncements = async () => {
  try {
    const response = await api.get('/api/v1/announcements')
    announcements.value = response.data.announcements || response.data || []

    // Sort: pinned first, then by date
    announcements.value.sort((a, b) => {
      if (a.is_pinned && !b.is_pinned) return -1
      if (!a.is_pinned && b.is_pinned) return 1
      return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    })
  } catch (err) {
    console.error('Failed to fetch announcements:', err)
  } finally {
    loading.value = false
  }
}

const markAsRead = async (id) => {
  try {
    await api.post(`/api/v1/announcements/${id}/view`)
    const announcement = announcements.value.find(a => a.id === id)
    if (announcement) {
      announcement.is_read = true
    }
  } catch (err) {
    console.error('Failed to mark as read:', err)
  }
}

onMounted(() => {
  fetchAnnouncements()
})
</script>

<style scoped>
.announcements-container {
  min-height: 100vh;
  background: linear-gradient(to bottom right, #111827, #1f2937, #111827);
}

.header {
  background-color: rgba(31, 41, 55, 0.5);
  padding: 1rem 1.5rem;
}

.header-content {
  max-width: 800px;
  margin: 0 auto;
}

.back-link {
  color: #9ca3af;
  text-decoration: none;
  font-size: 0.875rem;
  transition: color 0.2s;
}

.back-link:hover {
  color: #00bcd4;
}

.content-wrapper {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.announcements-banner {
  background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 2rem;
  border: 1px solid rgba(75, 85, 99, 0.3);
}

.banner-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.banner-title {
  font-size: 2rem;
  font-weight: 700;
  color: #f9fafb;
  margin: 0 0 0.5rem;
}

.banner-subtitle {
  color: #9ca3af;
  margin: 0;
}

.banner-icon {
  font-size: 4rem;
  opacity: 0.5;
}

.loading-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.spinner {
  width: 3rem;
  height: 3rem;
  border: 3px solid rgba(0, 188, 212, 0.2);
  border-top-color: #00bcd4;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #9ca3af;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state h3 {
  font-size: 1.25rem;
  color: #f9fafb;
  margin: 0 0 0.5rem;
}

.empty-state p {
  margin: 0;
}

.announcements-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.announcement-card {
  background: rgba(31, 41, 55, 0.5);
  border-radius: 1rem;
  padding: 1.5rem;
  border: 1px solid rgba(75, 85, 99, 0.3);
  transition: all 0.2s;
}

.announcement-card:hover {
  border-color: rgba(0, 188, 212, 0.3);
}

.announcement-card.pinned {
  border-color: #f59e0b;
  background: rgba(245, 158, 11, 0.1);
}

.announcement-card.unread {
  border-left: 3px solid #00bcd4;
}

.pinned-badge {
  font-size: 0.75rem;
  color: #f59e0b;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

.announcement-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.announcement-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #f9fafb;
  margin: 0;
}

.announcement-date {
  font-size: 0.75rem;
  color: #6b7280;
  white-space: nowrap;
}

.announcement-content {
  color: #d1d5db;
  font-size: 0.9375rem;
  line-height: 1.7;
}

.announcement-content :deep(p) {
  margin: 0 0 1rem;
}

.announcement-content :deep(a) {
  color: #00bcd4;
  text-decoration: none;
}

.announcement-content :deep(a:hover) {
  text-decoration: underline;
}

.announcement-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(75, 85, 99, 0.3);
}

.author {
  font-size: 0.8125rem;
  color: #6b7280;
}

.mark-read-btn {
  background: none;
  border: 1px solid rgba(0, 188, 212, 0.3);
  color: #00bcd4;
  padding: 0.375rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.mark-read-btn:hover {
  background: rgba(0, 188, 212, 0.1);
}

@media (max-width: 640px) {
  .banner-icon {
    display: none;
  }

  .announcement-header {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>
