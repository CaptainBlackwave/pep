<template>
  <div class="forum-container">
    <div class="page-header">
      <h1>💬 Forum</h1>
      <div class="header-actions">
        <button v-if="selectedTopic" @click="backToTopics" class="back-btn">← Back to Topics</button>
        <button v-else-if="selectedCategory" @click="backToCategories" class="back-btn">← Back to Categories</button>
        <router-link v-else to="/dashboard" class="back-link">← Back to Dashboard</router-link>
      </div>
    </div>

    <div class="content-wrapper">
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading forum...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button @click="fetchCategories" class="retry-btn">Retry</button>
      </div>

      <!-- Topic View -->
      <div v-else-if="selectedTopic" class="topic-view">
        <div class="topic-header-card">
          <h2>{{ selectedTopic.title }}</h2>
          <div class="topic-meta">
            <span class="meta-item">
              <span class="meta-icon">👤</span>
              Posted by {{ selectedTopic.user?.username || 'Unknown' }}
            </span>
            <span class="meta-item">
              <span class="meta-icon">📅</span>
              {{ formatDate(selectedTopic.created_at) }}
            </span>
            <span class="meta-item">
              <span class="meta-icon">💬</span>
              {{ formatNumber(replies.length) }} replies
            </span>
          </div>
          <div class="topic-content">
            {{ selectedTopic.content }}
          </div>
        </div>

        <!-- Replies -->
        <div class="replies-section">
          <h3>Replies</h3>
          <div v-if="replies.length === 0" class="empty-state">
            <p>No replies yet. Be the first to reply!</p>
          </div>
          <div v-else class="replies-list">
            <div v-for="reply in replies" :key="reply.id" class="reply-card">
              <div class="reply-avatar">
                {{ reply.user?.username?.[0]?.toUpperCase() || '?' }}
              </div>
              <div class="reply-content">
                <div class="reply-header">
                  <span class="reply-author">{{ reply.user?.username || 'Unknown' }}</span>
                  <span class="reply-date">{{ formatDate(reply.created_at) }}</span>
                </div>
                <div class="reply-text">{{ reply.content }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reply Form -->
        <div class="reply-form">
          <h3>Post a Reply</h3>
          <textarea
            v-model="newReply"
            placeholder="Write your reply..."
            class="reply-textarea"
          ></textarea>
          <button @click="postReply" class="btn-post-reply">Post Reply</button>
        </div>
      </div>

      <!-- Category Topics View -->
      <div v-else-if="selectedCategory" class="category-view">
        <div class="category-header-card">
          <div class="category-title-section">
            <h2>{{ getCategoryIcon(selectedCategory.name) }} {{ selectedCategory.name }}</h2>
            <p>{{ selectedCategory.description }}</p>
          </div>
          <button @click="showNewTopicModal = true" class="btn-new-topic">+ New Topic</button>
        </div>

        <div v-if="topics.length === 0" class="empty-state">
          <div class="empty-icon">📝</div>
          <p>No topics in this category yet. Be the first to start a discussion!</p>
        </div>
        
        <div v-else class="topics-list">
          <div
            v-for="topic in topics"
            :key="topic.id"
            class="topic-card"
            @click="viewTopic(topic.id)"
          >
            <div class="topic-info">
              <h3 class="topic-title">{{ topic.title }}</h3>
              <div class="topic-meta">
                <span class="meta-item">
                  <span class="meta-icon">👤</span>
                  {{ topic.user?.username || 'Unknown' }}
                </span>
                <span class="meta-item">
                  <span class="meta-icon">📅</span>
                  {{ formatDate(topic.created_at) }}
                </span>
                <span class="meta-item">
                  <span class="meta-icon">💬</span>
                  {{ formatNumber(topic.reply_count || 0) }} replies
                </span>
              </div>
            </div>
            <div class="arrow-icon">→</div>
          </div>
        </div>
      </div>

      <!-- Categories List View -->
      <div v-else>
        <div class="forum-card">
          <div class="card-header">
            <h2>Forum Categories</h2>
            <p class="subtitle">Join the conversation with the community</p>
          </div>

          <div class="categories-list">
            <div
              v-for="category in categories"
              :key="category.id"
              class="category-item"
              @click="viewCategory(category.id)"
            >
              <div class="category-icon">
                {{ getCategoryIcon(category.name) }}
              </div>
              <div class="category-content">
                <h3 class="category-name">{{ category.name }}</h3>
                <p class="category-description">{{ category.description }}</p>
                <div class="category-meta">
                  <span class="meta-item">
                    <span class="meta-icon">📝</span>
                    {{ formatNumber(category.topic_count) }} topics
                  </span>
                  <span class="meta-item">
                    <span class="meta-icon">💬</span>
                    {{ formatNumber(category.post_count) }} posts
                  </span>
                </div>
              </div>
              <div class="category-stats">
                <div class="stat-number">{{ formatNumber(category.topic_count) }}</div>
                <div class="stat-label">Topics</div>
              </div>
              <div class="arrow-icon">→</div>
            </div>

            <div v-if="!categories || categories.length === 0" class="empty-state">
              <div class="empty-icon">💭</div>
              <p>No forum categories available yet.</p>
            </div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
              <div class="stat-value">{{ formatNumber(totalTopics) }}</div>
              <div class="stat-label">Total Topics</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-info">
              <div class="stat-value">{{ formatNumber(totalPosts) }}</div>
              <div class="stat-label">Total Posts</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
              <div class="stat-value">{{ categories.length }}</div>
              <div class="stat-label">Categories</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- New Topic Modal -->
  <div v-if="showNewTopicModal" class="modal-overlay" @click="showNewTopicModal = false">
    <div class="modal-content" @click.stop>
      <h3>Create New Topic</h3>
      <input v-model="newTopicTitle" placeholder="Topic title" class="input-field" />
      <textarea v-model="newTopicContent" placeholder="Topic content..." class="textarea-field"></textarea>
      <div class="modal-actions">
        <button @click="createTopic" class="btn-primary">Create Topic</button>
        <button @click="showNewTopicModal = false" class="btn-secondary">Cancel</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();
const loading = ref(true);
const error = ref(null);
const categories = ref([]);
const selectedCategory = ref(null);
const topics = ref([]);
const selectedTopic = ref(null);
const replies = ref([]);
const newTopicTitle = ref('');
const newTopicContent = ref('');
const showNewTopicModal = ref(false);
const newReply = ref('');

const totalTopics = computed(() => 
  categories.value.reduce((sum, cat) => sum + (cat.topic_count || 0), 0)
);

const totalPosts = computed(() => 
  categories.value.reduce((sum, cat) => sum + (cat.post_count || 0), 0)
);

const fetchCategories = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    const response = await api.get('/forum');
    categories.value = response.data.categories || response.data || [];
  } catch (err) {
    console.error('Error fetching forum categories:', err);
    error.value = err.response?.data?.message || 'Failed to load forum. Please try again.';
  } finally {
    loading.value = false;
  }
};

const viewCategory = async (categoryId) => {
  loading.value = true;
  try {
    const response = await api.get(`/forum/category/${categoryId}`);
    selectedCategory.value = categories.value.find(c => c.id === categoryId);
    topics.value = response.data.topics || response.data || [];
    selectedTopic.value = null;
  } catch (err) {
    console.error('Error fetching topics:', err);
    error.value = 'Failed to load topics';
  } finally {
    loading.value = false;
  }
};

const viewTopic = async (topicId) => {
  loading.value = true;
  try {
    const response = await api.get(`/forum/topic/${topicId}`);
    selectedTopic.value = response.data.topic || response.data;
    replies.value = response.data.replies || response.data.posts || [];
  } catch (err) {
    console.error('Error fetching topic:', err);
    error.value = 'Failed to load topic';
  } finally {
    loading.value = false;
  }
};

const backToCategories = () => {
  selectedCategory.value = null;
  selectedTopic.value = null;
  topics.value = [];
  replies.value = [];
};

const backToTopics = () => {
  selectedTopic.value = null;
  replies.value = [];
};

const createTopic = async () => {
  if (!newTopicTitle.value.trim() || !newTopicContent.value.trim()) return;
  
  try {
    await api.post(`/forum/category/${selectedCategory.value.id}/topic`, {
      title: newTopicTitle.value,
      content: newTopicContent.value
    });
    showNewTopicModal.value = false;
    newTopicTitle.value = '';
    newTopicContent.value = '';
    await viewCategory(selectedCategory.value.id);
  } catch (err) {
    console.error('Error creating topic:', err);
    alert('Failed to create topic: ' + (err.response?.data?.message || err.message));
  }
};

const postReply = async () => {
  if (!newReply.value.trim()) return;
  
  try {
    await api.post(`/forum/topic/${selectedTopic.value.id}/reply`, {
      content: newReply.value
    });
    newReply.value = '';
    await viewTopic(selectedTopic.value.id);
  } catch (err) {
    console.error('Error posting reply:', err);
    alert('Failed to post reply: ' + (err.response?.data?.message || err.message));
  }
};

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US').format(num || 0);
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getCategoryIcon = (name) => {
  const icons = {
    'General Discussion': '💭',
    'Game Updates': '📢',
    'Help & Support': '🆘',
    'Suggestions': '💡',
    'Off Topic': '🎲',
    'Bug Reports': '🐛',
    'Gangs': '🎭',
    'Trading': '💰',
  };
  
  for (const [key, icon] of Object.entries(icons)) {
    if (name.toLowerCase().includes(key.toLowerCase())) {
      return icon;
    }
  }
  
  return '📌';
};

onMounted(() => {
  fetchCategories();
});
</script>

<style scoped>
.forum-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 50%, #93c5fd 100%);
  padding: 2rem 1rem;
}

.page-header {
  max-width: 1200px;
  margin: 0 auto 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-header h1 {
  font-size: 2.5rem;
  font-weight: 800;
  color: #1e3a8a;
  margin: 0;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
}

.back-link {
  color: #1d4ed8;
  text-decoration: none;
  font-weight: 600;
  font-size: 1rem;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.back-link:hover {
  background: #eff6ff;
  transform: translateX(-4px);
}

.content-wrapper {
  max-width: 1200px;
  margin: 0 auto;
}

.loading-state,
.error-state {
  background: white;
  border-radius: 1rem;
  padding: 3rem;
  text-align: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #dbeafe;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-state p {
  color: #991b1b;
  font-size: 1.1rem;
  margin-bottom: 1rem;
}

.retry-btn {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.retry-btn:hover {
  background: #2563eb;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.forum-card {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 2rem;
}

.card-header {
  padding: 2rem;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border-bottom: 2px solid #bfdbfe;
}

.card-header h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #1e3a8a;
  margin: 0 0 0.5rem;
}

.subtitle {
  color: #475569;
  font-size: 1rem;
  margin: 0;
}

.categories-list {
  padding: 1rem;
}

.category-item {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  margin-bottom: 1rem;
  border: 2px solid #e0f2fe;
  border-radius: 0.75rem;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
}

.category-item:hover {
  border-color: #3b82f6;
  background: #f0f9ff;
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.category-icon {
  font-size: 2.5rem;
  flex-shrink: 0;
}

.category-content {
  flex: 1;
}

.category-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e3a8a;
  margin: 0 0 0.5rem;
}

.category-description {
  color: #64748b;
  font-size: 0.95rem;
  margin: 0 0 0.75rem;
  line-height: 1.5;
}

.category-meta {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: #475569;
  font-size: 0.875rem;
  font-weight: 500;
}

.meta-icon {
  font-size: 1rem;
}

.category-stats {
  text-align: center;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  border-radius: 0.5rem;
  flex-shrink: 0;
}

.stat-number {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e40af;
}

.stat-label {
  font-size: 0.75rem;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.arrow-icon {
  font-size: 1.5rem;
  color: #3b82f6;
  flex-shrink: 0;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-state p {
  color: #94a3b8;
  font-size: 1.1rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.stat-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.stat-card .stat-icon {
  font-size: 2.5rem;
  flex-shrink: 0;
}

.stat-info {
  flex: 1;
}

.stat-card .stat-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: #1e3a8a;
  margin-bottom: 0.25rem;
}

.stat-card .stat-label {
  color: #64748b;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

@media (max-width: 768px) {
  .page-header h1 {
    font-size: 2rem;
  }

  .category-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .category-icon {
    font-size: 2rem;
  }

  .category-stats {
    align-self: flex-start;
  }

  .arrow-icon {
    display: none;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }
}

/* Topic and Reply Views */
.topic-view, .category-view {
  max-width: 1200px;
  margin: 0 auto;
}

.topic-header-card, .category-header-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.topic-header-card h2 {
  color: #1e3a8a;
  margin: 0 0 1rem 0;
  font-size: 2rem;
}

.topic-meta {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.topic-content {
  color: #1f2937;
  line-height: 1.6;
  white-space: pre-wrap;
}

.category-title-section {
  flex: 1;
}

.category-title-section h2 {
  color: #1e3a8a;
  margin: 0 0 0.5rem 0;
}

.category-title-section p {
  color: #6b7280;
  margin: 0;
}

.btn-new-topic {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-new-topic:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.topics-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.topic-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.topic-card:hover {
  transform: translateX(5px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.topic-info {
  flex: 1;
}

.topic-title {
  color: #1e3a8a;
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
}

.replies-section {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.replies-section h3 {
  color: #1e3a8a;
  margin: 0 0 1.5rem 0;
}

.replies-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.reply-card {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  border-left: 3px solid #3b82f6;
}

.reply-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  flex-shrink: 0;
}

.reply-content {
  flex: 1;
}

.reply-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.reply-author {
  color: #1e3a8a;
  font-weight: 600;
}

.reply-date {
  color: #6b7280;
  font-size: 0.875rem;
}

.reply-text {
  color: #1f2937;
  line-height: 1.6;
  white-space: pre-wrap;
}

.reply-form {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.reply-form h3 {
  color: #1e3a8a;
  margin: 0 0 1rem 0;
}

.reply-textarea {
  width: 100%;
  min-height: 120px;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-family: inherit;
  font-size: 1rem;
  resize: vertical;
  margin-bottom: 1rem;
}

.reply-textarea:focus {
  outline: none;
  border-color: #3b82f6;
}

.btn-post-reply {
  padding: 0.75rem 2rem;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-post-reply:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.back-btn {
  padding: 0.5rem 1rem;
  background: white;
  color: #1d4ed8;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.back-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 1rem;
  width: 600px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content h3 {
  color: #1e3a8a;
  margin: 0 0 1.5rem 0;
}

.input-field, .textarea-field {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-family: inherit;
  font-size: 1rem;
  margin-bottom: 1rem;
}

.textarea-field {
  min-height: 150px;
  resize: vertical;
}

.input-field:focus, .textarea-field:focus {
  outline: none;
  border-color: #3b82f6;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn-primary, .btn-secondary {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.btn-secondary {
  background: #e5e7eb;
  color: #1f2937;
}

.btn-primary:hover, .btn-secondary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}
</style>

