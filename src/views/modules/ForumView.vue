<template>
  <div class="forum-view">
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <!-- Topic View -->
    <div v-else-if="selectedTopic" class="topic-view">
      <div class="topic-header">
        <button @click="backToTopics" class="back-btn">
          <span>←</span> {{ selectedCategory?.name || 'BACK' }}
        </button>
      </div>

      <!-- Posts -->
      <div class="posts-list">
        <!-- Original Post -->
        <div class="post-card">
          <div class="post-avatar">
            <div class="avatar">
              {{ selectedTopic.user?.username?.[0]?.toUpperCase() || '?' }}
            </div>
            <div class="post-author">{{ selectedTopic.user?.username || 'Unknown' }}</div>
          </div>
          <div class="post-content">
            <div class="post-time">{{ formatTimeAgo(selectedTopic.created_at) }}</div>
            <div class="post-title">{{ selectedTopic.title }}</div>
            <div class="post-text">{{ selectedTopic.content }}</div>
          </div>
        </div>

        <!-- Replies -->
        <div v-for="reply in replies" :key="reply.id" class="post-card">
          <div class="post-avatar">
            <div class="avatar">
              {{ reply.user?.username?.[0]?.toUpperCase() || '?' }}
            </div>
            <div class="post-author">{{ reply.user?.username || 'Unknown' }}</div>
          </div>
          <div class="post-content">
            <div class="post-time">{{ formatTimeAgo(reply.created_at) }}</div>
            <div class="post-text">{{ reply.content }}</div>
          </div>
        </div>
      </div>

      <!-- Reply Form -->
      <div class="reply-section">
        <div class="reply-header">ADD REPLY</div>
        <div class="editor-toolbar">
          <button class="toolbar-btn" title="Bold"><strong>B</strong></button>
          <button class="toolbar-btn" title="Italic"><em>I</em></button>
          <button class="toolbar-btn" title="Strikethrough"><s>S</s></button>
          <span class="toolbar-divider"></span>
          <button class="toolbar-btn" title="Quote">""</button>
          <button class="toolbar-btn" title="Code">&lt;/&gt;</button>
          <button class="toolbar-btn" title="Image">🖼</button>
        </div>
        <textarea
          v-model="newReply"
          placeholder="Write your message..."
          class="reply-textarea"
          maxlength="10000"
        ></textarea>
        <div class="reply-footer">
          <span class="char-count">{{ newReply.length }}/10000</span>
          <button @click="postReply" :disabled="!newReply.trim()" class="post-btn">
            POST
          </button>
        </div>
      </div>
    </div>

    <!-- Category Topics View -->
    <div v-else-if="selectedCategory" class="category-view">
      <div class="category-header">
        <button @click="backToCategories" class="back-btn">
          <span>←</span> FORUMS
        </button>
        <h1 class="category-title">{{ selectedCategory.name?.toUpperCase() }}</h1>
        <button @click="showNewTopicModal = true" class="add-topic-btn">
          ADD TOPIC
        </button>
      </div>

      <div class="topics-table">
        <div class="table-header">
          <div class="col-topics">TOPICS</div>
          <div class="col-replies">REPLIES</div>
          <div class="col-author">AUTHOR</div>
          <div class="col-last">LAST POST</div>
        </div>

        <div v-if="topics.length === 0" class="empty-topics">
          <p>No topics yet. Be the first to start a discussion!</p>
        </div>

        <div
          v-for="topic in topics"
          :key="topic.id"
          class="topic-row"
          @click="viewTopic(topic.id)"
        >
          <div class="col-topics">
            <span class="topic-icon">💬</span>
            <span class="topic-name">{{ topic.title }}</span>
          </div>
          <div class="col-replies">{{ topic.replies || 0 }}</div>
          <div class="col-author">
            <div class="user-avatar">
              {{ topic.author?.[0]?.toUpperCase() || '?' }}
            </div>
            <span class="user-name">{{ topic.author || 'Unknown' }}</span>
          </div>
          <div class="col-last">{{ topic.updated_at || '-' }}</div>
        </div>
      </div>
    </div>

    <!-- Categories List View -->
    <div v-else class="categories-view">
      <h1 class="forum-title">FORUMS</h1>

      <div v-if="!categories || categories.length === 0" class="empty-categories">
        <p>No forum categories available yet.</p>
      </div>

      <div v-else class="categories-list">
        <div
          v-for="category in categories"
          :key="category.id"
          class="category-card"
          @click="viewCategory(category.id)"
        >
          <h2 class="category-name">{{ category.name?.toUpperCase() }}</h2>
          <p class="category-desc">{{ category.description }}</p>
        </div>
      </div>
    </div>
  </div>

  <!-- New Topic Modal -->
  <div v-if="showNewTopicModal" class="modal-overlay" @click="showNewTopicModal = false">
    <div class="modal-card" @click.stop>
      <h2>Create New Topic</h2>
      <input
        v-model="newTopicTitle"
        placeholder="Topic title"
        class="input-field"
      />
      <textarea
        v-model="newTopicContent"
        placeholder="What's on your mind..."
        class="textarea-field"
      ></textarea>
      <div class="modal-actions">
        <button @click="createTopic" :disabled="!newTopicTitle.trim() || !newTopicContent.trim()" class="btn-primary">
          Create Topic
        </button>
        <button @click="showNewTopicModal = false" class="btn-secondary">
          Cancel
        </button>
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

const formatTimeAgo = (dateString) => {
  if (!dateString) return 'Recently';
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  if (diffDays === 0) return 'Today';
  if (diffDays === 1) return '1 day ago';
  if (diffDays < 30) return `${diffDays} days ago`;
  if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;
  return `${Math.floor(diffDays / 365)} years ago`;
};

onMounted(() => {
  fetchCategories();
});
</script>

<style scoped>
.forum-view {
  min-height: 100vh;
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* Loading */
.loading-state {
  display: flex;
  justify-content: center;
  padding: 4rem;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-top-color: #00bcd4;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Categories View */
.forum-title {
  text-align: center;
  font-size: 2rem;
  font-weight: 400;
  letter-spacing: 2px;
  color: #94a3b8;
  margin-bottom: 3rem;
}

.categories-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-width: 1200px;
  margin: 0 auto;
}

.category-card {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1.5rem 2rem;
  cursor: pointer;
  transition: all 0.2s;
}

.category-card:hover {
  background: rgba(30, 41, 59, 0.7);
  border-color: rgba(255, 255, 255, 0.2);
}

.category-name {
  font-size: 1.1rem;
  font-weight: 600;
  letter-spacing: 1px;
  color: #e2e8f0;
  margin: 0 0 0.5rem 0;
}

.category-desc {
  color: #94a3b8;
  margin: 0;
  font-size: 0.95rem;
}

/* Category View (Topics List) */
.category-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
}

.back-btn {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #94a3b8;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.back-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.3);
}

.category-title {
  font-size: 1.75rem;
  font-weight: 400;
  letter-spacing: 2px;
  color: #e2e8f0;
  margin: 0;
}

.add-topic-btn {
  background: #4ade80;
  color: #000;
  border: none;
  padding: 0.6rem 1.5rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  letter-spacing: 0.5px;
  transition: background 0.2s;
}

.add-topic-btn:hover {
  background: #3bc96f;
}

/* Topics Table */
.topics-table {
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  overflow: hidden;
}

.table-header {
  display: grid;
  grid-template-columns: 2fr 120px 200px 200px;
  gap: 1rem;
  padding: 1rem 1.5rem;
  background: rgba(0, 0, 0, 0.3);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 1px;
  color: #94a3b8;
}

.table-header .col-topics,
.table-header .col-replies,
.table-header .col-author,
.table-header .col-last {
  display: block;
}

.topic-row {
  display: grid;
  grid-template-columns: 2fr 120px 200px 200px;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  cursor: pointer;
  transition: background 0.2s;
  align-items: center;
}

.topic-row:last-child {
  border-bottom: none;
}

.topic-row:hover {
  background: rgba(255, 255, 255, 0.03);
}

.col-topics {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.topic-icon {
  font-size: 1.2rem;
  opacity: 0.5;
}

.topic-name {
  color: #e2e8f0;
  font-size: 0.95rem;
}

.col-replies {
  color: #94a3b8;
  font-size: 0.95rem;
  text-align: center;
}

.col-author,
.col-last {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #00bcd4, #0097a7);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.85rem;
  font-weight: 600;
  flex-shrink: 0;
}

.user-name {
  color: #cbd5e1;
  font-size: 0.9rem;
}

/* Topic View (Posts) */
.topic-header {
  margin-bottom: 1.5rem;
}

/* Posts */
.posts-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.post-card {
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1rem;
  display: grid;
  grid-template-columns: 100px 1fr;
  gap: 1rem;
}

.post-avatar {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #00bcd4, #0097a7);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
  font-weight: 700;
}

.post-author {
  color: #cbd5e1;
  font-size: 0.9rem;
  font-weight: 500;
  text-align: center;
}

.post-content {
  position: relative;
}

.post-time {
  position: absolute;
  top: 0;
  right: 0;
  color: #64748b;
  font-size: 0.85rem;
}

.post-title {
  font-size: 1.15rem;
  font-weight: 600;
  color: #e2e8f0;
  margin-bottom: 0.75rem;
  padding-top: 0.25rem;
}

.post-text {
  color: #cbd5e1;
  line-height: 1.6;
  padding-top: 0.75rem;
  white-space: pre-wrap;
}

/* Reply Section */
.reply-section {
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1rem;
}

.reply-header {
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 1px;
  color: #94a3b8;
  margin-bottom: 0.75rem;
}

.editor-toolbar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 4px 4px 0 0;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-bottom: none;
}

.toolbar-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  padding: 0.25rem 0.5rem;
  cursor: pointer;
  font-size: 0.95rem;
  transition: color 0.2s;
}

.toolbar-btn:hover {
  color: #e2e8f0;
}

.toolbar-divider {
  width: 1px;
  height: 16px;
  background: rgba(255, 255, 255, 0.1);
  margin: 0 0.25rem;
}

.reply-textarea {
  width: 100%;
  min-height: 120px;
  padding: 0.75rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0 0 4px 4px;
  color: #e2e8f0;
  font-family: inherit;
  font-size: 0.95rem;
  resize: vertical;
  outline: none;
}

.reply-textarea::placeholder {
  color: #64748b;
}

.reply-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.75rem;
}

.char-count {
  color: #64748b;
  font-size: 0.85rem;
}

.post-btn {
  background: #4ade80;
  color: #000;
  border: none;
  padding: 0.6rem 2rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  letter-spacing: 0.5px;
  transition: background 0.2s;
}

.post-btn:hover:not(:disabled) {
  background: #3bc96f;
}

.post-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-card {
  background: rgba(15, 23, 42, 0.95);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  padding: 2rem;
  max-width: 600px;
  width: 90%;
}

.modal-card h2 {
  font-size: 1.5rem;
  color: #e2e8f0;
  margin: 0 0 1.5rem 0;
}

.input-field,
.textarea-field {
  width: 100%;
  padding: 0.75rem;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  color: #e2e8f0;
  font-family: inherit;
  font-size: 0.95rem;
  margin-bottom: 1rem;
  outline: none;
}

.input-field:focus,
.textarea-field:focus {
  border-color: #00bcd4;
}

.textarea-field {
  min-height: 200px;
  resize: vertical;
}

.input-field::placeholder,
.textarea-field::placeholder {
  color: #64748b;
}

.modal-actions {
  display: flex;
  gap: 1rem;
}

.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  flex: 1;
}

.btn-primary {
  background: #4ade80;
  color: #000;
}

.btn-primary:hover:not(:disabled) {
  background: #3bc96f;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #94a3b8;
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.05);
}

/* Empty States */
.empty-categories,
.empty-topics {
  text-align: center;
  padding: 3rem;
  color: #64748b;
}

/* Responsive */
@media (max-width: 968px) {
  .table-header,
  .topic-row {
    grid-template-columns: 2fr 80px 150px 150px;
    gap: 0.75rem;
  }
}

@media (max-width: 768px) {
  .forum-view {
    padding: 1rem;
  }

  .category-header,
  .topic-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .topic-title {
    text-align: left;
  }

  .table-header {
    display: none;
  }

  .topic-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .col-topics {
    grid-column: 1;
  }

  .col-replies,
  .col-author,
  .col-last {
    display: none;
  }

  .post-card {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .post-avatar {
    flex-direction: row;
    justify-content: flex-start;
  }
}
</style>

