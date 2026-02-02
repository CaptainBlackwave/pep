<template>
  <div class="chat-container">
    <div class="chat-sidebar">
      <div class="sidebar-header">
        <h3>Messages</h3>
        <button @click="showChannelModal = true" class="btn-new">+</button>
      </div>
      
      <div class="tab-selector">
        <button 
          :class="['tab-btn', {active: activeTab === 'channels'}]"
          @click="activeTab = 'channels'"
        >
          Channels
        </button>
        <button 
          :class="['tab-btn', {active: activeTab === 'direct'}]"
          @click="activeTab = 'direct'"
        >
          Direct
          <span v-if="unreadCount > 0" class="unread-badge">{{ unreadCount }}</span>
        </button>
      </div>

      <!-- Channel List -->
      <div v-if="activeTab === 'channels'" class="channel-list">
        <div
          v-for="channel in channels"
          :key="channel.id"
          :class="['channel-item', {active: selectedChannel?.id === channel.id}]"
          @click="selectChannel(channel)"
        >
          <span class="channel-icon">#</span>
          <span class="channel-name">{{ channel.name }}</span>
          <span v-if="channel.is_private" class="private-badge">🔒</span>
        </div>
      </div>

      <!-- Direct Message List -->
      <div v-if="activeTab === 'direct'" class="dm-list">
        <div
          v-for="dm in directMessages"
          :key="dm.user.id"
          :class="['dm-item', {active: selectedDM?.user.id === dm.user.id}]"
          @click="selectDM(dm.user)"
        >
          <div class="dm-avatar">{{ dm.user.username[0].toUpperCase() }}</div>
          <div class="dm-info">
            <div class="dm-name">{{ dm.user.username }}</div>
            <div class="dm-preview">{{ dm.last_message?.message?.substring(0, 30) }}...</div>
          </div>
          <span v-if="dm.unread_count > 0" class="unread-count">{{ dm.unread_count }}</span>
        </div>
      </div>
    </div>

    <!-- Chat Area -->
    <div class="chat-main">
      <div v-if="!selectedChannel && !selectedDM" class="empty-state">
        <p>Select a channel or direct message to start chatting</p>
      </div>

      <div v-else class="chat-content">
        <div class="chat-header">
          <h3 v-if="selectedChannel"># {{ selectedChannel.name }}</h3>
          <h3 v-else-if="selectedDM">{{ selectedDM.username }}</h3>
          <p v-if="selectedChannel" class="channel-description">{{ selectedChannel.description }}</p>
        </div>

        <div class="messages-container" ref="messagesContainer">
          <div v-for="message in messages" :key="message.id" class="message">
            <div class="message-avatar">{{ message.user.username[0].toUpperCase() }}</div>
            <div class="message-content">
              <div class="message-header">
                <span class="message-author">{{ message.user.username }}</span>
                <span class="message-time">{{ formatTime(message.created_at) }}</span>
                <span v-if="message.is_edited" class="edited-badge">(edited)</span>
              </div>
              
              <!-- Edit Mode -->
              <div v-if="editingMessageId === message.id" class="message-edit">
                <input
                  v-model="editMessageText"
                  @keyup.enter="saveEdit(message.id)"
                  @keyup.esc="cancelEdit"
                  class="edit-input"
                  ref="editInput"
                />
                <div class="edit-actions">
                  <button @click="saveEdit(message.id)" class="btn-save-edit">Save</button>
                  <button @click="cancelEdit" class="btn-cancel-edit">Cancel</button>
                </div>
              </div>
              
              <!-- Normal Message Display -->
              <div v-else>
                <div class="message-text">{{ message.message }}</div>
                
                <!-- Message Actions (only show for own messages) -->
                <div v-if="message.user.id === currentUserId" class="message-actions">
                  <button @click="startEdit(message)" class="action-btn" title="Edit">✏️</button>
                  <button @click="deleteMessage(message.id)" class="action-btn" title="Delete">🗑️</button>
                </div>
              </div>
              
              <!-- Reactions -->
              <div v-if="message.reactions && message.reactions.length > 0" class="reactions">
                <button
                  v-for="(reaction, index) in groupedReactions(message.reactions)"
                  :key="index"
                  @click="toggleReaction(message.id, reaction.emoji)"
                  :class="['reaction-badge', {active: reaction.userReacted}]"
                >
                  {{ reaction.emoji }} {{ reaction.count }}
                </button>
              </div>
              
              <!-- Add Reaction Button -->
              <div class="reaction-add">
                <button @click="showEmojiPicker(message.id)" class="btn-add-reaction">
                  😀 Add Reaction
                </button>
                <div v-if="emojiPickerMessageId === message.id" class="emoji-picker">
                  <button
                    v-for="emoji in quickEmojis"
                    :key="emoji"
                    @click="addReaction(message.id, emoji)"
                    class="emoji-option"
                  >
                    {{ emoji }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="message-input-container">
          <button @click="showMessageEmojiPicker = !showMessageEmojiPicker" class="btn-emoji">😀</button>
          <div v-if="showMessageEmojiPicker" class="message-emoji-picker">
            <button
              v-for="emoji in quickEmojis"
              :key="emoji"
              @click="insertEmoji(emoji)"
              class="emoji-option"
            >
              {{ emoji }}
            </button>
          </div>
          <input
            v-model="newMessage"
            @keyup.enter="sendMessage"
            type="text"
            :placeholder="selectedChannel ? `Message #${selectedChannel.name}` : `Message ${selectedDM?.username}`"
            class="message-input"
            ref="messageInput"
          />
          <button @click="sendMessage" class="btn-send">Send</button>
        </div>
      </div>
    </div>
  </div>

  <!-- New Channel Modal -->
  <div v-if="showChannelModal" class="modal-overlay" @click="showChannelModal = false">
    <div class="modal-content" @click.stop>
      <h3>Create Channel</h3>
      <input v-model="newChannelName" placeholder="Channel name" class="input-field" />
      <textarea v-model="newChannelDescription" placeholder="Description" class="textarea-field"></textarea>
      <label class="checkbox-label">
        <input type="checkbox" v-model="newChannelPrivate" />
        Private channel
      </label>
      <div class="modal-actions">
        <button @click="createChannel" class="btn-primary">Create</button>
        <button @click="showChannelModal = false" class="btn-secondary">Cancel</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed, nextTick } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const currentUserId = computed(() => authStore.user?.id)

const activeTab = ref('channels')
const channels = ref([])
const directMessages = ref([])
const selectedChannel = ref(null)
const selectedDM = ref(null)
const messages = ref([])
const newMessage = ref('')
const unreadCount = ref(0)
const messagesContainer = ref(null)
const messageInput = ref(null)

const showChannelModal = ref(false)
const newChannelName = ref('')
const newChannelDescription = ref('')
const newChannelPrivate = ref(false)

// Emoji and editing features
const emojiPickerMessageId = ref(null)
const showMessageEmojiPicker = ref(false)
const editingMessageId = ref(null)
const editMessageText = ref('')
const editInput = ref(null)

const quickEmojis = ['👍', '❤️', '😂', '😮', '😢', '😡', '🎉', '🔥', '✅', '❌']

onMounted(() => {
  loadChannels()
  loadDirectMessages()
  loadUnreadCount()
})

async function loadChannels() {
  try {
    const response = await api.get('/channels')
    channels.value = response.data
  } catch (error) {
    console.error('Failed to load channels:', error)
    channels.value = []
  }
}

async function loadDirectMessages() {
  try {
    const response = await api.get('/direct-messages')
    directMessages.value = response.data
  } catch (error) {
    console.error('Failed to load direct messages:', error)
    directMessages.value = []
  }
}

async function loadUnreadCount() {
  try {
    const response = await api.get('/chat/unread-count')
    unreadCount.value = response.data.unread_count || response.data.count || 0
  } catch (error) {
    console.error('Failed to load unread count:', error)
    unreadCount.value = 0
  }
}

async function selectChannel(channel) {
  selectedChannel.value = channel
  selectedDM.value = null
  await loadChannelMessages(channel.id)
}

async function selectDM(user) {
  selectedDM.value = user
  selectedChannel.value = null
  await loadDMMessages(user.id)
  await markAsRead(user.id)
}

async function loadChannelMessages(channelId) {
  try {
    const response = await api.get(`/channels/${channelId}/messages`)
    messages.value = response.data.data || response.data
    scrollToBottom()
  } catch (error) {
    console.error('Failed to load messages:', error)
    messages.value = []
  }
}

async function loadDMMessages(userId) {
  try {
    const response = await api.get(`/direct-messages/${userId}`)
    messages.value = response.data.data || response.data
    scrollToBottom()
  } catch (error) {
    console.error('Failed to load messages:', error)
    messages.value = []
  }
}

async function sendMessage() {
  if (!newMessage.value.trim()) return

  try {
    if (selectedChannel.value) {
      await api.post(`/channels/${selectedChannel.value.id}/messages`, {
        message: newMessage.value
      })
      await loadChannelMessages(selectedChannel.value.id)
    } else if (selectedDM.value) {
      await api.post('/direct-messages', {
        to_user_id: selectedDM.value.id,
        message: newMessage.value
      })
      await loadDMMessages(selectedDM.value.id)
    }
    newMessage.value = ''
  } catch (error) {
    console.error('Failed to send message:', error)
    alert('Failed to send message: ' + (error.response?.data?.message || error.message))
  }
}

async function createChannel() {
  try {
    await api.post('/channels', {
      name: newChannelName.value,
      description: newChannelDescription.value,
      is_private: newChannelPrivate.value
    })
    showChannelModal.value = false
    newChannelName.value = ''
    newChannelDescription.value = ''
    newChannelPrivate.value = false
    await loadChannels()
  } catch (error) {
    console.error('Failed to create channel:', error)
    alert('Failed to create channel. Please try again.')
  }
}

async function markAsRead(userId) {
  try {
    await api.patch(`/direct-messages/${userId}/read`)
    await loadUnreadCount()
  } catch (error) {
    console.error('Failed to mark as read:', error)
  }
}

function scrollToBottom() {
  setTimeout(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  }, 100)
}

// Emoji functions
function showEmojiPicker(messageId) {
  emojiPickerMessageId.value = emojiPickerMessageId.value === messageId ? null : messageId
}

function insertEmoji(emoji) {
  newMessage.value += emoji
  showMessageEmojiPicker.value = false
  messageInput.value?.focus()
}

async function addReaction(messageId, emoji) {
  try {
    await api.post(`/messages/${messageId}/reactions`, { emoji })
    emojiPickerMessageId.value = null
    // Reload messages to get updated reactions
    if (selectedChannel.value) {
      await loadChannelMessages(selectedChannel.value.id)
    } else if (selectedDM.value) {
      await loadDMMessages(selectedDM.value.id)
    }
  } catch (error) {
    console.error('Failed to add reaction:', error)
  }
}

async function toggleReaction(messageId, emoji) {
  try {
    await api.post(`/messages/${messageId}/reactions`, { emoji })
    // Reload messages to get updated reactions
    if (selectedChannel.value) {
      await loadChannelMessages(selectedChannel.value.id)
    } else if (selectedDM.value) {
      await loadDMMessages(selectedDM.value.id)
    }
  } catch (error) {
    console.error('Failed to toggle reaction:', error)
  }
}

function groupedReactions(reactions) {
  if (!reactions) return []
  
  const grouped = {}
  reactions.forEach(reaction => {
    if (!grouped[reaction.emoji]) {
      grouped[reaction.emoji] = {
        emoji: reaction.emoji,
        count: 0,
        userReacted: false
      }
    }
    grouped[reaction.emoji].count++
    if (reaction.user_id === currentUserId.value) {
      grouped[reaction.emoji].userReacted = true
    }
  })
  
  return Object.values(grouped)
}

// Edit message functions
function startEdit(message) {
  editingMessageId.value = message.id
  editMessageText.value = message.message
  nextTick(() => {
    editInput.value?.[0]?.focus()
  })
}

function cancelEdit() {
  editingMessageId.value = null
  editMessageText.value = ''
}

async function saveEdit(messageId) {
  if (!editMessageText.value.trim()) return
  
  try {
    await api.patch(`/messages/${messageId}`, {
      message: editMessageText.value
    })
    editingMessageId.value = null
    editMessageText.value = ''
    // Reload messages
    if (selectedChannel.value) {
      await loadChannelMessages(selectedChannel.value.id)
    } else if (selectedDM.value) {
      await loadDMMessages(selectedDM.value.id)
    }
  } catch (error) {
    console.error('Failed to update message:', error)
    alert('Failed to update message: ' + (error.response?.data?.message || error.message))
  }
}

async function deleteMessage(messageId) {
  if (!confirm('Are you sure you want to delete this message?')) return
  
  try {
    await api.delete(`/messages/${messageId}`)
    // Reload messages
    if (selectedChannel.value) {
      await loadChannelMessages(selectedChannel.value.id)
    } else if (selectedDM.value) {
      await loadDMMessages(selectedDM.value.id)
    }
  } catch (error) {
    console.error('Failed to delete message:', error)
    alert('Failed to delete message: ' + (error.response?.data?.message || error.message))
  }
}

function formatTime(timestamp) {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now - date
  
  if (diff < 60000) return 'just now'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`
  return date.toLocaleDateString()
}
</script>

<style scoped>
.chat-container {
  display: flex;
  height: calc(100vh - 120px);
  background: #1a1a2e;
  border-radius: 8px;
  overflow: hidden;
}

.chat-sidebar {
  width: 300px;
  background: #16213e;
  border-right: 1px solid #0f3460;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #0f3460;
}

.sidebar-header h3 {
  margin: 0;
  color: #e94560;
}

.btn-new {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #e94560;
  color: white;
  border: none;
  cursor: pointer;
  font-size: 20px;
}

.tab-selector {
  display: flex;
  padding: 10px;
  gap: 5px;
}

.tab-btn {
  flex: 1;
  padding: 10px;
  background: transparent;
  color: #8b8b8b;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  position: relative;
}

.tab-btn.active {
  background: #0f3460;
  color: #e94560;
}

.unread-badge {
  position: absolute;
  top: 5px;
  right: 5px;
  background: #e94560;
  color: white;
  border-radius: 10px;
  padding: 2px 6px;
  font-size: 11px;
}

.channel-list, .dm-list {
  flex: 1;
  overflow-y: auto;
}

.channel-item, .dm-item {
  padding: 12px 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 1px solid #0f3460;
}

.channel-item:hover, .dm-item:hover {
  background: #0f3460;
}

.channel-item.active, .dm-item.active {
  background: #0f3460;
  border-left: 3px solid #e94560;
}

.channel-icon {
  color: #8b8b8b;
  font-size: 18px;
}

.channel-name {
  flex: 1;
  color: #fff;
}

.private-badge {
  font-size: 12px;
}

.dm-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e94560;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
}

.dm-info {
  flex: 1;
}

.dm-name {
  color: #fff;
  font-weight: 600;
}

.dm-preview {
  color: #8b8b8b;
  font-size: 12px;
}

.unread-count {
  background: #e94560;
  color: white;
  border-radius: 10px;
  padding: 2px 8px;
  font-size: 11px;
}

.chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.empty-state {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #8b8b8b;
}

.chat-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.chat-header {
  padding: 20px;
  border-bottom: 1px solid #0f3460;
}

.chat-header h3 {
  margin: 0 0 5px 0;
  color: #e94560;
}

.channel-description {
  margin: 0;
  color: #8b8b8b;
  font-size: 14px;
}

.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

.message {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.message-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #0f3460;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  flex-shrink: 0;
}

.message-content {
  flex: 1;
}

.message-header {
  display: flex;
  gap: 10px;
  margin-bottom: 5px;
}

.message-author {
  color: #e94560;
  font-weight: 600;
}

.message-time {
  color: #8b8b8b;
  font-size: 12px;
}

.message-text {
  color: #fff;
}

.message-input-container {
  padding: 20px;
  border-top: 1px solid #0f3460;
  display: flex;
  gap: 10px;
}

.message-input {
  flex: 1;
  padding: 12px;
  background: #16213e;
  border: 1px solid #0f3460;
  border-radius: 4px;
  color: white;
  outline: none;
}

.btn-send {
  padding: 12px 24px;
  background: #e94560;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: #16213e;
  padding: 30px;
  border-radius: 8px;
  width: 400px;
  max-width: 90%;
}

.modal-content h3 {
  margin: 0 0 20px 0;
  color: #e94560;
}

.input-field, .textarea-field {
  width: 100%;
  padding: 12px;
  margin-bottom: 15px;
  background: #1a1a2e;
  border: 1px solid #0f3460;
  border-radius: 4px;
  color: white;
  outline: none;
}

.textarea-field {
  min-height: 80px;
  resize: vertical;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  color: white;
  margin-bottom: 20px;
}

.modal-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.btn-primary, .btn-secondary {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
}

.btn-primary {
  background: #e94560;
  color: white;
}

.btn-secondary {
  background: #0f3460;
  color: white;
}

/* New Discord-like features */
.edited-badge {
  color: #8b8b8b;
  font-size: 11px;
  font-style: italic;
}

.message-actions {
  display: flex;
  gap: 5px;
  margin-top: 5px;
  opacity: 0;
  transition: opacity 0.2s;
}

.message:hover .message-actions {
  opacity: 1;
}

.action-btn {
  background: #0f3460;
  border: none;
  border-radius: 4px;
  padding: 4px 8px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.3s;
}

.action-btn:hover {
  background: #1a3a5c;
}

.message-edit {
  margin-top: 10px;
}

.edit-input {
  width: 100%;
  padding: 10px;
  background: #0f3460;
  border: 2px solid #e94560;
  border-radius: 4px;
  color: white;
  outline: none;
  margin-bottom: 8px;
}

.edit-actions {
  display: flex;
  gap: 8px;
}

.btn-save-edit, .btn-cancel-edit {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
  font-size: 12px;
}

.btn-save-edit {
  background: #4caf50;
  color: white;
}

.btn-cancel-edit {
  background: #8b8b8b;
  color: white;
}

.reactions {
  display: flex;
  gap: 5px;
  margin-top: 8px;
  flex-wrap: wrap;
}

.reaction-badge {
  padding: 4px 8px;
  background: #0f3460;
  border: 1px solid #0f3460;
  border-radius: 12px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.reaction-badge:hover {
  background: #1a3a5c;
  border-color: #e94560;
}

.reaction-badge.active {
  background: rgba(233, 69, 96, 0.2);
  border-color: #e94560;
}

.reaction-add {
  position: relative;
  margin-top: 5px;
}

.btn-add-reaction {
  padding: 4px 8px;
  background: transparent;
  border: 1px solid #0f3460;
  border-radius: 4px;
  color: #8b8b8b;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.3s;
}

.btn-add-reaction:hover {
  background: #0f3460;
  color: white;
}

.emoji-picker, .message-emoji-picker {
  position: absolute;
  bottom: 100%;
  left: 0;
  background: #16213e;
  border: 1px solid #0f3460;
  border-radius: 8px;
  padding: 10px;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 5px;
  z-index: 100;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.message-emoji-picker {
  bottom: auto;
  top: 100%;
  margin-top: 5px;
}

.emoji-option {
  background: transparent;
  border: none;
  font-size: 24px;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: background 0.2s;
}

.emoji-option:hover {
  background: #0f3460;
}

.btn-emoji {
  padding: 12px;
  background: #16213e;
  border: 1px solid #0f3460;
  border-radius: 4px;
  cursor: pointer;
  font-size: 20px;
  transition: background 0.3s;
}

.btn-emoji:hover {
  background: #0f3460;
}

.message-input-container {
  position: relative;
}
</style>

