<template>
<section class="pv-page">
    <div v-if="detailDiscussion" class="thread-wrap">
      <router-link to="/discussions" class="op-back">← Back to Discussions</router-link>
      <article class="op-card">
        <header class="op-header">
          <router-link class="op-avatar-link pv-author-link" :to="memberHref(detailDiscussion.authorUsername)" :aria-label="`View ${detailDiscussion.author}'s profile`">
            <span v-if="detailDiscussion.avatarUrl" class="op-avatar"><img :src="assetUrl(detailDiscussion.avatarUrl)" :alt="detailDiscussion.author"></span>
            <span v-else class="op-avatar op-avatar--letter">{{ detailDiscussion.initial }}</span>
            <span v-if="detailDiscussion.authorOnline" class="online-indicator"></span>
          </router-link>
          <div class="op-user">
            <div class="op-identity">
              <router-link class="pv-author-name-link" :to="memberHref(detailDiscussion.authorUsername)"><strong>{{ detailDiscussion.authorUsername }}</strong></router-link>
              <span v-if="detailDiscussion.authorBadge" class="verified">{{ detailDiscussion.authorBadge }}</span>
              <small class="op-presence" :class="{ online: detailDiscussion.authorOnline }"><span></span>{{ detailDiscussion.authorOnline ? 'Online' : 'Away' }}</small>
            </div>
          </div>
          <div class="op-meta">
            <span>{{ detailDiscussion.time }}</span>
            <div class="op-dots">
              <button class="dots" @click="togglePostMenu">⋯</button>
              <div v-if="showPostMenu" class="dots-dropdown" @click.self="showPostMenu = false">
                <button @click="shareCurrentPage(detailDiscussion.title); showPostMenu = false">Share</button>
                <button @click="toggleDiscussionSave(detailDiscussion); showPostMenu = false">{{ isSavedDiscussion(detailDiscussion) ? 'Unsave' : 'Save' }}</button>
                <button @click="toggleDiscussionFollow(detailDiscussion); showPostMenu = false">{{ isFollowingDiscussion(detailDiscussion) ? 'Unfollow' : 'Follow' }}</button>
                <button @click="openDiscussionReport(); showPostMenu = false">Report</button>
                <button v-if="authStore.user?.id === detailDiscussion.authorId && !isEditingDiscussion" @click="startEditDiscussion(); showPostMenu = false">Edit</button>
                <button v-if="authStore.user?.id === detailDiscussion.authorId && !isEditingDiscussion" @click="deleteDiscussion(); showPostMenu = false">Delete</button>
                <template v-if="hasAnyRole(['admin', 'moderator'])">
                  <button @click="moderateDiscussion(detailDiscussion, 'hide'); showPostMenu = false">Hide</button>
                  <button @click="moderateDiscussion(detailDiscussion, 'pin'); showPostMenu = false">{{ detailDiscussion.isPinned ? 'Unpin' : 'Pin' }}</button>
                  <button @click="moderateDiscussion(detailDiscussion, 'lock'); showPostMenu = false">{{ detailDiscussion.isLocked ? 'Unlock' : 'Lock' }}</button>
                  <button @click="moderateBanAuthor(detailDiscussion); showPostMenu = false">Ban Author</button>
                  <button @click="moderateWarnAuthor(detailDiscussion); showPostMenu = false">Warn Author</button>
                </template>
              </div>
            </div>
          </div>
        </header>
        <h1>{{ detailDiscussion.title }}</h1>
        <div v-if="detailDiscussion.isPinned || detailDiscussion.isLocked" class="op-flags">
          <span v-if="detailDiscussion.isPinned" class="flag-pinned"><PvIcon name="pin" /> Pinned</span>
          <span v-if="detailDiscussion.isLocked" class="flag-locked"><PvIcon name="lock" /> Locked</span>
        </div>
        <div v-if="!isEditingDiscussion" class="op-content">
          <div class="pv-rich-text" v-html="renderFormattedText(detailDiscussion.body ?? detailDiscussion.excerpt)"></div>
        </div>
         <div v-else class="thread-edit-form">
           <p v-if="discussionEditError" class="pv-alert pv-alert--compact">{{ discussionEditError }}</p>
           <input v-model="editDiscussionTitle" required maxlength="160" placeholder="Title">
            <select v-model="editDiscussionTag" class="pv-edit-select">
              <option value="">No tag</option>
              <option v-for="tag in discussionTags" :key="tag" :value="tag">{{ tag }}</option>
            </select>
            <small class="pv-edit-label">Tag — select a topic type</small>
            <select v-model="editDiscussionCategory" class="pv-edit-select">
              <option v-for="cat in discussionCategories" :key="cat.slug" :value="cat.slug">{{ cat.name }}</option>
            </select>
            <small class="pv-edit-label">Category — select a category</small>
           <TipTapComposer v-model="editDiscussionBody" placeholder="Update your discussion..." :max-length="10000" compact />
          <div class="pv-form-actions">
            <button type="button" class="pv-small-button" @click="cancelEditDiscussion">Cancel</button>
            <button type="button" class="pv-primary-button" :disabled="discussionEditSaving" @click="saveEditDiscussion">{{ discussionEditSaving ? 'Saving...' : 'Save Changes' }}</button>
          </div>
        </div>
        <footer class="op-actions">
          <div class="vote-pill">
            <button :class="{ active: detailDiscussion.viewerVote === 1 }" :disabled="discussionVoteLoading" aria-label="Upvote discussion" title="Upvote" @click="voteOnDiscussion(1)"><PvIcon name="vote-up" /></button>
            <strong>{{ detailDiscussion.voteScore }}</strong>
            <button :class="{ active: detailDiscussion.viewerVote === -1 }" :disabled="discussionVoteLoading" aria-label="Downvote discussion" title="Downvote" @click="voteOnDiscussion(-1)"><PvIcon name="vote-down" /></button>
          </div>
          <button aria-label="Reply to discussion" title="Reply" @click="jumpToReplyComposer"><PvIcon name="reply" /><span>Reply</span></button>
          <button aria-label="Quote discussion" title="Quote" @click="prepareReply(null, true)"><PvIcon name="quote" /><span>Quote</span></button>
          <div v-if="hasAnyRole(['admin', 'moderator'])" style="position: relative; display: inline-block;">
            <button aria-label="Moderate discussion" title="Moderate" @click.stop="activeModMenu = !activeModMenu"><PvIcon name="shield" /><span>Moderate</span></button>
            <div v-if="activeModMenu" class="dots-dropdown" style="bottom: 100%; top: auto; margin-bottom: 8px; left: 0;" @click.stop>
              <button @click="moderateDiscussion(detailDiscussion, 'hide'); activeModMenu = false">{{ detailDiscussion.status === 'hidden' ? 'Publish' : 'Hide' }}</button>
              <button @click="moderateDiscussion(detailDiscussion, 'pin'); activeModMenu = false">{{ detailDiscussion.isPinned ? 'Unpin' : 'Pin' }}</button>
              <button @click="moderateDiscussion(detailDiscussion, 'lock'); activeModMenu = false">{{ detailDiscussion.isLocked ? 'Unlock' : 'Lock' }}</button>
              <button @click="moderateDiscussion(detailDiscussion, 'premium'); activeModMenu = false">{{ detailDiscussion.premiumOnly ? 'Remove Premium' : 'Make Premium' }}</button>
            </div>
          </div>
          <button aria-label="Share discussion" title="Share" @click="shareCurrentPage(detailDiscussion.title)"><PvIcon name="share" /><span>Share</span></button>
          <button :class="{ active: isSavedDiscussion(detailDiscussion) }" :aria-label="isSavedDiscussion(detailDiscussion) ? 'Remove saved discussion' : 'Save discussion'" :title="isSavedDiscussion(detailDiscussion) ? 'Saved' : 'Save'" @click="toggleDiscussionSave(detailDiscussion)"><PvIcon name="bookmark" /><span>{{ isSavedDiscussion(detailDiscussion) ? 'Saved' : 'Save' }}</span></button>
          <button :class="{ active: isFollowingDiscussion(detailDiscussion) }" :aria-label="isFollowingDiscussion(detailDiscussion) ? 'Unfollow discussion' : 'Follow discussion'" :title="isFollowingDiscussion(detailDiscussion) ? 'Following' : 'Follow'" @click="toggleDiscussionFollow(detailDiscussion)"><PvIcon name="bell" /><span>{{ isFollowingDiscussion(detailDiscussion) ? 'Following' : 'Follow' }}</span></button>
        </footer>
      </article>

      <p v-if="actionStatusMessage" class="pv-alert pv-alert--compact">{{ actionStatusMessage }}</p>

      <article v-for="(reply, index) in replies" :key="reply.id ?? `${reply.author}-${reply.time}`" class="post-card reply-post">
        <div class="post-left reply-left">
          <router-link class="avatar letter-avatar reply-avatar pv-author-link" :to="memberHref(reply.authorUsername)" :aria-label="`View ${reply.author}'s profile`">
            <span v-if="reply.avatarUrl" class="img-wrap"><img :src="assetUrl(reply.avatarUrl)" :alt="reply.author"></span>
            <span v-else>{{ reply.initial }}</span>
            <span v-if="reply.authorOnline" class="online-indicator"></span>
          </router-link>
          <router-link class="reply-name pv-author-name-link" :to="memberHref(reply.authorUsername)">{{ reply.authorUsername }}</router-link>
        </div>
        <div class="post-main">
          <div class="reply-top">
            <div class="reply-meta">
              <span>#{{ Number(index) + 1 }}</span>
              <span>•</span>
              <span>{{ reply.time }}</span>
              <span v-if="reply.authorId && reply.authorId === detailDiscussion.authorId" class="small-badge">OP</span>
            </div>
          </div>
          <div class="reply-text pv-rich-text" v-html="renderFormattedText(reply.text)"></div>
          <figure v-if="isVisualAttachment(reply)" class="pv-reply-media">
            <img :src="reply.attachmentUrl || ''" :alt="reply.file || 'Reply attachment'">
            <figcaption>{{ reply.file }} <span>{{ attachmentLabel(reply) }}</span></figcaption>
          </figure>
          <div v-else-if="reply.file" class="pv-file-card"><PvIcon name="document" /><span><strong>{{ reply.file }}</strong><small>{{ attachmentLabel(reply) || 'Attachment' }}</small></span></div>
          <div class="divider"></div>
          <div class="actions">
            <div class="vote-box small">
              <button class="vote" :class="{ active: reply.viewerVote === 1 }" :disabled="replyVoteLoading === `${reply.id}:1`" aria-label="Upvote reply" title="Upvote" @click="voteOnReply(reply, 1)"><PvIcon name="vote-up" /></button>
              <span>{{ reply.votes }}</span>
              <button class="vote" :class="{ active: reply.viewerVote === -1 }" :disabled="replyVoteLoading === `${reply.id}:-1`" aria-label="Downvote reply" title="Downvote" @click="voteOnReply(reply, -1)"><PvIcon name="vote-down" /></button>
            </div>
            <div class="action-links">
              <button aria-label="Reply to this post" title="Reply" @click="prepareReply(reply)"><PvIcon name="reply" /><span class="action-label">Reply</span></button>
              <button aria-label="Quote this post" title="Quote" @click="prepareReply(reply, true)"><PvIcon name="quote" /><span class="action-label">Quote</span></button>
            </div>
            <div class="dots-corner">
              <button class="dots" @click.stop="toggleReplyMenu(index)">⋯</button>
              <div v-if="activeReplyMenu === index" class="dots-dropdown" @click.stop>
                <button @click="openReplyReport(reply)">Report</button>
                <button v-if="authStore.user?.id === reply.authorId" @click="deleteReply(reply)">Delete</button>
                <button v-if="hasAnyRole(['admin', 'moderator'])" @click="moderateHideReply(reply)">Hide</button>
                <button v-if="hasAnyRole(['admin', 'moderator'])" @click="moderateBanAuthor(reply)">Ban Author</button>
                <button v-if="hasAnyRole(['admin', 'moderator'])" @click="moderateWarnAuthor(reply)">Warn Author</button>
              </div>
            </div>
          </div>
        </div>
      </article>

      <p v-if="replyStatusMessage" class="pv-alert pv-alert--compact">{{ replyStatusMessage }}</p>

      <form v-if="authStore.isAuthenticated" id="reply-composer" class="reply-composer" @submit.prevent="submitReply">
        <div class="reply-composer-head">
          <strong>Reply as {{ accountName() }}</strong>
          <small>{{ plainTextFromRichText(replyBody).length }}/8000</small>
        </div>
        <TipTapComposer v-model="replyBody" placeholder="Write a reply..." :max-length="8000" compact />
        <div v-if="replyAttachmentFile || replyAttachmentGifUrl" class="pv-attachment-preview">
          <img v-if="replyAttachmentPreviewUrl || replyAttachmentGifUrl" :src="replyAttachmentPreviewUrl || replyAttachmentGifUrl" alt="Attachment preview">
          <span><strong>{{ replyAttachmentName() }}</strong><small>{{ replyAttachmentFile ? 'Image' : 'GIF' }}</small></span>
          <button type="button" class="pv-icon-button" aria-label="Remove" @click="clearReplyAttachment"><PvIcon name="close" /></button>
        </div>
        <div class="reply-composer-tools">
          <label class="pv-icon-button" for="reply-image-upload" aria-label="Attach image"><PvIcon name="image" /></label>
          <input id="reply-image-upload" class="pv-sr-only" type="file" accept="image/png,image/jpeg,image/webp,image/gif" @change="handleReplyAttachment">
          <GiphyPicker @select="onGifSelect" />
          <span class="pv-flex-spacer"></span>
          <button type="submit" class="pv-primary-button" :disabled="submittingReply"><PvIcon name="send" /> {{ submittingReply ? 'Posting...' : 'Post Reply' }}</button>
        </div>
      </form>
      <article v-else id="reply-composer" class="pv-panel pv-reply-login">
        <span class="pv-icon-tile"><PvIcon name="lock" /></span>
        <div>
          <h2>Sign in to reply</h2>
          <p class="pv-muted">Join the discussion with your account, quote posts, and upload attachments.</p>
        </div>
        <div class="pv-reply-login-actions">
          <router-link :to="{ path: '/login', query: { redirect: route.fullPath } }" class="pv-primary-button">Sign In</router-link>
          <router-link :to="{ path: '/register', query: { redirect: route.fullPath } }" class="pv-small-button">Register</router-link>
        </div>
      </article>
    </div>
    <div v-else class="pv-empty-route">
      <h1>Discussion not found</h1>
      <p>This discussion has not been published or does not exist.</p>
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
