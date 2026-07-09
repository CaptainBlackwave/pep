<template>
<section class="pv-page pv-messages-page">
    <div v-if="showUpgradePrompt" class="pv-upgrade-overlay">
      <div class="pv-upgrade-card">
        <span class="pv-upgrade-icon"><PvIcon name="message" /></span>
        <h2>Premium Feature</h2>
        <p>Upgrade to Premium to send direct messages to other members for private discussions and collaboration.</p>
        <router-link to="/pricing" class="pv-primary-button">View Pricing</router-link>
      </div>
    </div>
    <div v-else class="pv-messages" :class="{ 'pv-messages--thread-active': !!currentThread }">
      <aside class="pv-message-list">
        <header><h1>Messages</h1><span class="pv-icon-button active pv-mode-indicator" aria-label="Primary messages"><PvIcon name="document" /></span></header>
        <label class="pv-input-search"><input v-model="messageSearch" placeholder="Search messages..."><PvIcon name="search" /></label>
        <div class="pv-tabs pv-tabs--line"><span class="pv-tab-label active">Primary</span></div>
        <form class="pv-new-message" @submit.prevent="startMessageFromSearch">
          <label class="pv-input-search">
            <input v-model="messageRecipientSearch" placeholder="Message a member...">
            <PvIcon name="users" />
          </label>
          <div v-if="messageRecipientOptions.length > 0" class="pv-recipient-list">
            <button
              v-for="member in messageRecipientOptions"
              :key="member.id ?? member.slug"
              type="button"
              class="pv-recipient-row"
              :disabled="startingMessageUserId === member.id"
              @click="startMessage(member)"
            >
              <span class="pv-avatar" :class="member.color">{{ member.initial }}</span>
              <span><strong>{{ member.name }}</strong><small>@{{ member.username }}</small></span>
              <PvIcon name="message" />
            </button>
          </div>
          <p v-else-if="messageRecipientSearch.trim()" class="pv-muted">No members match that search.</p>
        </form>
        <p v-if="messagesStatusMessage" class="pv-muted">{{ messagesStatusMessage }}</p>
        <p v-if="messagesLoaded && chats.length === 0" class="pv-muted">No message threads yet.</p>
        <button
          v-for="chat in chats"
          :key="chat.id"
          class="pv-chat-row"
          :class="{ active: currentThread?.id === chat.id }"
          @click="openMessageThread(chat.id)"
        >
          <span class="pv-avatar" :class="chat.participant.color">{{ chat.participant.initial }}</span>
          <span class="pv-chat-row-main">
            <strong>{{ chat.participant.name }} <em v-if="chat.participant.role" class="pv-tag pv-chat-role">{{ chat.participant.role }}</em></strong>
            <small>{{ chat.preview }}</small>
          </span>
          <span class="pv-chat-row-end"><time>{{ chat.time }}</time><span v-if="chat.unread > 0" class="pv-unread-badge">{{ chat.unread }}</span></span>
        </button>
      </aside>
      <main v-if="currentThread" class="pv-chat-panel">
        <header class="pv-chat-header"><button class="pv-icon-button pv-icon-button--static pv-mobile-back" @click="openMessagesInbox" aria-label="Back to threads"><PvIcon name="arrow-left" /></button><span class="pv-avatar" :class="currentThread.participant.color">{{ currentThread.participant.initial }}</span><div class="pv-chat-header-copy"><h2>{{ currentThread.participant.name }} <span class="pv-tag pv-chat-role">{{ currentThread.participant.role }}</span></h2><small><span class="pv-green-dot"></span> {{ currentThread.participant.lastActive }}</small></div><span class="pv-flex-spacer"></span><button class="pv-icon-button pv-icon-button--static pv-chat-delete" title="Delete conversation" @click="deleteCurrentThread"><PvIcon name="trash" /></button><router-link :to="currentThread.participant.href" class="pv-icon-button pv-icon-button--static pv-chat-profile" aria-label="View member profile"><PvIcon name="user" /></router-link></header>
        <div v-if="showMessageSafetyNotice" class="pv-alert"><PvIcon name="shield" /><span>Messages are visible only to you and the recipient. Do not share personal info or make any transactions.</span><button type="button" class="pv-icon-button" aria-label="Dismiss notice" @click="showMessageSafetyNotice = false"><PvIcon name="close" /></button></div>
        <div ref="messageStreamRef" class="pv-chat-stream">
          <template v-for="(message, msgIdx) in currentThread.messages" :key="message.id ?? message.time">
            <span v-if="showDateSep(currentThread.messages, msgIdx)" class="pv-date-sep">{{ formatDateSep(message.sentAt) }}</span>
            <MessageBubble :side="message.side" :text="message.text" :time="message.time" :file="Boolean(message.attachmentName)" :attachment-name="message.attachmentName ?? ''" :attachment-label="message.attachmentLabel" :avatar-initial="message.avatarInitial || currentThread.participant.initial" :avatar-color="message.avatarColor || currentThread.participant.color" />
          </template>
        </div>
        <div v-if="messageAttachmentFile" class="pv-attachment-preview"><img v-if="messageAttachmentPreviewUrl" :src="messageAttachmentPreviewUrl" alt="Attachment preview"><span><strong>{{ messageAttachmentFile.name }}</strong><small>{{ formatFileSize(messageAttachmentFile.size) }}</small></span><button type="button" class="pv-icon-button" aria-label="Remove" @click="clearMessageAttachment"><PvIcon name="close" /></button></div>
        <form class="pv-chat-input" @submit.prevent="sendMessage"><button type="button" class="pv-icon-button pv-icon-button--static pv-chat-attach" title="Attach file" @click="messageFileInput?.click()"><PvIcon name="image" /></button><input ref="messageFileInput" type="file" accept="image/*,application/pdf" class="pv-sr-only" @change="handleMessageAttachment"><input v-model="messageBody" class="pv-chat-text" placeholder="Type a message..."><EmojiPicker v-model="messageBody" /><button class="pv-primary-button pv-chat-send" :disabled="sendingMessage || (!messageBody.trim() && !messageAttachmentFile)" aria-label="Send message"><PvIcon name="send" /></button></form>
      </main>
      <main v-else class="pv-chat-panel"><article class="pv-panel"><h2>No thread selected</h2><p class="pv-muted">Choose a thread to read messages.</p></article></main>
    </div>
  </section>
</template>

<script lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { inject } from 'vue';
export default {
  setup() {
    const state = inject('communityState');
    if (!state) throw new Error('Missing community state');
    return state as any;
  }
}
</script>
