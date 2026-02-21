<template>
  <div class="global-chat" :class="{ collapsed: isCollapsed }">
    <div class="chat-header" @click="toggleCollapse">
      <span class="chat-title">GLOBAL ({{ unreadCount }})</span>
      <button class="chat-toggle">{{ isCollapsed ? '^' : 'v' }}</button>
    </div>
    <div v-if="!isCollapsed" class="chat-body">
      <div class="chat-messages" ref="messagesContainer">
        <div v-for="msg in messages" :key="msg.id" class="chat-message">
          <span class="chat-user">{{ msg.username }}</span>
          <span class="chat-time">{{ msg.time }}</span>
          <div class="chat-text">{{ msg.content }}</div>
        </div>
      </div>
      <div class="chat-input">
        <input
          v-model="inputMessage"
          type="text"
          placeholder="Write your message..."
          @keyup.enter="handleSend"
        />
        <button class="send-btn" @click="handleSend">Send</button>
      </div>
      <div class="chat-footer">
        <router-link to="/chat" class="full-chat-link">Open Full Chat</router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { useChatStore } from '@/stores/chat'

const chatStore = useChatStore()

const isCollapsed = ref(false)
const inputMessage = ref('')
const messagesContainer = ref<HTMLElement | null>(null)

const messages = computed(() =>
  chatStore.recentMessages.map((msg) => ({
    id: msg.id,
    username: msg.username,
    content: msg.content,
    time: formatTime(msg.created_at),
  }))
)

const unreadCount = computed(() => chatStore.totalUnread)

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}

const handleSend = async () => {
  if (inputMessage.value.trim()) {
    const success = await chatStore.sendMessage(inputMessage.value)
    if (success) {
      inputMessage.value = ''
      await scrollToBottom()
    }
  }
}

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const formatTime = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

watch(
  () => chatStore.messages.length,
  () => {
    scrollToBottom()
  }
)
</script>

<style scoped>
.global-chat {
  position: fixed;
  bottom: 1rem;
  right: 1rem;
  width: 350px;
  background: rgba(15, 20, 25, 0.95);
  border: 1px solid rgba(0, 188, 212, 0.2);
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
  transition: all 0.3s;
  z-index: 90;
}

.global-chat.collapsed {
  height: auto;
}

.chat-header {
  padding: 0.75rem 1rem;
  background: rgba(30, 41, 59, 0.8);
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  border-radius: 0.5rem 0.5rem 0 0;
}

.chat-title {
  font-weight: 700;
  font-size: 0.875rem;
  color: #e2e8f0;
}

.chat-toggle {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 0.75rem;
  cursor: pointer;
}

.chat-body {
  display: flex;
  flex-direction: column;
  height: 300px;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.chat-message {
  font-size: 0.8125rem;
}

.chat-user {
  font-weight: 700;
  color: #00bcd4;
  margin-right: 0.5rem;
}

.chat-time {
  color: #64748b;
  font-size: 0.75rem;
}

.chat-text {
  color: #cbd5e1;
  margin-top: 0.125rem;
}

.chat-input {
  padding: 0.75rem;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
  display: flex;
  gap: 0.5rem;
}

.chat-input input {
  flex: 1;
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.375rem;
  padding: 0.5rem 0.75rem;
  color: #e2e8f0;
  font-size: 0.875rem;
}

.chat-input input::placeholder {
  color: #64748b;
}

.send-btn {
  background: rgba(0, 188, 212, 0.2);
  border: 1px solid rgba(0, 188, 212, 0.3);
  border-radius: 0.375rem;
  padding: 0.5rem 0.75rem;
  color: #00bcd4;
  cursor: pointer;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.send-btn:hover {
  background: rgba(0, 188, 212, 0.3);
}

.chat-footer {
  padding: 0.5rem 1rem;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
  text-align: center;
}

.full-chat-link {
  color: #00bcd4;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 600;
  transition: color 0.2s;
}

.full-chat-link:hover {
  color: #0891b2;
}

.chat-messages::-webkit-scrollbar {
  width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
  background: rgba(30, 41, 59, 0.3);
}

.chat-messages::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.3);
  border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.5);
}

@media (max-width: 768px) {
  .global-chat {
    width: calc(100% - 2rem);
    right: 1rem;
    left: 1rem;
  }
}
</style>
