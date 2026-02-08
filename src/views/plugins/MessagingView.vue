<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import api from '@/services/api';

const conversations = ref([]);
const selectedConversation = ref(null);
const messages = ref([]);
const player = ref(null);
const loading = ref(true);
const loadingMessages = ref(false);
const processing = ref(false);
const error = ref('');
const successMessage = ref('');

// New conversation
const showNewMessage = ref(false);
const searchUsers = ref('');
const searchResults = ref([]);
const selectedRecipient = ref(null);
const newMessageText = ref('');

// Reply
const replyText = ref('');

let pollingInterval = null;

const formatDate = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diff = now - date;

  // Less than 1 day
  if (diff < 86400000) {
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  }

  // Less than 7 days
  if (diff < 604800000) {
    return date.toLocaleDateString('en-US', { weekday: 'short', hour: '2-digit', minute: '2-digit' });
  }

  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const unreadCount = computed(() => {
  return conversations.value.filter(c => c.unread_count > 0).length;
});

const fetchConversations = async () => {
  try {
    loading.value = true;
    error.value = '';
    const response = await api.get('/messages');
    conversations.value = response.data.conversations || [];
    player.value = response.data.player || null;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load messages';
  } finally {
    loading.value = false;
  }
};

const fetchMessages = async (conversationId) => {
  try {
    loadingMessages.value = true;
    error.value = '';
    const response = await api.get(`/messages/${conversationId}`);
    messages.value = response.data.messages || [];

    // Mark as read
    if (selectedConversation.value) {
      selectedConversation.value.unread_count = 0;
    }

    // Scroll to bottom
    await nextTick();
    scrollToBottom();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load conversation';
  } finally {
    loadingMessages.value = false;
  }
};

const selectConversation = async (conversation) => {
  selectedConversation.value = conversation;
  showNewMessage.value = false;
  await fetchMessages(conversation.id);
};

const searchForUsers = async () => {
  if (searchUsers.value.length < 2) {
    searchResults.value = [];
    return;
  }

  try {
    const response = await api.get('/players/search', { params: { q: searchUsers.value } });
    searchResults.value = response.data.players || [];
  } catch (err) {
    console.error('Search failed:', err);
  }
};

const selectRecipient = (user) => {
  selectedRecipient.value = user;
  searchUsers.value = user.username;
  searchResults.value = [];
};

const sendNewMessage = async () => {
  if (processing.value || !selectedRecipient.value || !newMessageText.value.trim()) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post('/messages/send', {
      recipient_id: selectedRecipient.value.id,
      message: newMessageText.value
    });

    successMessage.value = response.data.message || 'Message sent!';

    // Reset form
    showNewMessage.value = false;
    selectedRecipient.value = null;
    searchUsers.value = '';
    newMessageText.value = '';

    // Refresh conversations and open the new one
    await fetchConversations();
    if (response.data.conversation_id) {
      const newConvo = conversations.value.find(c => c.id === response.data.conversation_id);
      if (newConvo) {
        await selectConversation(newConvo);
      }
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to send message';
  } finally {
    processing.value = false;
  }
};

const sendReply = async () => {
  if (processing.value || !selectedConversation.value || !replyText.value.trim()) return;

  try {
    processing.value = true;
    error.value = '';

    const response = await api.post(`/messages/${selectedConversation.value.id}/reply`, {
      message: replyText.value
    });

    // Add message to list
    messages.value.push(response.data.message);
    replyText.value = '';

    // Scroll to bottom
    await nextTick();
    scrollToBottom();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to send reply';
  } finally {
    processing.value = false;
  }
};

const deleteConversation = async (conversationId) => {
  if (processing.value || !confirm('Delete this conversation?')) return;

  try {
    processing.value = true;
    error.value = '';

    await api.delete(`/messages/${conversationId}`);

    conversations.value = conversations.value.filter(c => c.id !== conversationId);

    if (selectedConversation.value?.id === conversationId) {
      selectedConversation.value = null;
      messages.value = [];
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete conversation';
  } finally {
    processing.value = false;
  }
};

const scrollToBottom = () => {
  const container = document.querySelector('.messages-container');
  if (container) {
    container.scrollTop = container.scrollHeight;
  }
};

const startPolling = () => {
  pollingInterval = setInterval(async () => {
    await fetchConversations();
    if (selectedConversation.value) {
      const response = await api.get(`/messages/${selectedConversation.value.id}`);
      if (response.data.messages.length > messages.value.length) {
        messages.value = response.data.messages;
        await nextTick();
        scrollToBottom();
      }
    }
  }, 10000);
};

const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
};

onMounted(() => {
  fetchConversations();
  startPolling();
});

onUnmounted(() => {
  stopPolling();
});
</script>

<template>
  <div class="messaging-view">
    <div class="page-header">
      <h1>✉️ Messages</h1>
      <button @click="showNewMessage = true; selectedConversation = null" class="btn btn-primary">
        New Message
      </button>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="alert alert-error">
      {{ error }}
    </div>

    <!-- Success Message -->
    <div v-if="successMessage" class="alert alert-success">
      {{ successMessage }}
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading messages...</p>
    </div>

    <!-- Main Content -->
    <div v-else class="messaging-container">

      <!-- Conversations List -->
      <div class="conversations-panel">
        <div class="panel-header">
          <h2>Conversations</h2>
          <span v-if="unreadCount > 0" class="unread-badge">{{ unreadCount }}</span>
        </div>

        <div v-if="conversations.length === 0" class="empty-conversations">
          <p>No conversations yet</p>
          <button @click="showNewMessage = true" class="btn btn-primary btn-sm">
            Start a conversation
          </button>
        </div>

        <div v-else class="conversations-list">
          <div
            v-for="convo in conversations"
            :key="convo.id"
            :class="['conversation-item', {
              active: selectedConversation?.id === convo.id,
              unread: convo.unread_count > 0
            }]"
            @click="selectConversation(convo)"
          >
            <div class="conversation-avatar">
              {{ convo.participant_name?.charAt(0).toUpperCase() || '?' }}
            </div>
            <div class="conversation-info">
              <span class="participant-name">{{ convo.participant_name }}</span>
              <span class="last-message">{{ convo.last_message }}</span>
            </div>
            <div class="conversation-meta">
              <span class="timestamp">{{ formatDate(convo.last_message_at) }}</span>
              <span v-if="convo.unread_count > 0" class="unread-count">
                {{ convo.unread_count }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Message View -->
      <div class="messages-panel">

        <!-- New Message Form -->
        <div v-if="showNewMessage" class="new-message-form">
          <div class="form-header">
            <h2>New Message</h2>
            <button @click="showNewMessage = false" class="btn-close">×</button>
          </div>

          <div class="form-group">
            <label>To:</label>
            <input
              type="text"
              v-model="searchUsers"
              @input="searchForUsers"
              placeholder="Search for a player..."
              class="recipient-input"
            />
            <div v-if="searchResults.length > 0" class="search-results">
              <div
                v-for="user in searchResults"
                :key="user.id"
                class="search-result-item"
                @click="selectRecipient(user)"
              >
                <span class="user-name">{{ user.username }}</span>
                <span class="user-level">Level {{ user.level }}</span>
              </div>
            </div>
          </div>

          <div v-if="selectedRecipient" class="selected-recipient">
            <span>Sending to: <strong>{{ selectedRecipient.username }}</strong></span>
          </div>

          <div class="form-group">
            <textarea
              v-model="newMessageText"
              placeholder="Type your message..."
              rows="4"
            ></textarea>
          </div>

          <button
            @click="sendNewMessage"
            :disabled="processing || !selectedRecipient || !newMessageText.trim()"
            class="btn btn-primary btn-block"
          >
            Send Message
          </button>
        </div>

        <!-- Conversation View -->
        <div v-else-if="selectedConversation" class="conversation-view">
          <div class="conversation-header">
            <h2>{{ selectedConversation.participant_name }}</h2>
            <button
              @click="deleteConversation(selectedConversation.id)"
              :disabled="processing"
              class="btn btn-danger btn-sm"
            >
              Delete
            </button>
          </div>

          <div v-if="loadingMessages" class="loading-messages">
            <div class="spinner"></div>
          </div>

          <div v-else class="messages-container">
            <div
              v-for="msg in messages"
              :key="msg.id"
              :class="['message', { sent: msg.sender_id === player?.id }]"
            >
              <div class="message-content">
                <p>{{ msg.content }}</p>
                <span class="message-time">{{ formatDate(msg.created_at) }}</span>
              </div>
            </div>
          </div>

          <div class="reply-form">
            <textarea
              v-model="replyText"
              placeholder="Type a reply..."
              rows="2"
              @keydown.enter.ctrl="sendReply"
            ></textarea>
            <button
              @click="sendReply"
              :disabled="processing || !replyText.trim()"
              class="btn btn-primary"
            >
              Send
            </button>
          </div>
        </div>

        <!-- No Conversation Selected -->
        <div v-else class="no-conversation">
          <div class="placeholder-content">
            <span class="placeholder-icon">💬</span>
            <p>Select a conversation or start a new one</p>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.messaging-view {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1rem;
  height: calc(100vh - 200px);
  min-height: 500px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.page-header h1 {
  font-size: 1.5rem;
  color: var(--color-heading);
  margin: 0;
}

.loading-state {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.alert {
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.alert-error {
  background-color: rgba(239, 68, 68, 0.1);
  border: 1px solid var(--color-danger);
  color: var(--color-danger);
}

.alert-success {
  background-color: rgba(34, 197, 94, 0.1);
  border: 1px solid var(--color-success);
  color: var(--color-success);
}

.messaging-container {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 1rem;
  height: calc(100% - 60px);
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
  overflow: hidden;
}

.conversations-panel {
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid var(--color-border);
}

.panel-header h2 {
  font-size: 1rem;
  margin: 0;
  color: var(--color-heading);
}

.unread-badge {
  background: var(--color-primary);
  color: white;
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 1rem;
}

.empty-conversations {
  text-align: center;
  padding: 2rem;
  color: var(--color-text-muted);
}

.conversations-list {
  flex: 1;
  overflow-y: auto;
}

.conversation-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid var(--color-border);
  transition: background 0.2s;
}

.conversation-item:hover {
  background: var(--color-background);
}

.conversation-item.active {
  background: var(--color-background);
  border-left: 3px solid var(--color-primary);
}

.conversation-item.unread {
  background: rgba(59, 130, 246, 0.05);
}

.conversation-avatar {
  width: 40px;
  height: 40px;
  background: var(--color-primary);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  flex-shrink: 0;
}

.conversation-info {
  flex: 1;
  overflow: hidden;
}

.participant-name {
  display: block;
  font-weight: 600;
  color: var(--color-heading);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.last-message {
  display: block;
  font-size: 0.875rem;
  color: var(--color-text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.conversation-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.timestamp {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.unread-count {
  background: var(--color-primary);
  color: white;
  font-size: 0.625rem;
  padding: 0.125rem 0.375rem;
  border-radius: 1rem;
  min-width: 1.25rem;
  text-align: center;
}

.messages-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.new-message-form {
  padding: 1.5rem;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.form-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: var(--color-heading);
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: var(--color-text-muted);
  cursor: pointer;
}

.form-group {
  margin-bottom: 1rem;
  position: relative;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--color-text);
}

.recipient-input,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  background: var(--color-background);
  color: var(--color-text);
  font-size: 1rem;
}

.recipient-input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: var(--color-primary);
}

.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  max-height: 200px;
  overflow-y: auto;
  z-index: 10;
}

.search-result-item {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem;
  cursor: pointer;
  transition: background 0.2s;
}

.search-result-item:hover {
  background: var(--color-background-soft);
}

.user-name {
  font-weight: 500;
}

.user-level {
  font-size: 0.875rem;
  color: var(--color-text-muted);
}

.selected-recipient {
  padding: 0.5rem;
  background: rgba(59, 130, 246, 0.1);
  border-radius: 0.25rem;
  margin-bottom: 1rem;
}

.conversation-view {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.conversation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid var(--color-border);
}

.conversation-header h2 {
  margin: 0;
  font-size: 1.125rem;
  color: var(--color-heading);
}

.loading-messages {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.message {
  max-width: 70%;
}

.message.sent {
  align-self: flex-end;
}

.message-content {
  padding: 0.75rem 1rem;
  border-radius: 1rem;
  background: var(--color-background);
}

.message.sent .message-content {
  background: var(--color-primary);
  color: white;
}

.message-content p {
  margin: 0 0 0.25rem;
  word-wrap: break-word;
}

.message-time {
  font-size: 0.625rem;
  opacity: 0.7;
}

.reply-form {
  display: flex;
  gap: 0.5rem;
  padding: 1rem;
  border-top: 1px solid var(--color-border);
}

.reply-form textarea {
  flex: 1;
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  background: var(--color-background);
  color: var(--color-text);
  font-size: 0.875rem;
  resize: none;
}

.reply-form textarea:focus {
  outline: none;
  border-color: var(--color-primary);
}

.no-conversation {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-content {
  text-align: center;
  color: var(--color-text-muted);
}

.placeholder-icon {
  font-size: 4rem;
  display: block;
  margin-bottom: 1rem;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
}

.btn-block {
  width: 100%;
}

.btn-primary {
  background-color: var(--color-primary);
  color: white;
}

.btn-danger {
  background-color: var(--color-danger);
  color: white;
}

@media (max-width: 768px) {
  .messaging-container {
    grid-template-columns: 1fr;
  }

  .conversations-panel {
    display: none;
  }

  .messaging-container.show-conversations .conversations-panel {
    display: flex;
  }

  .messaging-container.show-conversations .messages-panel {
    display: none;
  }
}
</style>
