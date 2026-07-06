<template>
  <div v-if="showNewDiscussion" class="pv-modal-backdrop pv-compose-backdrop" @click.self="closeNewDiscussion">
    <form class="pv-modal pv-discussion-modal" @submit.prevent="submitNewDiscussion">
      <header class="pv-compose-header">
        <span class="pv-compose-header-icon"><PvIcon name="message" /></span>
        <div>
          <h2>New Discussion</h2>
          <p>Start a conversation with the community.</p>
        </div>
        <button type="button" class="pv-icon-button pv-compose-close" aria-label="Close" @click="closeNewDiscussion">
          <PvIcon name="close" />
        </button>
      </header>
      <p v-if="discussionFormError" class="pv-alert pv-alert--compact">{{ discussionFormError }}</p>
      <label class="pv-compose-field">
        <span>Title <b>*</b></span>
        <span class="pv-compose-input-shell">
          <input v-model="newDiscussion.title" required maxlength="100" placeholder="Summarise your discussion in one sentence...">
          <small>{{ newDiscussion.title.length }} / 100</small>
        </span>
      </label>
      <div class="pv-compose-select-grid">
        <label class="pv-compose-field">
          <span>Category <b>*</b></span>
          <span class="pv-select-card">
            <PvIcon name="discussions" />
            <select v-model="newDiscussion.category_slug" required>
              <option value="">Choose category</option>
              <option v-for="category in discussionCategories" :key="category.slug" :value="category.slug">
                {{ category.name }}
              </option>
            </select>
          </span>
          <small>Choose the most relevant category.</small>
        </label>
        <label class="pv-compose-field">
          <span>Tag</span>
          <span class="pv-select-card">
            <PvIcon name="tag" />
            <select v-model="newDiscussion.tag">
              <option value="">No tag</option>
              <option v-for="tag in discussionTags" :key="tag" :value="tag">{{ tag }}</option>
            </select>
          </span>
          <small>Add a tag to help others find your discussion.</small>
        </label>
      </div>
      <section class="pv-compose-section">
        <div class="pv-compose-section-head">
          <strong>Discussion <b>*</b></strong>
        </div>
        <TipTapComposer :key="'new-disc-' + newDiscussionKey" v-model="newDiscussion.body" placeholder="Write your discussion..." :max-length="10000" />
      </section>
      <div class="pv-compose-lower">
        <section class="pv-compose-attachments" aria-label="Add attachments">
          <p>Drag and drop files here or click to browse</p>
          <div>
            <button type="button" :disabled="discussionUploading" @click="insertDiscussionAttachment('image')"><PvIcon name="image" />Image</button>
            <button type="button" :disabled="discussionUploading" @click="insertDiscussionAttachment('video')"><PvIcon name="list" />Video</button>
            <button type="button" @click="insertDiscussionAttachment('link')"><PvIcon name="share" />Link</button>
            <button type="button" @click="insertDiscussionAttachment('poll')"><PvIcon name="chart" />Poll</button>
            <button type="button" :disabled="discussionUploading" @click="insertDiscussionAttachment('file')"><PvIcon name="document" />{{ discussionUploading ? 'Uploading...' : 'File' }}</button>
            <input ref="discussionFileInput" type="file" accept="image/*,video/*,application/pdf" class="pv-sr-only" @change="handleDiscussionFileUpload">
          </div>
        </section>
        <aside class="pv-compose-tips">
          <strong>Tips</strong>
          <ul>
            <li>Be respectful and follow our guidelines</li>
            <li>Search before posting to avoid duplicates</li>
            <li>Use @ to mention members</li>
            <li>You can preview your post anytime</li>
          </ul>
        </aside>
      </div>
      <footer class="pv-compose-footer">
        <span><PvIcon name="upload" /> {{ discussionDraftStatus }}</span>
        <div>
          <button type="button" class="pv-small-button" @click="closeNewDiscussion">Cancel</button>
          <button type="button" class="pv-small-button" @click="clearNewDiscussion">Clear</button>
          <button type="submit" class="pv-primary-button" :disabled="creatingDiscussion">
            <PvIcon name="send" />
            {{ creatingDiscussion ? 'Posting...' : 'Post Discussion' }}
          </button>
        </div>
      </footer>
    </form>
  </div>
  <div v-if="showSubmitLabResult" class="pv-modal-backdrop" @click.self="closeSubmitLabResult">
    <form class="pv-modal" @submit.prevent="submitLabResult">
      <header class="pv-panel-header">
        <div>
          <h2>Submit Lab Result</h2>
          <p class="pv-muted">Share a batch-specific report for admin review.</p>
        </div>
        <button type="button" class="pv-icon-button" aria-label="Close" @click="closeSubmitLabResult">
          <PvIcon name="close" />
        </button>
      </header>
      <p v-if="labFormError" class="pv-alert pv-alert--compact">{{ labFormError }}</p>
      <label>
        Compound
        <input v-model="newLabResult.compound_name" required maxlength="160" placeholder="Compound name">
      </label>
      <div class="pv-two-col">
        <label>
          Compound Type
          <input v-model="newLabResult.compound_type" maxlength="80" placeholder="Compound type">
        </label>
        <label>
          Use Case
          <input v-model="newLabResult.use_case" maxlength="120" placeholder="Research category">
        </label>
      </div>
      <div class="pv-two-col">
        <label>
          Vendor
          <input v-model="newLabResult.vendor_name" required maxlength="160" placeholder="Vendor name">
        </label>
        <label>
          Batch
          <input v-model="newLabResult.batch_code" required maxlength="120" placeholder="Batch code">
        </label>
      </div>
      <div class="pv-two-col">
        <label>
          Lab
          <input v-model="newLabResult.lab_name" required maxlength="160" placeholder="Testing lab">
        </label>
        <label>
          Tested Date
          <input v-model="newLabResult.tested_at" type="date">
        </label>
      </div>
      <div class="pv-two-col">
        <label>
          Purity %
          <input v-model.number="newLabResult.purity_percent" type="number" min="0" max="100" step="0.01" placeholder="Purity percentage">
        </label>
        <label>
          COA filename
          <input v-model="newLabResult.coa_filename" maxlength="180" placeholder="report.pdf">
        </label>
      </div>
      <label>
        Notes
        <div class="pv-textarea-with-emoji">
          <textarea v-model="newLabResult.notes" rows="4" maxlength="4000" placeholder="Add relevant report notes or context for moderators."></textarea>
          <EmojiPicker v-model="newLabResult.notes" />
        </div>
      </label>
      <footer>
        <button type="button" class="pv-small-button" @click="closeSubmitLabResult">Cancel</button>
        <button type="submit" class="pv-primary-button" :disabled="submittingLabResult">
          <PvIcon name="send" />
          {{ submittingLabResult ? 'Submitting...' : 'Submit for Review' }}
        </button>
      </footer>
    </form>
  </div>

  <div v-if="showReportModal" class="pv-modal-backdrop" @click.self="closeReportModal">
    <form class="pv-modal pv-modal--compact" @submit.prevent="submitReport">
      <header class="pv-panel-header">
        <div>
          <h2>Report Content</h2>
          <p class="pv-muted">Send this to moderators for review.</p>
        </div>
        <button type="button" class="pv-icon-button" aria-label="Close" @click="closeReportModal">
          <PvIcon name="close" />
        </button>
      </header>
      <label>
        Reason
        <select v-model="reportReason">
          <option value="source-discussion">Source or transaction discussion</option>
          <option value="medical-claims">Medical claims</option>
          <option value="scam">Scam or solicitation</option>
          <option value="harassment">Harassment</option>
          <option value="privacy">Privacy concern</option>
          <option value="spam">Spam</option>
          <option value="other">Other</option>
        </select>
      </label>
      <label>
        Details
        <textarea v-model="reportDetails" rows="4" maxlength="1000" placeholder="Add context for moderators."></textarea>
      </label>
      <footer>
        <button type="button" class="pv-small-button" @click="closeReportModal">Cancel</button>
        <button type="submit" class="pv-primary-button" :disabled="reportSubmitting">
          <PvIcon name="shield" />
          {{ reportSubmitting ? 'Sending...' : 'Send Report' }}
        </button>
      </footer>
    </form>
  </div>

  <section v-if="page === 'home'" class="pv-page pv-dashboard">
    <div class="pv-dashboard-grid">
      <div class="pv-stack">
        <article class="pv-hero" :style="{ backgroundImage: `linear-gradient(90deg, rgba(13,14,22,.96), rgba(13,14,22,.72) 43%, rgba(13,14,22,.22)), url(${heroImage})` }">
          <div>
            <h1>A trusted community for peptide information.</h1>
            <p>Real reviews. Real lab results. Real people.</p>
          </div>
          <div class="pv-hero-points">
            <span><PvIcon name="shield" /> Independent Reviews</span>
            <span><PvIcon name="flask" /> Lab Verified</span>
            <span><PvIcon name="users" /> Community Driven</span>
            <span><PvIcon name="lock" /> Private & Secure</span>
          </div>
        </article>

      </div>

      <aside class="pv-stack pv-right-rail">
        <article class="pv-panel">
          <header class="pv-panel-header">
            <h2><PvIcon name="megaphone" /> Announcements</h2>
            <router-link to="/announcements" class="pv-small-button">View all</router-link>
          </header>
          <div class="pv-mini-list">
            <p v-if="announcementsLoaded && announcementPreview.length === 0" class="pv-muted">No announcements published yet.</p>
            <router-link v-for="item in announcementPreview" :key="item.slug" :to="item.href" class="pv-mini-row">
              <span><strong>{{ item.title }}</strong><small>{{ item.text }}</small><em>{{ item.time }}</em></span>
              <small class="pv-read-more">Read more</small>
            </router-link>
          </div>
        </article>
        <article class="pv-panel">
          <header class="pv-panel-header"><h2><PvIcon name="users" /> Members Online</h2><span class="pv-count">{{ memberStats.online }}</span></header>
          <div class="pv-avatar-stack">
            <span v-for="member in onlineMembers" :key="member.name" class="pv-avatar" :class="member.color">{{ member.initial }}</span>
            <span class="pv-more">+{{ Math.max(0, memberStats.online - onlineMembers.length) }}</span>
          </div>
          <p class="pv-muted">Active members in the last 15 minutes</p>
        </article>
        <article class="pv-panel">
          <header class="pv-panel-header">
            <h2><PvIcon name="star" /> Top Reviewed Vendors</h2>
            <router-link to="/vendor-reviews" class="pv-small-button">View all</router-link>
          </header>
          <div class="pv-ranked-list">
            <p v-if="vendorsLoaded && vendors.length === 0" class="pv-muted">No reviewed vendors yet.</p>
            <router-link v-for="vendor in vendors.slice(0, 5)" :key="vendor.name" :to="vendor.href" class="pv-ranked-row">
              <span class="pv-vendor-logo"><img v-if="vendor.imageUrl" :src="vendor.imageUrl" :alt="vendor.name"><template v-else>{{ vendor.logo }}</template></span>
              <span><strong>{{ vendor.name }}</strong><small class="pv-stars">★★★★★ <em>{{ vendor.rating }} ({{ vendor.reviews }})</em></small></span>
              <PvIcon name="chevron" />
            </router-link>
          </div>
        </article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'discussions'" class="pv-page">
    <div class="pv-content-grid pv-content-grid--wide">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div>
            <h1>Discussions</h1>
            <p>Share knowledge. Ask questions. Get real answers.</p>
          </div>
          <button class="pv-primary-button" @click="openNewDiscussion"><PvIcon name="plus" /> New Discussion</button>
        </header>
        <div class="pv-category-strip">
          <button
            v-for="category in categoryFilters"
            :key="category.slug"
            :class="{ active: activeCategory === category.slug }"
            @click="setDiscussionCategory(category.slug)"
          >
            <PvIcon :name="category.icon" />
            <span class="pv-category-name">{{ category.name }}</span>
            <strong>{{ formatCount(category.count) }}</strong>
          </button>
        </div>
        <article class="pv-panel">
          <div class="pv-toolbar">
            <button class="pv-small-button" type="button" @click="cycleDiscussionSort">{{ discussionSortLabel }} <PvIcon name="chevron" /></button>
          </div>
          <form class="pv-inline-search" @submit.prevent="applyDiscussionFilters">
            <label class="pv-input-search">
              <input v-model="discussionSearch" placeholder="Search discussions..." type="search">
              <PvIcon name="search" />
            </label>
            <button class="pv-small-button" type="submit">Search</button>
            <button v-if="discussionSearch || activeCategory" class="pv-small-button" type="button" @click="clearDiscussionFilters">Clear</button>
          </form>
          <p v-if="discussionStatusMessage" class="pv-alert pv-alert--compact">{{ discussionStatusMessage }}</p>
          <div class="pv-topic-list">
            <p v-if="discussionsLoaded && discussions.length === 0" class="pv-muted">No discussions found.</p>
            <article
              v-for="topic in discussions"
              :key="topic.title"
              class="topic-card"
              role="link"
              tabindex="0"
              @click="goToDiscussion(topic)"
              @keydown.enter.prevent="goToDiscussion(topic)"
              @keydown.space.prevent="goToDiscussion(topic)"
            >
              <div class="topic-menu">
                <span>{{ topic.time }}</span>
                <button>⋮</button>
              </div>
              <aside class="author-panel">
                <router-link class="avatar-wrap pv-author-link" :to="memberHref(topic.authorUsername)" :aria-label="`View ${topic.author}'s profile`" @click.stop>
                  <span v-if="topic.avatarUrl" class="avatar" :class="topic.color"><img :src="assetUrl(topic.avatarUrl)" :alt="topic.author"></span>
                  <span v-else class="avatar" :class="topic.color">{{ topic.initial }}</span>
                </router-link>
                <router-link class="pv-author-name-link" :to="memberHref(topic.authorUsername)" @click.stop><h4>{{ topic.authorUsername }}</h4></router-link>
                <div class="author-badge">🛡 Trusted Member</div>
                <div class="level-badge">✪ Level 12</div>
                <div class="author-posts">💬 {{ topic.replies }} posts</div>
              </aside>
              <main class="topic-body">
                <span v-if="topic.tag" class="topic-type">▱ {{ topic.tag }}</span>
                <span v-if="topic.isPinned" class="topic-type topic-type--pinned">📌 Pinned</span>
                <span v-if="topic.isLocked" class="topic-type topic-type--locked">🔒 Locked</span>
                <h2>{{ topic.title }}</h2>
                <p class="topic-excerpt">{{ topic.excerpt }}</p>
                <div class="divider"></div>
                <div class="topic-bottom">
                  <div class="stats">
                    <div class="stat">
                      <span>▱</span>
                      <strong>{{ topic.replies }}</strong>
                      <small>Replies</small>
                    </div>
                    <div class="stat">
                      <span>◉</span>
                      <strong>{{ topic.views }}</strong>
                      <small>Views</small>
                    </div>
                    <div class="stat">
                      <span>♡</span>
                      <strong>{{ topic.voteScore }}</strong>
                      <small>Likes</small>
                    </div>
                  </div>
                  <div v-if="topic.latestReply" class="last-reply">
                    <small>Last reply</small>
                    <router-link class="reply-row pv-author-link" :to="memberHref(topic.latestReply.username || topic.latestReply.author)" @click.stop>
                      <img v-if="topic.latestReply.avatar" :src="assetUrl(topic.latestReply.avatar)" :alt="topic.latestReply.author">
                      <span v-else class="avatar-sm">{{ topic.latestReply.initial }}</span>
                      <div>
                        <strong>{{ topic.latestReply.username || topic.latestReply.author }}</strong>
                        <span>{{ topic.latestReply.timeAgo }}</span>
                      </div>
                    </router-link>
                  </div>
                </div>
              </main>
            </article>
          </div>
          <PaginationBlock :meta="discussionPagination" @page="setDiscussionPage" />
        </article>
      </main>
    </div>
  </section>

  <section v-else-if="page === 'discussionDetail'" class="pv-page">
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
            <router-link class="pv-author-name-link" :to="memberHref(detailDiscussion.authorUsername)"><strong>{{ detailDiscussion.authorUsername }}</strong></router-link>
            <span class="verified">✓</span>
          </div>
          <div class="op-meta">
            <span>{{ detailDiscussion.time }}</span>
            <div class="op-dots">
              <button class="dots" @click="togglePostMenu">⋯</button>
              <div v-if="showPostMenu" class="dots-dropdown" @click.self="showPostMenu = false">
                <button @click="openDiscussionReport(); showPostMenu = false">Report</button>
                <button v-if="authStore.user?.id === detailDiscussion.authorId && !isEditingDiscussion" @click="startEditDiscussion(); showPostMenu = false">Edit</button>
                <button v-if="authStore.user?.id === detailDiscussion.authorId && !isEditingDiscussion" @click="deleteDiscussion(); showPostMenu = false">Delete</button>
              </div>
            </div>
          </div>
        </header>
        <h1>{{ detailDiscussion.title }}</h1>
        <div v-if="detailDiscussion.isPinned || detailDiscussion.isLocked" class="op-flags">
          <span v-if="detailDiscussion.isPinned" class="flag-pinned">📌 Pinned</span>
          <span v-if="detailDiscussion.isLocked" class="flag-locked">🔒 Locked</span>
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
        </footer>
      </article>

      <p v-if="actionStatusMessage" class="pv-alert pv-alert--compact">{{ actionStatusMessage }}</p>

      <article v-for="(reply, index) in replies" :key="reply.id ?? `${reply.author}-${reply.time}`" class="post-card reply-post">
        <div class="post-left reply-left">
          <router-link class="avatar letter-avatar pv-author-link" :to="memberHref(reply.authorUsername)" :aria-label="`View ${reply.author}'s profile`">
            <span v-if="reply.avatarUrl" class="img-wrap"><img :src="assetUrl(reply.avatarUrl)" :alt="reply.author"></span>
            <span v-else>{{ reply.initial }}</span>
            <span v-if="reply.authorOnline" class="online-indicator"></span>
          </router-link>
          <router-link class="reply-name pv-author-name-link" :to="memberHref(reply.authorUsername)">{{ reply.authorUsername }}</router-link>
        </div>
        <div class="post-main">
          <div class="reply-top">
            <div class="reply-meta">
              <span>#{{ index + 1 }}</span>
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
              </div>
            </div>
          </div>
        </div>
      </article>

      <p v-if="replyStatusMessage" class="pv-alert pv-alert--compact">{{ replyStatusMessage }}</p>

      <form id="reply-composer" class="reply-composer" @submit.prevent="submitReply">
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
    </div>
    <div v-else class="pv-empty-route">
      <h1>Discussion not found</h1>
      <p>This discussion has not been published or does not exist.</p>
    </div>
  </section>

  <section v-else-if="page === 'labResults'" class="pv-page">
    <div v-if="showUpgradePrompt" class="pv-upgrade-overlay">
      <div class="pv-upgrade-card">
        <PvIcon name="flask" />
        <h2>Premium Feature</h2>
        <p>Upgrade to Premium to access independent lab testing reports, COAs, purity analysis, and batch data.</p>
        <router-link to="/pricing" class="pv-primary-button">View Pricing</router-link>
      </div>
    </div>
    <div v-else class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>Lab Results</h1><p>Independent lab testing and analysis from community members.</p></div>
          <button class="pv-primary-button" @click="openSubmitLabResult"><PvIcon name="flask" /> Submit Lab Result</button>
        </header>
        <article class="pv-panel">
          <div class="pv-toolbar">
            <div class="pv-tabs">
              <button :class="{ active: labTypeFilter === '' }" @click="setLabTypeFilter('')">All Results</button>
              <button v-for="type in labFilterOptions.compound_types" :key="type" :class="{ active: labTypeFilter === type }" @click="setLabTypeFilter(type)">{{ type }}</button>
            </div>
            <button class="pv-small-button" type="button" @click="cycleLabSort">{{ labSortLabel }} <PvIcon name="chevron" /></button>
            <button class="pv-icon-button" @click="applyLabFilters"><PvIcon name="filter" /></button>
          </div>
          <form class="pv-inline-search" @submit.prevent="applyLabFilters">
            <label class="pv-input-search">
              <input v-model="labSearch" placeholder="Search compounds, vendors, batches, labs..." type="search">
              <PvIcon name="search" />
            </label>
            <button class="pv-small-button" type="submit">Search</button>
            <button v-if="labHasActiveFilters" class="pv-small-button" type="button" @click="clearLabFilters">Clear</button>
          </form>
          <p v-if="labStatusMessage" class="pv-alert pv-alert--compact">{{ labStatusMessage }}</p>
          <p v-if="labResultsLoaded && labResults.length === 0" class="pv-muted">No lab results found.</p>
          <router-link v-for="result in labResults" :key="result.slug" :to="result.href" class="pv-result-row">
            <span class="pv-coa-thumb pv-coa-thumb--large"></span>
            <span class="pv-vial-icon" :class="result.color"><PvIcon name="flask" /></span>
            <span class="pv-topic-main">
              <strong>{{ result.name }}</strong>
              <small>Vendor: {{ result.vendor }}</small>
              <small>Batch: {{ result.batch }}</small>
              <small>Lab: <em>{{ result.lab }}</em></small>
              <span class="pv-chip-row"><span>{{ result.type }}</span><span>{{ result.use }}</span><span>{{ result.sampleType }}</span></span>
            </span>
            <span class="pv-purity pv-purity--green">{{ result.purity }}<small>Purity</small></span>
            <span class="pv-identity"><PvIcon name="shield" /> {{ result.identityResult }}<br>Identity</span>
            <span class="pv-date">{{ result.date }}<small>Tested Date</small></span>
            <span class="pv-small-button">View Report</span>
            <span class="pv-row-meta"><PvIcon name="eye" /> {{ result.views }} <PvIcon name="message" /> {{ result.comments }}</span>
          </router-link>
          <PaginationBlock :meta="labPagination" @page="setLabPage" />
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel pv-filter-panel">
          <header class="pv-panel-header"><h2>Filter Results</h2><button class="pv-purple-link" @click="clearLabFilters">Clear all</button></header>
          <label class="pv-input-search"><input v-model="labSearch" placeholder="Search compounds, vendors..." @keydown.enter="applyLabFilters"><PvIcon name="search" /></label>
          <label>Compound Type<select v-model="labTypeFilter"><option value="">All compound types</option><option v-for="type in labFilterOptions.compound_types" :key="type" :value="type">{{ type }}</option></select></label>
          <label>Compound<select v-model="labCompoundFilter"><option value="">All compounds</option><option v-for="compound in labFilterOptions.compounds" :key="compound" :value="compound">{{ compound }}</option></select></label>
          <label>Vendor<select v-model="labVendorFilter"><option value="">All vendors</option><option v-for="vendor in labFilterOptions.vendors" :key="vendor" :value="vendor">{{ vendor }}</option></select></label>
          <label>Lab<select v-model="labLabFilter"><option value="">All labs</option><option v-for="lab in labFilterOptions.labs" :key="lab" :value="lab">{{ lab }}</option></select></label>
          <button class="pv-primary-button pv-full" @click="applyLabFilters">Apply Filters</button>
        </article>
        <article class="pv-panel">
          <h2>About Lab Results</h2>
          <p class="pv-muted">All lab results are shared by community members for educational and harm reduction purposes only.</p>
          <ul class="pv-check-list"><li>Independent testing</li><li>Community verified</li><li>COA and analysis reports</li><li>Batch specific results</li></ul>
          <button class="pv-purple-link" type="button" @click="openSubmitLabResult">How to submit a lab result -></button>
        </article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'labReport'" class="pv-page">
    <div v-if="detailLabResult" class="pv-content-grid">
      <main class="pv-stack">
        <nav class="pv-breadcrumbs">Lab Results <PvIcon name="chevron" /> {{ detailLabResult.name }} <PvIcon name="chevron" /> Report</nav>
        <header class="pv-page-header">
          <div><h1>{{ detailLabResult.name }} <span class="pv-success-pill">{{ detailLabResult.purity }} Purity</span></h1><p>Batch: {{ detailLabResult.batch }} · Vendor: <router-link :to="{ path: '/lab-results', query: { q: detailLabResult.vendor } }">{{ detailLabResult.vendor }}</router-link> · Lab: <router-link :to="{ path: '/lab-results', query: { q: detailLabResult.lab } }">{{ detailLabResult.lab }}</router-link></p></div>
          <div class="pv-action-row"><button class="pv-small-button" @click="downloadLabReport"><PvIcon name="download" /> Download Report</button><button class="pv-primary-button" @click="shareCurrentPage(detailLabResult.name)"><PvIcon name="share" /> Share</button></div>
        </header>
        <div class="pv-tabs pv-tabs--line"><button :class="{ active: labDetailTab === 'overview' }" @click="labDetailTab = 'overview'">Report Overview</button><button :class="{ active: labDetailTab === 'certificate' }" @click="labDetailTab = 'certificate'">Full Certificate</button><button :class="{ active: labDetailTab === 'raw' }" @click="labDetailTab = 'raw'">Raw Data</button><button :class="{ active: labDetailTab === 'batch' }" @click="labDetailTab = 'batch'">Batch History</button></div>
        <p v-if="labStatusMessage" class="pv-alert pv-alert--compact">{{ labStatusMessage }}</p>
        <div v-if="labDetailTab === 'overview'" class="pv-report-meta">
          <span><PvIcon name="calendar" /><small>Tested Date</small><strong>{{ detailLabResult.date }}</strong></span>
          <span><PvIcon name="box" /><small>Received Date</small><strong>{{ detailLabResult.receivedDate }}</strong></span>
          <span><PvIcon name="document" /><small>Report ID</small><strong>{{ detailLabResult.reportId }}</strong></span>
          <span><PvIcon name="flask" /><small>Sample Type</small><strong>{{ detailLabResult.sampleType }}</strong></span>
          <span><PvIcon name="shield" /><small>Sample Condition</small><strong>{{ detailLabResult.sampleCondition }}</strong></span>
        </div>
        <article v-if="labDetailTab === 'certificate' || labDetailTab === 'overview'" class="pv-certificate">
          <header><strong>{{ detailLabResult.lab }}</strong><span>Certificate of Analysis</span></header>
          <div class="pv-cert-grid">
            <span>Client <strong>{{ detailLabResult.vendor }}</strong></span><span>Analysis Date <strong>{{ detailLabResult.date }}</strong></span>
            <span>Sample <strong>{{ detailLabResult.name }}</strong></span><span>Report ID <strong>{{ detailLabResult.reportId }}</strong></span>
            <span>Batch <strong>{{ detailLabResult.batch }}</strong></span><span>Receipt Date <strong>{{ detailLabResult.receivedDate }}</strong></span>
          </div>
          <table>
            <thead><tr><th>Test</th><th>Result</th></tr></thead>
            <tbody>
              <tr v-if="detailLabResult.identityResult"><td>Identity</td><td>{{ detailLabResult.identityResult }}</td></tr>
              <tr v-if="detailLabResult.purity"><td>Purity</td><td class="pv-green-text">{{ detailLabResult.purity }}</td></tr>
              <tr v-if="detailLabResult.waterContent"><td>Water Content</td><td>{{ detailLabResult.waterContent }}</td></tr>
              <tr v-if="detailLabResult.peptideContent"><td>Peptide Content</td><td>{{ detailLabResult.peptideContent }}</td></tr>
            </tbody>
          </table>
        </article>
        <article v-if="labDetailTab === 'raw'" class="pv-panel"><h2>Raw Data</h2><dl class="pv-data-list"><div><dt>Identity</dt><dd>{{ detailLabResult.identityResult || 'Not provided' }}</dd></div><div><dt>Purity</dt><dd>{{ detailLabResult.purity || 'Not provided' }}</dd></div><div><dt>Water Content</dt><dd>{{ detailLabResult.waterContent || 'Not provided' }}</dd></div><div><dt>Peptide Content</dt><dd>{{ detailLabResult.peptideContent || 'Not provided' }}</dd></div><div><dt>COA Filename</dt><dd>{{ detailLabResult.coaFilename || 'Not attached' }}</dd></div></dl></article>
        <article v-if="labDetailTab === 'batch'" class="pv-panel"><h2>Batch History</h2><p class="pv-muted">Showing the submitted report for batch {{ detailLabResult.batch }}. Use the lab results search to find other records for this vendor, compound, or lab.</p><div class="pv-action-row"><button class="pv-small-button" @click="findCurrentBatchResults">Find this batch</button><button class="pv-small-button" @click="findCurrentVendorLabResults">Find vendor results</button></div></article>
        <article v-if="labDetailTab === 'overview'" class="pv-panel"><h2>Additional Notes</h2><p>{{ detailLabResult.notes }}</p></article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Summary</h2>
          <div class="pv-ring" :style="detailLabRingStyle"><strong>{{ detailLabResult.purity }}</strong><span>Purity</span></div>
          <dl class="pv-data-list"><div><dt>Identity</dt><dd>{{ detailLabResult.identityResult }}</dd></div><div><dt>Purity</dt><dd>{{ detailLabResult.purity }}</dd></div><div><dt>Water Content</dt><dd>{{ detailLabResult.waterContent }}</dd></div><div><dt>Peptide Content</dt><dd>{{ detailLabResult.peptideContent }}</dd></div></dl>
          <div class="pv-pass-box"><PvIcon name="shield" /> Overall Result <strong>{{ detailLabResult.overallResult }}</strong></div>
        </article>
        <article class="pv-panel"><h2>Compound Details</h2><dl class="pv-data-list"><div><dt>Compound Name</dt><dd>{{ detailLabResult.name }}</dd></div><div><dt>Type</dt><dd>{{ detailLabResult.type }}</dd></div><div><dt>Use Case</dt><dd>{{ detailLabResult.use }}</dd></div></dl></article>
        <article class="pv-panel"><h2>About This Report</h2><p class="pv-muted">This report is provided by {{ detailLabResult.lab }} for independent testing and analysis. All testing is performed using validated analytical methods.</p><ul class="pv-check-list"><li>ISO 17025 Accredited Lab</li><li>Community Funded</li><li>Independent & Unbiased</li><li>Batch Specific Results</li></ul></article>
      </aside>
    </div>
    <div v-else class="pv-empty-route">
      <h1>Lab result not found</h1>
      <p>This report has not been published or does not exist.</p>
    </div>
  </section>

  <section v-else-if="page === 'vendorReviews'" class="pv-page">
    <div v-if="showUpgradePrompt" class="pv-upgrade-overlay">
      <div class="pv-upgrade-card">
        <PvIcon name="shield" />
        <h2>Premium Feature</h2>
        <p>Upgrade to Premium to read and write vendor reviews with ratings, photos, and detailed feedback.</p>
        <router-link to="/pricing" class="pv-primary-button">View Pricing</router-link>
      </div>
    </div>
    <div v-else class="pv-content-grid pv-content-grid--vendor-index">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>Vendors</h1><p>Browse vendor profiles, compare community feedback, and write a review from the vendor row.</p></div>
        </header>
        <article class="pv-panel">
          <div class="pv-toolbar"><div class="pv-tabs"><button :class="{ active: vendorStatusFilter === '' }" @click="setVendorStatusFilter('')">All Vendors</button><button v-for="status in vendorFilterOptions.statuses" :key="status.slug" :class="{ active: vendorStatusFilter === status.slug }" @click="setVendorStatusFilter(status.slug)">{{ status.name }}</button></div><button class="pv-small-button" type="button" @click="cycleVendorSort">{{ vendorSortLabel }} <PvIcon name="chevron" /></button><button class="pv-icon-button" @click="loadVendors"><PvIcon name="filter" /></button></div>
          <form class="pv-inline-search" @submit.prevent="loadVendors">
            <label class="pv-input-search">
              <input v-model="vendorSearch" placeholder="Search vendors..." type="search">
              <PvIcon name="search" />
            </label>
            <button class="pv-small-button" type="submit">Search</button>
            <button v-if="vendorHasActiveFilters" class="pv-small-button" type="button" @click="clearVendorFilters">Clear</button>
          </form>
          <p v-if="vendorStatusMessage" class="pv-alert pv-alert--compact">{{ vendorStatusMessage }}</p>
          <p v-if="vendorsLoaded && vendors.length === 0" class="pv-muted">No vendors found.</p>
          <article v-for="vendor in vendors" :key="vendor.slug" class="vendor-card">
            <router-link :to="vendor.href" class="vendor-arrow">›</router-link>
            <div class="vendor-top">
              <span v-if="vendor.imageUrl" class="vendor-logo"><img :src="vendor.imageUrl" :alt="vendor.name"></span>
              <span v-else class="vendor-logo vendor-logo--letter">{{ vendor.logoText }}</span>
              <div class="vendor-info">
                <h3>{{ vendor.name }}</h3>
                <span class="trusted">✓ {{ vendor.status }}</span>
                <span v-if="vendor.country" class="country-badge">{{ countryFlag(vendor.country) }} {{ vendor.country }}</span>
                <p class="vendor-meta">☆ {{ vendor.reviews }} {{ vendor.reviews === 1 ? 'review' : 'reviews' }} · Member since {{ vendor.since }}</p>
              </div>
            </div>
            <div class="vendor-tags">
              <span v-for="chip in vendor.chips" :key="chip">{{ chip }}</span>
            </div>
            <div class="vendor-stats">
              <div>
                <strong>★ {{ vendor.rating || '0.0' }} <small>/ 5</small></strong>
                <span>Overall Rating</span>
              </div>
              <div>
                <strong>🛒 {{ vendor.buyAgain || '0%' }}</strong>
                <span>Would buy again</span>
              </div>
            </div>
            <div class="vendor-actions">
              <router-link :to="vendor.href" class="secondary">☷ View Vendor</router-link>
              <router-link :to="`${vendor.href}/review`" class="primary">✎ Write Review</router-link>
            </div>
          </article>
          <PaginationBlock :meta="vendorPagination" @page="setVendorPage" />
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Overall Rating</h2><div class="pv-big-rating">{{ vendorStats.average_rating }} <small>/5</small> <span class="pv-stars">★★★★★</span></div><p class="pv-muted">Based on {{ formatCount(vendorStats.total_reviews) }} reviews</p></article>
        <article class="pv-panel pv-filter-panel">
          <header class="pv-panel-header"><h2>Filter Vendors</h2><button class="pv-text-button" type="button" @click="clearVendorFilters">Clear all</button></header>
          <label class="pv-input-search">
            <input v-model="vendorSearch" placeholder="Search vendors..." type="search" @keyup.enter="applyVendorFilters">
            <PvIcon name="search" />
          </label>
          <label>Rating<select v-model="vendorRatingFilter"><option value="">All ratings</option><option v-for="rating in vendorFilterOptions.ratings" :key="rating" :value="String(rating)">{{ rating }}+ stars</option></select></label>
          <label>Status<select v-model="vendorStatusFilter"><option value="">All statuses</option><option v-for="status in vendorFilterOptions.statuses" :key="status.slug" :value="status.slug">{{ status.name }}</option></select></label>
          <label>Tag<select v-model="vendorTagFilter"><option value="">All tags</option><option v-for="tag in vendorFilterOptions.tags" :key="tag" :value="tag">{{ tag }}</option></select></label>
          <button class="pv-primary-button pv-full" type="button" @click="applyVendorFilters">Apply Filters</button>
        </article>
        <article class="pv-panel"><header class="pv-panel-header"><h2>Top Vendors</h2><router-link to="/vendor-reviews" class="pv-purple-link">View all</router-link></header><div class="pv-ranked-list"><router-link v-for="(vendor, index) in topVendors" :key="vendor.name" :to="vendor.href" class="pv-ranked-row"><span class="pv-rank">{{ index + 1 }}</span><span class="pv-vendor-logo"><img v-if="vendor.imageUrl" :src="vendor.imageUrl" :alt="vendor.name"><template v-else>{{ vendor.logo }}</template></span><strong>{{ vendor.name }}</strong><span class="pv-green-text"><PvIcon name="star" /> {{ vendor.rating }}</span></router-link></div></article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'vendorPortal'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div>
            <h1>Vendor Portal</h1>
            <p>Manage your approved vendor listing and public contact details.</p>
          </div>
          <router-link v-if="apiMyVendor && apiMyVendor.publishStatus === 'published'" :to="apiMyVendor.href" class="pv-small-button">
            View Public Profile <PvIcon name="chevron" />
          </router-link>
        </header>

        <article v-if="apiMyVendor" class="pv-panel pv-vendor-owner-card">
          <span class="pv-logo-card" :class="apiMyVendor.class"><img v-if="apiMyVendor.imageUrl" :src="apiMyVendor.imageUrl" :alt="apiMyVendor.name"><template v-else>{{ apiMyVendor.logoText }}</template></span>
          <span>
            <strong>{{ vendorPortalAccessApproved ? `You control ${apiMyVendor.name}` : `${apiMyVendor.name} is locked` }}</strong>
            <small>{{ apiMyVendor.publishStatus }} profile · {{ vendorPortalAccessApproved ? 'approved vendor' : 'vendor access disabled' }}</small>
          </span>
          <router-link v-if="apiMyVendor.publishStatus === 'published'" :to="apiMyVendor.href" class="pv-small-button">Open Profile</router-link>
        </article>

        <p v-if="vendorPortalStatusMessage" class="pv-alert pv-alert--compact">{{ vendorPortalStatusMessage }}</p>
        <p v-if="vendorPortalFormError" class="pv-alert pv-alert--compact">{{ vendorPortalFormError }}</p>

        <form v-if="vendorPortalAccessApproved" class="pv-form-card pv-vendor-portal-form" @submit.prevent="saveVendorProfile">
          <header class="pv-panel-header">
            <div>
              <h2>{{ apiMyVendor ? 'Edit Vendor Profile' : 'Set Up Vendor Profile' }}</h2>
              <p class="pv-muted">This information appears on your vendor review page for contact and support only.</p>
            </div>
            <span v-if="vendorPortalLoaded" class="pv-tag trusted">approved</span>
          </header>

          <div class="pv-two-col">
            <label>
               Vendor Name *
               <input v-model="vendorPortalForm.name" required maxlength="160" placeholder="Public vendor name">
             </label>
           </div>
          <label>
            About
            <textarea v-model="vendorPortalForm.description" maxlength="4000" rows="5" placeholder="Describe support expectations, service area, and public profile details."></textarea>
          </label>
          <label>
            Tags
            <input v-model="vendorPortalForm.tags" placeholder="Comma-separated tags, e.g. Domestic, Lab Tested">
          </label>

          <div class="pv-two-col">
            <label>
              Website
              <input v-model="vendorPortalForm.website_url" type="url" maxlength="255" placeholder="https://example.com">
            </label>
            <div class="pv-upload-control">
              <strong>Vendor Image</strong>
              <div class="pv-image-upload-row">
                <span class="pv-logo-card" :class="apiMyVendor?.class"><img v-if="vendorPortalForm.image_url" :src="assetUrl(vendorPortalForm.image_url)" alt="Vendor image preview"><template v-else>{{ apiMyVendor?.logoText ?? 'V' }}</template></span>
                <span>
                  <small>Upload a logo or product-safe vendor image up to 5 MB.</small>
                  <input ref="vendorImageFileInput" type="file" accept="image/*" class="pv-sr-only" @change="uploadVendorImage">
                  <button class="pv-small-button" type="button" :disabled="uploadingVendorImage" @click="vendorImageFileInput?.click()"><PvIcon name="upload" /> {{ uploadingVendorImage ? 'Uploading...' : 'Upload Image' }}</button>
                </span>
              </div>
            </div>
          </div>
          <div class="pv-two-col">
            <label>
              Support URL
              <input v-model="vendorPortalForm.support_url" type="url" maxlength="255" placeholder="https://example.com/support">
            </label>
            <label>
              Contact Email
              <input v-model="vendorPortalForm.contact_email" type="email" maxlength="255" placeholder="support@example.com">
            </label>
          </div>
          <div class="pv-two-col">
            <label>
              Telegram
              <input v-model="vendorPortalForm.contact_telegram" maxlength="120" placeholder="@handle">
            </label>
            <label>
              Signal
              <input v-model="vendorPortalForm.contact_signal" maxlength="120" placeholder="Signal username or number">
            </label>
          </div>
          <div class="pv-two-col">
            <label>
              Discord
              <input v-model="vendorPortalForm.contact_discord" maxlength="120" placeholder="Discord handle">
            </label>
          </div>
          <label>
            Response Policy
            <textarea v-model="vendorPortalForm.response_policy" maxlength="1000" rows="3" placeholder="Expected response times, support hours, and preferred contact channel."></textarea>
          </label>
          <label>
            Public Contact Notes
            <textarea v-model="vendorPortalForm.public_contact_notes" maxlength="1000" rows="3" placeholder="Anything members should know before contacting you."></textarea>
          </label>
          <footer class="pv-form-actions">
            <button class="pv-primary-button" type="submit" :disabled="savingVendorProfile">{{ savingVendorProfile ? 'Saving...' : apiMyVendor ? 'Save Profile' : 'Publish Profile' }}</button>
            <router-link v-if="apiMyVendor?.publishStatus === 'published'" :to="apiMyVendor.href" class="pv-small-button">Preview</router-link>
          </footer>
        </form>

        <article v-if="vendorPortalAccessApproved && apiMyVendor" class="pv-form-card pv-vendor-product-manager">
          <header class="pv-panel-header">
            <div>
              <h2>Product Catalog</h2>
              <p class="pv-muted">Add public product listings with prices. Members must contact you outside this site to purchase.</p>
            </div>
            <span class="pv-count">{{ apiMyVendor.productCount }} listed</span>
          </header>
          <p v-if="vendorProductStatusMessage" class="pv-alert pv-alert--compact">{{ vendorProductStatusMessage }}</p>
          <p v-if="vendorProductFormError" class="pv-alert pv-alert--compact">{{ vendorProductFormError }}</p>
          <form class="pv-product-form" @submit.prevent="saveVendorProduct">
            <div class="pv-two-col">
              <label>
                Product Name *
                <input v-model="vendorProductForm.name" required maxlength="160" placeholder="Retatrutide">
              </label>
              <label>
                Product Slug
                <input v-model="vendorProductForm.slug" maxlength="180" pattern="[a-zA-Z0-9-]+" placeholder="retatrutide">
              </label>
            </div>
            <div class="pv-two-col">
              <label>
                Category
                <input v-model="vendorProductForm.category" maxlength="80" placeholder="Peptide">
              </label>
              <label>
                Strength
                <input v-model="vendorProductForm.strength" maxlength="80" placeholder="10mg">
              </label>
            </div>
            <div class="pv-two-col">
              <label>
                Package Size
                <input v-model="vendorProductForm.package_size" maxlength="80" placeholder="1 vial">
              </label>
              <label>
                Purity / Notes
                <input v-model="vendorProductForm.purity_label" maxlength="80" placeholder=">98%">
              </label>
            </div>
            <label>
              Description
              <textarea v-model="vendorProductForm.description" maxlength="2000" rows="3" placeholder="Short public product description and research-use notes."></textarea>
            </label>
            <div class="pv-two-col">
              <label>
                Price
                <input v-model="vendorProductForm.price" inputmode="decimal" placeholder="85.00">
              </label>
              <label>
                Currency
                <input v-model="vendorProductForm.currency_code" maxlength="3" placeholder="USD">
              </label>
            </div>
            <fieldset class="pv-vendor-fieldset">
              <legend>Variants</legend>
              <p class="pv-muted" style="font-size:12px">When variants exist, each variant has its own price. The main price field becomes optional.</p>
              <div v-if="vendorProductForm.variants.length === 0" class="pv-muted" style="font-size:12px;padding:4px 0">No variants yet.</div>
              <div v-for="(variant, vi) in vendorProductForm.variants" :key="vi" class="pv-two-col" style="align-items:end">
                <label>
                  Label
                  <input v-model="variant.label" maxlength="80" placeholder="10mg">
                </label>
                <label>
                  Price
                  <input v-model="variant.price" inputmode="decimal" placeholder="50.00">
                </label>
                <label>
                  Availability
                  <select v-model="variant.availability">
                    <option value="in_stock">In stock</option>
                    <option value="limited">Limited</option>
                    <option value="out_of_stock">Out of stock</option>
                  </select>
                </label>
                <button type="button" class="pv-small-button pv-small-button--danger" @click="removeVendorProductVariant(vi)">Remove</button>
              </div>
              <button type="button" class="pv-small-button" @click="addVendorProductVariant">+ Add Variant</button>
            </fieldset>
            <div class="pv-two-col">
              <label>
                Availability
                <select v-model="vendorProductForm.availability">
                  <option value="in_stock">In stock</option>
                  <option value="limited">Limited</option>
                  <option value="out_of_stock">Out of stock</option>
                </select>
              </label>
              <label>
                Visibility
                <select v-model="vendorProductForm.status">
                  <option value="published">Published</option>
                  <option value="hidden">Hidden</option>
                </select>
              </label>
            </div>
            <label>
              Tags
              <input v-model="vendorProductForm.tags" placeholder="Comma-separated tags, e.g. GLP-1, Research">
            </label>
            <div class="pv-two-col">
              <label>
                Sort Order
                <input v-model="vendorProductForm.sort_order" inputmode="numeric" placeholder="0">
              </label>
              <div class="pv-upload-control">
                <strong>Product Image</strong>
                <div class="pv-image-upload-row">
                  <span class="pv-product-image-preview">
                    <img v-if="vendorProductImagePreview" :src="vendorProductImagePreview" alt="Product image preview">
                    <PvIcon v-else name="flask" />
                  </span>
                  <span>
                    <small>Upload a product-safe image up to 5 MB.</small>
                    <input ref="vendorProductImageInput" type="file" accept="image/*" class="pv-sr-only" @change="selectVendorProductImage">
                    <button class="pv-small-button" type="button" @click="vendorProductImageInput?.click()"><PvIcon name="upload" /> Choose Image</button>
                  </span>
                </div>
              </div>
            </div>
            <footer class="pv-form-actions">
              <button class="pv-primary-button" type="submit" :disabled="savingVendorProduct">{{ savingVendorProduct ? 'Saving...' : editingVendorProductId ? 'Update Product' : 'Add Product' }}</button>
              <button class="pv-small-button" type="button" @click="resetVendorProductForm">Clear</button>
            </footer>
          </form>
          <div class="pv-product-manage-list">
            <p v-if="vendorProductManageList.length === 0" class="pv-muted">No products listed yet.</p>
            <article v-for="product in vendorProductManageList" :key="product.id ?? product.slug" class="pv-product-manage-row">
              <span class="pv-product-thumb"><img v-if="product.imageUrl" :src="product.imageUrl" :alt="product.name"><PvIcon v-else name="flask" /></span>
              <span>
                <strong>{{ product.name }}</strong>
                <small>{{ product.category || 'Uncategorised' }} · {{ product.variants?.length ? product.variants.length + ' variants' : product.priceLabel || 'No price' }} · {{ product.availabilityLabel }} · {{ product.status }}</small>
              </span>
              <button class="pv-small-button" type="button" @click="editVendorProduct(product)">Edit</button>
              <button class="pv-small-button pv-small-button--danger" type="button" @click="deleteVendorProduct(product)">Delete</button>
            </article>
          </div>
        </article>

        <article v-if="vendorPortalAccessApproved && apiMyVendor" class="pv-form-card pv-vendor-document-manager">
          <header class="pv-panel-header">
            <div>
              <h2>Documents</h2>
              <p class="pv-muted">Upload COAs, quality reports, and other supporting documents. These will be publicly visible once published.</p>
            </div>
            <span class="pv-count">{{ apiMyVendor.documents.length }} uploaded</span>
          </header>
          <p v-if="vendorDocumentStatusMessage" class="pv-alert pv-alert--compact">{{ vendorDocumentStatusMessage }}</p>
          <p v-if="vendorDocumentFormError" class="pv-alert pv-alert--compact">{{ vendorDocumentFormError }}</p>
          <form class="pv-document-form" @submit.prevent="saveVendorDocument">
            <div class="pv-two-col">
              <label>
                Document Title *
                <input v-model="vendorDocumentForm.title" required maxlength="200" placeholder="Certificate of Analysis - Batch 001">
              </label>
              <label>
                Category
                <select v-model="vendorDocumentForm.category">
                  <option value="">Select category</option>
                  <option value="coa">Certificate of Analysis (COA)</option>
                  <option value="quality">Quality Report</option>
                  <option value="other">Other</option>
                </select>
              </label>
            </div>
            <label>
              Description
              <textarea v-model="vendorDocumentForm.description" maxlength="1000" rows="2" placeholder="Brief description of the document..."></textarea>
            </label>
            <label>
              File (PDF or image, max 10 MB) *
              <input ref="vendorDocumentFileInput" type="file" accept=".pdf,image/*" class="pv-sr-only" @change="selectVendorDocumentFile">
              <div class="pv-upload-box" role="button" @click="vendorDocumentFileInput?.click()">
                <PvIcon name="upload" />
                <span>{{ vendorDocumentFilePreview ? vendorDocumentFilePreview : 'Click to choose a file' }}</span>
                <small>PDF, PNG, JPG, WebP up to 10 MB</small>
              </div>
            </label>
            <footer class="pv-form-actions">
              <button class="pv-primary-button" type="submit" :disabled="savingVendorDocument">
                {{ savingVendorDocument ? 'Uploading...' : 'Upload Document' }}
              </button>
              <button class="pv-small-button" type="button" @click="resetVendorDocumentForm">Clear</button>
            </footer>
          </form>
          <div class="pv-doc-manage-list">
            <p v-if="apiMyVendor.documents.length === 0" class="pv-muted">No documents uploaded yet.</p>
            <article v-for="doc in documentManageList" :key="doc.id" class="pv-doc-manage-row">
              <span class="pv-doc-icon">
                <PvIcon :name="doc.fileType === 'image' ? 'image' : 'document'" />
              </span>
              <span>
                <strong>{{ doc.title }}</strong>
                <small>
                  {{ doc.category ? doc.category.toUpperCase() + ' · ' : '' }}
                  {{ doc.fileType.toUpperCase() }}
                  {{ doc.description ? ' · ' + doc.description : '' }}
                </small>
              </span>
              <a :href="doc.url" target="_blank" rel="noreferrer" class="pv-small-button">View</a>
              <button class="pv-small-button pv-small-button--danger" type="button" @click="deleteVendorDocument(doc)">Delete</button>
            </article>
          </div>
        </article>

         <article v-else-if="!vendorPortalAccessApproved" class="pv-panel">
           <h2>Vendor Access Required</h2>
           <p class="pv-muted">Read the <router-link to="/discussions/vendor-application-process" class="pv-purple-link">Vendor Application Process</router-link> first, then follow the steps below.</p>
           <dl class="pv-data-list">
             <div><dt>Account</dt><dd>{{ authStore.user?.username || authStore.user?.name || 'Current user' }}</dd></div>
             <div><dt>Vendor Access</dt><dd>Not approved</dd></div>
             <div v-if="apiMyVendor"><dt>Existing Profile</dt><dd>{{ apiMyVendor.publishStatus }}</dd></div>
           </dl>
           <button
             v-if="!vendorAccessRequested"
             class="pv-primary-button pv-full"
             style="margin-top:12px"
             @click="requestVendorAccess"
           >
             Request Vendor Access
           </button>
           <button
             v-else
             class="pv-primary-button pv-full"
             style="margin-top:12px"
             disabled
           >
             Requested
           </button>
           <a :href="publicTelegramUrl" target="_blank" rel="noreferrer" class="pv-small-button pv-full" style="margin-top:8px"><PvIcon name="send" /> Contact Admin on Telegram</a>
         </article>
      </main>

      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Vendor Access</h2>
          <dl class="pv-data-list">
            <div><dt>Status</dt><dd>{{ vendorPortalAccessApproved ? 'Approved' : 'Not approved' }}</dd></div>
            <div><dt>Profile</dt><dd>{{ apiMyVendor ? apiMyVendor.publishStatus : 'Not created' }}</dd></div>
          </dl>
          <button v-if="authStore.user?.roles?.includes('admin') && !vendorPortalAccessApproved" class="pv-primary-button pv-full" style="margin-top:12px" @click="approveVendorAccess">Approve Vendor Access</button>
        </article>

        <article class="pv-panel">
          <h2>What Vendors Can Control</h2>
          <ul class="pv-check-list">
            <li>Approved vendors can edit public profile text and tags</li>
            <li>Approved vendors can maintain support links and contact channels</li>
            <li>Vendor images, websites, and support links are public</li>
            <li>No sales, checkout, payments, or order handling</li>
          </ul>
        </article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'vendorDetail' || page === 'reviewModal'" class="pv-page">
    <div v-if="detailVendor" class="pv-content-grid pv-content-grid--vendor-detail">
      <main class="pv-stack">
        <router-link to="/vendor-reviews" class="op-back">← Back to Vendors</router-link>
        <article class="vendor-card vendor-card--hero">
          <div v-if="detailVendor.imageUrl" class="vendor-hero-bg" :style="{ backgroundImage: `url(${detailVendor.imageUrl})` }"></div>
          <div class="vendor-top">
            <span v-if="detailVendor.imageUrl" class="vendor-logo"><img :src="detailVendor.imageUrl" :alt="detailVendor.name"></span>
            <span v-else class="vendor-logo vendor-logo--letter">{{ detailVendor.logoText }}</span>
            <div class="vendor-info">
              <h3>{{ detailVendor.name }}</h3>
              <span class="trusted">✓ {{ detailVendor.status }}</span>
              <span v-if="detailVendor.country" class="country-badge">{{ countryFlag(detailVendor.country) }} {{ detailVendor.country }}</span>
              <p class="vendor-meta">☆ {{ detailVendor.rating }} / 5 · {{ detailVendor.reviews }} {{ detailVendor.reviews === 1 ? 'review' : 'reviews' }} · Member since {{ detailVendor.since }}</p>
              <div class="vendor-tags" style="margin-top:8px">
                <span v-for="chip in detailVendor.chips" :key="chip">{{ chip }}</span>
              </div>
            </div>
          </div>
          <div class="vendor-actions" style="margin-top:16px">
            <router-link :to="`${detailVendor.href}/review`" class="primary">✎ Write Review</router-link>
            <button v-if="hasVendorContact(detailVendor)" class="secondary" @click="showVendorContactSection">Contact</button>
            <router-link v-if="detailVendor.isOwnedByViewer" to="/vendor-portal" class="secondary">Manage</router-link>
            <a v-if="detailVendor.websiteUrl" :href="detailVendor.websiteUrl" target="_blank" rel="noreferrer" class="secondary">Website</a>
          </div>
        </article>
        <div class="pv-tabs pv-tabs--line"><button :class="{ active: vendorDetailTab === 'overview' }" @click="vendorDetailTab = 'overview'">Overview</button><button :class="{ active: vendorDetailTab === 'reviews' }" @click="vendorDetailTab = 'reviews'">Reviews ({{ detailVendor.reviews }})</button><button :class="{ active: vendorDetailTab === 'products' }" @click="vendorDetailTab = 'products'">Products ({{ detailVendor.productCount }})</button><button :class="{ active: vendorDetailTab === 'documents' }" @click="vendorDetailTab = 'documents'">Documents ({{ detailVendor.documents.length }})</button><button :class="{ active: vendorDetailTab === 'about' }" @click="vendorDetailTab = 'about'">About</button></div>
        <article v-if="vendorDetailTab === 'overview'" class="pv-panel pv-review-summary">
          <div class="pv-score-block"><strong>{{ detailVendor.rating }}</strong><span class="pv-stars">★★★★★</span><small>{{ detailVendor.reviews }} reviews</small></div>
          <div class="pv-bars pv-bars--wide"><span v-for="row in detailVendor.ratingDistribution" :key="row.rating">{{ row.rating }} stars <b :style="{ '--w': `${row.percent}%` }"></b><em>{{ row.percent }}% ({{ row.count }})</em></span></div>
          <div class="pv-rate-box"><h3>Rate this vendor</h3><p>Share your experience to help others in the community.</p><div class="pv-stars pv-stars--big">☆☆☆☆☆</div><router-link :to="`${detailVendor.href}/review`" class="pv-primary-button pv-full">Write a Review</router-link></div>
        </article>
        <template v-if="vendorDetailTab === 'products'">
          <article class="pv-panel pv-product-catalog-panel">
            <header class="pv-panel-header">
              <div>
                <h2>Product Catalog</h2>
                <p class="pv-muted">{{ detailVendor.productCount }} products listed. Purchases happen off-site through vendor contact only.</p>
              </div>
              <button v-if="hasVendorContact(detailVendor)" class="pv-small-button" type="button" @click="showVendorContactSection"><PvIcon name="mail" /> Contact Vendor</button>
            </header>
            <div class="pv-toolbar pv-product-toolbar">
              <label class="pv-input-search">
                <input v-model="vendorProductSearch" placeholder="Search products..." type="search">
                <PvIcon name="search" />
              </label>
              <label class="pv-compact-select">Category<select v-model="vendorProductCategoryFilter"><option value="">All Categories</option><option v-for="category in vendorProductCategoryOptions" :key="category" :value="category">{{ category }}</option></select></label>
              <label class="pv-compact-select">Availability<select v-model="vendorProductAvailabilityFilter"><option value="">All Availability</option><option value="in_stock">In stock</option><option value="limited">Limited</option><option value="out_of_stock">Out of stock</option></select></label>
              <label class="pv-compact-select">Sort<select v-model="vendorProductSort"><option value="featured">Featured</option><option value="price-low">Price low to high</option><option value="price-high">Price high to low</option><option value="name">Name A-Z</option></select></label>
            </div>
            <p v-if="actionStatusMessage" class="pv-alert pv-alert--compact">{{ actionStatusMessage }}</p>
            <p v-if="filteredVendorProducts.length === 0" class="pv-muted">No public products match these filters.</p>
            <div class="pv-product-grid">
              <article v-for="product in filteredVendorProducts" :key="product.id ?? product.slug" class="pv-product-card">
                <header class="pv-product-card-top">
                  <span class="pv-product-stock" :class="product.availability === 'out_of_stock' ? 'avoid' : product.availability === 'limited' ? 'caution' : 'trusted'"><i></i>{{ product.availabilityLabel }}</span>
                  <button class="pv-product-save-button" :class="{ active: isBookmarkedProduct(product) }" type="button" :aria-label="isBookmarkedProduct(product) ? 'Remove product bookmark' : 'Bookmark product'" @click="toggleProductBookmark(product)">
                    <PvIcon name="bookmark" />
                  </button>
                </header>
                <div class="pv-product-card-body">
                  <span class="pv-product-card-image"><img v-if="product.imageUrl" :src="product.imageUrl" :alt="product.name"><PvIcon v-else name="flask" /></span>
                  <div class="pv-product-card-info">
                    <span class="pv-product-kind">{{ product.category || 'Product' }}</span>
                    <h3>{{ product.name }}</h3>
                    <div class="pv-product-specs">
                      <span><PvIcon name="flask" /><strong>{{ product.strength || 'Ask' }}</strong><small>Strength</small></span>
                      <span><PvIcon name="box" /><strong>{{ product.packageSize || 'Ask' }}</strong><small>Quantity</small></span>
                      <span><PvIcon name="tag" /><strong>{{ product.category || 'Product' }}</strong><small>Type</small></span>
                    </div>
                    <p v-if="product.description" class="pv-product-description">{{ product.description }}</p>
                    <div class="pv-product-variant-tiles">
                      <span v-for="(variant, index) in product.variants" :key="`${variant.label}-${index}`" :class="{ active: index === 0 }" :title="`${variant.label} ${variantPrice(variant)}`">
                        <strong>{{ variant.label }}</strong>
                        <em>{{ variantPrice(variant) }}</em>
                      </span>
                      <span v-if="!product.variants.length" class="active">
                        <strong>{{ product.strength || product.packageSize || 'Listing' }}</strong>
                        <em>{{ product.priceLabel || 'Contact for price' }}</em>
                      </span>
                    </div>
                    <span class="pv-product-review-note"><PvIcon name="star" /><template v-if="product.reviews > 0">{{ product.ratingLabel }} / 5 · {{ product.reviews }} {{ product.reviews === 1 ? 'review' : 'reviews' }}</template><template v-else>No product reviews yet</template></span>
                    <span v-if="product.tags.length" class="pv-chip-row pv-product-chip-row"><span v-for="tag in product.tags.slice(0, 4)" :key="tag">{{ tag }}</span></span>
                  </div>
                </div>
                <footer class="pv-product-card-footer">
                  <strong>{{ productCatalogPriceLabel(product) }}</strong>
                  <span><PvIcon name="shield" /> Verified listing</span>
                  <button class="pv-primary-button" type="button" @click="showVendorContactSection"><PvIcon name="mail" /> Contact Vendor</button>
                </footer>
                <div class="pv-product-trust-strip">
                  <span><PvIcon name="mail" /><strong>Direct Contact</strong><small>No on-site checkout</small></span>
                  <span><PvIcon name="shield" /><strong>Trusted Vendor</strong><small>Reviewed profile</small></span>
                  <span><PvIcon name="lock" /><strong>Research Use Only</strong><small>Not for human use</small></span>
                </div>
              </article>
            </div>
          </article>
          <article class="pv-alert pv-alert--compact"><PvIcon name="shield" /> Product listings are informational only. This site does not process orders, carts, payments, shipping, refunds, or transactions.</article>
        </template>
        <template v-if="vendorDetailTab === 'documents'">
          <article class="pv-panel pv-doc-list-panel">
            <header class="pv-panel-header">
              <div>
                <h2>Documents</h2>
                <p class="pv-muted">Certificates of analysis, quality reports, and other supporting documents from {{ detailVendor.name }}.</p>
              </div>
            </header>
            <p v-if="detailVendor.documents.length === 0" class="pv-muted">No documents have been published yet.</p>
            <div v-else class="pv-doc-list">
              <article v-for="doc in detailVendor.documents" :key="doc.id" class="pv-doc-row">
                <span class="pv-doc-type-icon">
                  <PvIcon :name="doc.fileType === 'image' ? 'image' : 'document'" />
                </span>
                <div class="pv-doc-info">
                  <strong>{{ doc.title }}</strong>
                  <span class="pv-doc-meta">
                    <span v-if="doc.category" class="pv-tag" :class="doc.category === 'coa' ? 'trusted' : 'caution'">{{ doc.category.toUpperCase() }}</span>
                    <span>{{ doc.fileType.toUpperCase() }}</span>
                    <span v-if="doc.description">{{ doc.description }}</span>
                  </span>
                </div>
                <a :href="doc.url" target="_blank" rel="noreferrer" class="pv-primary-button">
                  <PvIcon name="download" /> Download
                </a>
              </article>
            </div>
          </article>
        </template>
        <template v-if="vendorDetailTab === 'reviews'">
          <div class="pv-toolbar pv-vendor-review-toolbar">
            <label class="pv-compact-select">Rating<select v-model="vendorReviewRatingFilter"><option value="">All Ratings</option><option v-for="rating in [5, 4, 3, 2, 1]" :key="rating" :value="String(rating)">{{ rating }} stars</option></select></label>
            <label class="pv-compact-select">Product<select v-model="vendorReviewProductFilter"><option value="">All Products</option><option v-for="product in vendorReviewProductOptions" :key="product" :value="product">{{ product }}</option></select></label>
            <label class="pv-compact-select">When<select v-model="vendorReviewTimeFilter"><option value="all">All Time</option><option value="recent">Recent First</option></select></label>
            <span class="pv-flex-spacer"></span>
            <button class="pv-small-button" type="button" @click="toggleVendorReviewSort">{{ vendorReviewSortLabel }} <PvIcon name="chevron" /></button>
          </div>
          <p v-if="vendorReviewStatusMessage" class="pv-alert pv-alert--compact">{{ vendorReviewStatusMessage }}</p>
          <p v-if="reviews.length === 0" class="pv-muted">No published reviews yet.</p>
          <article v-for="review in reviews" :key="review.id ?? review.author" class="pv-review-row">
            <span class="pv-avatar" :class="review.color">{{ review.initial }}</span>
            <div>
              <div class="pv-reply-head"><strong>{{ review.author }}</strong><span class="pv-tag" :class="review.verifiedBuyer ? 'trusted' : 'caution'">{{ review.verifiedBuyer ? 'Verified Buyer' : 'Community Review' }}</span><span>{{ review.date }}</span></div>
              <span class="pv-stars">★★★★★ <em>{{ review.rating }}/5</em></span>
              <h3>{{ review.title }}</h3>
              <p>{{ review.text }}</p>
              <span class="pv-chip-row"><span v-for="chip in review.chips" :key="chip">{{ chip }}</span></span>
              <div v-if="review.photoUrls.length" class="pv-review-photo-grid">
                <a v-for="photo in review.photoUrls" :key="photo" :href="photo" target="_blank" rel="noreferrer">
                  <img :src="photo" alt="Review photo">
                </a>
              </div>
              <div v-if="review.vendorResponse" class="pv-vendor-response">
                <strong>Vendor Response</strong>
                <p>{{ review.vendorResponse }}</p>
                <small v-if="review.respondedAt">Responded {{ formatDate(review.respondedAt) }}</small>
              </div>
              <div v-if="detailVendor?.isOwnedByViewer && !review.vendorResponse && respondingReviewId !== review.id" style="margin-top:8px">
                <button class="pv-small-button" type="button" @click="respondingReviewId = review.id; reviewResponseText = ''">Respond</button>
              </div>
              <div v-if="respondingReviewId === review.id" class="pv-vendor-respond-form">
                <textarea v-model="reviewResponseText" maxlength="2000" rows="3" placeholder="Write your public response..." class="pv-vendor-response-textarea"></textarea>
                <div style="display:flex;gap:6px;margin-top:6px">
                  <button class="pv-primary-button" :disabled="submittingReviewResponse" @click="respondToReview(review)">{{ submittingReviewResponse ? 'Posting...' : 'Submit Response' }}</button>
                  <button class="pv-small-button" type="button" @click="cancelVendorResponse">Cancel</button>
                </div>
              </div>
            </div>
            <button class="pv-small-button" :disabled="markingReviewHelpful === review.id" @click="markReviewHelpful(review)"><PvIcon name="thumbs" /> Helpful ({{ review.helpful }})</button>
          </article>
        </template>
        <article v-if="vendorDetailTab === 'about'" class="pv-panel">
          <h2>About {{ detailVendor.name }}</h2>
          <p class="pv-muted">{{ detailVendor.description }}</p>
          <dl class="pv-data-list">
            <div><dt>Status</dt><dd><span class="pv-tag" :class="detailVendor.statusClass">{{ detailVendor.status }}</span></dd></div>
            <div><dt>Member Since</dt><dd>{{ detailVendor.since }}</dd></div>
            <div><dt>Last Active</dt><dd>{{ detailVendor.lastActive }}</dd></div>
            <div><dt>Response Rate</dt><dd>{{ detailVendor.responseRate }}</dd></div>
            <div><dt>Average Response</dt><dd>{{ detailVendor.avgResponseTime }}</dd></div>
          </dl>
          <div v-if="hasVendorContact(detailVendor)" class="pv-contact-list">
            <a v-for="link in detailVendorContactLinks" :key="`${link.label}-${link.value}`" :href="link.href" target="_blank" rel="noreferrer" class="pv-contact-row">
              <PvIcon :name="link.icon" />
              <span><strong>{{ link.label }}</strong><small>{{ link.value }}</small></span>
              <small class="pv-open-link">Open</small>
            </a>
            <p v-if="detailVendor.contact.responsePolicy" class="pv-muted"><strong>Response policy:</strong> {{ detailVendor.contact.responsePolicy }}</p>
            <p v-if="detailVendor.contact.publicNotes" class="pv-muted">{{ detailVendor.contact.publicNotes }}</p>
          </div>
          <a v-if="detailVendor.websiteUrl" :href="detailVendor.websiteUrl" target="_blank" rel="noreferrer" class="pv-small-button">Visit Website <PvIcon name="share" /></a>
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Vendor Summary</h2><dl class="pv-data-list"><div><dt>Total Reviews</dt><dd>{{ detailVendor.reviews }}</dd></div><div><dt>Average Rating</dt><dd><span class="pv-stars">★</span> {{ detailVendor.rating }} / 5</dd></div><div><dt>Would Buy Again</dt><dd>{{ detailVendor.buyAgain }}</dd></div><div><dt>Response Rate</dt><dd>{{ detailVendor.responseRate }}</dd></div><div><dt>Avg. Response Time</dt><dd>{{ detailVendor.avgResponseTime }}</dd></div></dl></article>
        <article v-if="hasVendorContact(detailVendor)" class="pv-panel">
          <h2>Contact & Support</h2>
          <div class="pv-contact-list">
            <a v-for="link in detailVendorContactLinks" :key="`${link.label}-sidebar-${link.value}`" :href="link.href" target="_blank" rel="noreferrer" class="pv-contact-row">
              <PvIcon :name="link.icon" />
              <span><strong>{{ link.label }}</strong><small>{{ link.value }}</small></span>
              <small class="pv-open-link">Open</small>
            </a>
          </div>
          <p v-if="detailVendor.contact.responsePolicy" class="pv-muted">{{ detailVendor.contact.responsePolicy }}</p>
          <p v-if="detailVendor.contact.publicNotes" class="pv-muted">{{ detailVendor.contact.publicNotes }}</p>
          <p class="pv-muted">Vendor contact details are for support and profile verification context only.</p>
        </article>
        <article class="pv-panel"><h2>Top Products</h2><div class="pv-ranked-list"><span v-for="product in detailVendor.topProducts" :key="product.slug || product.name" class="pv-ranked-row"><span class="pv-vendor-logo"><img v-if="product.imageUrl" :src="product.imageUrl" :alt="product.name"><PvIcon v-else name="flask" /></span><strong>{{ product.name }}</strong><span>{{ product.priceLabel || product.availabilityLabel }}</span></span></div><button class="pv-small-button pv-full" @click="vendorDetailTab = 'products'">View all products</button></article>
        <article class="pv-panel"><h2>Active Discussions</h2><div class="pv-mini-list"><router-link v-for="topic in discussions.slice(0, 3)" :key="topic.title" :to="topic.href" class="pv-mini-row"><PvIcon name="discussions" /><span><strong>{{ topic.title }}</strong></span><span>{{ topic.replies }}</span></router-link></div><router-link class="pv-small-button pv-full" to="/discussions">View all discussions</router-link></article>
        <article class="pv-panel"><h2>About {{ detailVendor.name }}</h2><p class="pv-muted">{{ detailVendor.description }}</p><a v-if="detailVendor.websiteUrl" :href="detailVendor.websiteUrl" target="_blank" rel="noreferrer" class="pv-small-button">Visit Website <PvIcon name="share" /></a></article>
      </aside>
    </div>
    <div v-else class="pv-empty-route">
      <h1>Vendor not found</h1>
      <p>This vendor has not been published or does not exist.</p>
    </div>
    <div v-if="page === 'reviewModal'" class="pv-modal-backdrop">
      <form v-if="detailVendor" class="pv-modal" @submit.prevent="submitVendorReview">
        <header class="pv-panel-header"><div><h2>Write a Review</h2><p class="pv-muted">Reviewing {{ detailVendor.name }}. Share your experience to help others in the community.</p></div><router-link :to="detailVendor.href" class="pv-icon-button pv-icon-button--static"><PvIcon name="close" /></router-link></header>
        <p v-if="vendorReviewFormError" class="pv-alert pv-alert--compact">{{ vendorReviewFormError }}</p>
         <label>Overall Rating *
           <div class="pv-star-rating">
             <button type="button" v-for="star in 5" :key="star" :class="{ active: star <= newVendorReview.rating }" @click="newVendorReview.rating = star">★</button>
           </div>
         </label>
        <label>Review Title *<input v-model="newVendorReview.title" required maxlength="160" placeholder="Summarise your experience in a few words..."><small>{{ newVendorReview.title.length }}/160</small></label>
        <label>Your Review *
  <div class="pv-textarea-with-emoji">
    <textarea v-model="newVendorReview.body" required maxlength="4000" placeholder="Tell others about your experience with this vendor. Include details about product quality, shipping, customer service, packaging, etc."></textarea>
    <EmojiPicker v-model="newVendorReview.body" />
  </div>
  <small>{{ newVendorReview.body.length }}/4000</small></label>
         <div><strong>Would you buy from this vendor again?</strong><div class="pv-choice-row"><button type="button" :class="{ active: newVendorReview.would_buy_again }" @click="newVendorReview.would_buy_again = true"><PvIcon name="thumbs" /> Yes, I would</button><button type="button" :class="{ active: !newVendorReview.would_buy_again }" @click="newVendorReview.would_buy_again = false">No, I wouldn&apos;t</button></div></div>
         <label>Review Photos *
           <p class="pv-muted" style="font-size:13px">You must include a photo of the received product with a handwritten note showing your <strong>username</strong> and the <strong>date received</strong>. Cover any personal info before uploading.</p>
           <input ref="vendorReviewPhotoInput" type="file" accept="image/*" multiple class="pv-sr-only" @change="selectVendorReviewPhotos">
           <button class="pv-upload-box" type="button" @click="vendorReviewPhotoInput?.click()"><PvIcon name="image" /> Click to upload photos<br><small>PNG, JPG up to 5MB each (min 1 photo required)</small></button>
         </label>
        <div v-if="vendorReviewPhotoPreviews.length" class="pv-upload-preview-grid">
          <span v-for="(preview, index) in vendorReviewPhotoPreviews" :key="preview">
            <img :src="preview" :alt="`Review photo ${index + 1}`">
            <button type="button" aria-label="Remove photo" @click="removeVendorReviewPhoto(index)"><PvIcon name="close" /></button>
          </span>
        </div>
        <footer><router-link :to="detailVendor.href" class="pv-small-button">Cancel</router-link><button class="pv-primary-button" type="submit" :disabled="submittingVendorReview">{{ submittingVendorReview ? 'Submitting...' : 'Submit Review' }}</button></footer>
      </form>
    </div>
  </section>

  <section v-else-if="page === 'researchLibrary'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header"><div><h1>Research Library</h1><p>Explore research, studies, and educational resources on peptides and performance compounds.</p></div></header>
        <div class="pv-tabs"><button :class="{ active: activeResearchCategory === '' }" @click="activeResearchCategory = ''; loadResearchContent()">All</button><button v-for="category in contentCategories.research" :key="category.slug" :class="{ active: activeResearchCategory === category.slug }" @click="activeResearchCategory = category.slug; loadResearchContent()">{{ category.name }}</button></div>
        <div class="pv-toolbar"><label class="pv-input-search"><input v-model="researchSearch" placeholder="Search articles, compounds, topics..." @input="loadResearchContent"><PvIcon name="search" /></label><button class="pv-small-button" @click="cycleResearchSort">{{ researchSortLabel }} <PvIcon name="chevron" /></button><span class="pv-icon-button active pv-mode-indicator" aria-label="Library view"><PvIcon name="library" /></span><button class="pv-icon-button pv-icon-button--static" @click="loadResearchContent"><PvIcon name="filter" /></button></div>
        <p v-if="contentStatusMessage" class="pv-form-error">{{ contentStatusMessage }}</p>
        <p v-if="contentLoaded && articles.length === 0" class="pv-muted">No research articles published yet.</p>
        <div class="pv-article-grid">
           <router-link v-for="(article, index) in articles" :key="article.title" :to="article.href" class="pv-article-card">
              <span class="pv-thumb" :style="contentThumbnailStyle(article, index)"></span>
              <span class="pv-tag">{{ article.category }}</span>
              <span class="pv-tag" :class="{ trusted: article.authorBadge === 'FDA Approved' }">{{ article.authorBadge }}</span>
             <h2>{{ article.title }}</h2>
             <p>{{ article.excerpt }}</p>
            <footer><span><PvIcon name="calendar" /> {{ article.date }}</span><span><PvIcon name="eye" /> {{ article.views }}</span></footer>
            <PvIcon name="bookmark" class="pv-bookmark" :class="{ active: isBookmarkedContent(article.slug) }" />
          </router-link>
        </div>
        <PaginationBlock :meta="researchPagination" @page="setResearchPage" />
      </main>
      <aside class="pv-stack">
        <article class="pv-panel pv-filter-panel">
          <header class="pv-panel-header"><h2>Filters</h2><button class="pv-text-button" type="button" @click="clearResearchFilters">Clear all</button></header>
          <label class="pv-input-search">
            <input v-model="researchSearch" placeholder="Search research library..." type="search" @keyup.enter="applyResearchFilters">
            <PvIcon name="search" />
          </label>
          <label>Category<select v-model="activeResearchCategory"><option value="">All categories</option><option v-for="category in contentFilterOptions.research.categories" :key="category.slug" :value="category.slug">{{ category.name }}</option></select></label>
          <label>Compound<select v-model="researchCompoundFilter"><option value="">All compounds</option><option v-for="compound in contentFilterOptions.research.compounds" :key="compound" :value="compound">{{ compound }}</option></select></label>
          <label>Tag<select v-model="researchTagFilter"><option value="">All tags</option><option v-for="tag in contentFilterOptions.research.tags" :key="tag" :value="tag">{{ tag }}</option></select></label>
          <label>Sort By<select v-model="researchSort"><option v-for="sort in contentFilterOptions.research.sorts" :key="sort.value" :value="sort.value">{{ sort.label }}</option></select></label>
          <label>Published From<input v-model="researchPublishedFrom" :min="contentFilterOptions.research.date_bounds?.from ?? undefined" :max="contentFilterOptions.research.date_bounds?.to ?? undefined" type="date"></label>
          <label>Published To<input v-model="researchPublishedTo" :min="contentFilterOptions.research.date_bounds?.from ?? undefined" :max="contentFilterOptions.research.date_bounds?.to ?? undefined" type="date"></label>
          <button class="pv-primary-button pv-full" type="button" @click="applyResearchFilters">Apply Filters</button>
        </article>
        <article class="pv-panel"><h2>Popular Topics</h2><dl class="pv-data-list"><div v-for="topic in popularTopics" :key="topic.name"><dt>{{ topic.name }}</dt><dd>{{ topic.count }}</dd></div></dl><button class="pv-purple-link" type="button" @click="clearResearchFilters">View all topics -></button></article>
        <article class="pv-panel"><h2>About the Library</h2><p class="pv-muted">Our research library is curated from credible sources and community contributions. Always do your own research and consult a professional.</p><router-link class="pv-purple-link" to="/guides">Submission Guidelines -></router-link></article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'researchArticle'" class="pv-page">
    <div v-if="detailArticle" class="pv-content-grid">
      <main class="pv-stack">
        <nav class="pv-breadcrumbs">Research Library <PvIcon name="chevron" /> {{ detailArticle.category }} <PvIcon name="chevron" /> {{ detailArticle.title }}</nav>
        <article class="pv-article-hero">
          <div><span class="pv-tag">{{ detailArticle.tag }}</span><h1>{{ detailArticle.title }}</h1><p>{{ detailArticle.excerpt }}</p><div class="pv-author-line"><span class="pv-avatar purple">{{ detailArticle.authorInitial }}</span><strong>{{ detailArticle.author }}</strong><span v-if="detailArticle.authorBadge" class="pv-tag trusted">{{ detailArticle.authorBadge }}</span><span>{{ detailArticle.date }}</span><span><PvIcon name="eye" /> {{ detailArticle.timeLabel }} read</span></div></div>
          <span class="pv-article-image" :style="contentThumbnailStyle(detailArticle)"></span>
        </article>
        <div class="pv-tabs pv-tabs--line"><button :class="{ active: researchDetailTab === 'article' }" @click="researchDetailTab = 'article'">Article</button><button :class="{ active: researchDetailTab === 'data' }" @click="researchDetailTab = 'data'">Figures & Data</button><button :class="{ active: researchDetailTab === 'references' }" @click="researchDetailTab = 'references'">References</button><button :class="{ active: researchDetailTab === 'comments' }" @click="researchDetailTab = 'comments'">Comments ({{ detailArticle.comments }})</button></div>
        <article v-if="researchDetailTab === 'article'" class="pv-prose">
          <template v-for="block in articleBodyBlocks" :key="block.text">
            <h2 v-if="block.kind === 'heading'">{{ block.text }}</h2>
            <ul v-else-if="block.kind === 'list'"><li v-for="item in block.items" :key="item">{{ item }}</li></ul>
            <p v-else>{{ block.text }}</p>
          </template>
        </article>
        <article v-else-if="researchDetailTab === 'data'" class="pv-panel"><h2>Figures & Data</h2><dl class="pv-data-list"><div v-for="(value, key) in detailArticle.metadata" :key="key"><dt>{{ formatMetadataKey(key) }}</dt><dd>{{ formatMetadataValue(value) }}</dd></div></dl></article>
        <article v-else-if="researchDetailTab === 'references'" class="pv-panel"><h2>References</h2><p class="pv-muted">{{ detailArticle.metadata.references ?? 'No references were attached to this article.' }}</p></article>
        <article v-else class="pv-panel"><h2>Comments</h2><p class="pv-muted">Discussion comments are not attached to this article yet. Start a related topic in the community discussions.</p><router-link to="/discussions" class="pv-primary-button">Open Discussions</router-link></article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Quick Info</h2><dl class="pv-data-list"><div><dt>Category</dt><dd>{{ detailArticle.category }}</dd></div><div><dt>Compound</dt><dd>{{ detailArticle.metadata.compound ?? detailArticle.title }}</dd></div><div v-if="detailArticle.metadata.fda_approved !== undefined"><dt>FDA Status</dt><dd>{{ detailArticle.metadata.fda_approved ? 'FDA Approved' : 'Research Only' }}</dd></div><div><dt>Views</dt><dd>{{ detailArticle.views }}</dd></div><div><dt>Read Time</dt><dd>{{ detailArticle.timeLabel }}</dd></div></dl></article>
        <article class="pv-panel"><h2>Table of Contents</h2><ol class="pv-toc"><li v-for="heading in articleHeadings" :key="heading" :class="{ active: heading === articleHeadings[0] }" @click="scrollToHeading(heading)">{{ heading }}</li></ol></article>
        <article class="pv-panel"><h2>Related Articles</h2><div class="pv-mini-list"><router-link v-for="(article, index) in relatedArticles" :key="article.title" :to="article.href" class="pv-mini-row"><span class="pv-mini-thumb" :style="contentThumbnailStyle(article, index + 1)"></span><span><strong>{{ article.title }}</strong><small>{{ article.date }}</small></span></router-link></div><router-link to="/research-library" class="pv-primary-button pv-full">View all related articles</router-link></article>
      </aside>
    </div>
    <div v-else class="pv-empty-route"><h1>Research article not found</h1><p>This article has not been published or does not exist.</p></div>
  </section>

  <section v-else-if="page === 'guides'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header"><div><h1>Guides & FAQ</h1><p>Helpful guides, tutorials and answers to common questions from the community.</p></div></header>
        <label class="pv-input-search pv-input-search--wide"><input v-model="guideSearch" placeholder="Search guides & FAQ..." @keydown.enter="loadGuidesContent"><PvIcon name="search" /></label>
        <div class="pv-guide-cats"><button :class="{ active: activeGuideCategory === '' }" @click="activeGuideCategory = ''; loadGuidesContent()"><PvIcon name="library" /><strong>All Topics</strong><small>View all</small></button><button v-for="category in contentCategories.guide" :key="category.slug" :class="{ active: activeGuideCategory === category.slug }" @click="activeGuideCategory = category.slug; loadGuidesContent()"><PvIcon name="document" /><strong>{{ category.name }}</strong><small>{{ category.count }} guides</small></button></div>
        <article class="pv-panel">
          <header class="pv-panel-header"><h2>Popular Guides</h2><router-link to="/guides" class="pv-purple-link">View all guides →</router-link></header>
          <p v-if="contentLoaded && guides.length === 0" class="pv-muted">No guides published yet.</p>
          <router-link v-for="(guide, index) in guides" :key="guide.title" :to="guide.href" class="pv-guide-row"><span class="pv-mini-thumb pv-guide-thumb" :style="contentThumbnailStyle(guide, index)"></span><span class="pv-topic-main"><strong>{{ guide.title }}</strong><small>{{ guide.excerpt }}</small><em>{{ guide.category }}</em></span><span><PvIcon name="document" /> {{ guide.metadata.guide_type ?? guide.type }}<br><PvIcon name="clock" /> {{ guide.timeLabel }} read<br><PvIcon name="eye" /> {{ guide.views }} views</span></router-link>
          <PaginationBlock :meta="guidePagination" @page="setGuidePage" />
        </article>
        <article id="faq-list" class="pv-panel"><header class="pv-panel-header"><h2>Frequently Asked Questions</h2><a class="pv-purple-link" href="#faq-list">View all FAQ →</a></header><details v-for="faq in faqs" :key="faq.slug"><summary><PvIcon name="question" /> {{ faq.title }}</summary><p class="pv-muted">{{ faq.body || faq.excerpt }}</p></details></article>
      </main>
      <aside class="pv-stack"><article class="pv-panel pv-help-card"><PvIcon name="question" /><h2>Need Help?</h2><p>Can&apos;t find what you&apos;re looking for?</p><router-link to="/discussions" class="pv-primary-button pv-full">Ask a Question</router-link></article><article class="pv-panel"><h2>Top FAQ Topics</h2><dl class="pv-data-list"><div v-for="category in contentCategories.faq" :key="category.slug"><dt>{{ category.name }}</dt><dd>{{ category.count }}</dd></div></dl></article><article class="pv-panel"><h2>Community Help</h2><div class="pv-mini-list"><router-link to="/discussions" class="pv-mini-row"><PvIcon name="question" /><span><strong>Ask the Community</strong><small>Get answers from experienced members</small></span><PvIcon name="chevron" /></router-link><a href="/admin/community-content" class="pv-mini-row"><PvIcon name="document" /><span><strong>Submit a Guide</strong><small>Share your knowledge with others</small></span><PvIcon name="chevron" /></a><router-link :to="{ path: '/discussions', query: { q: 'issue' } }" class="pv-mini-row"><PvIcon name="bell" /><span><strong>Report an Issue</strong><small>Report outdated or incorrect info</small></span><PvIcon name="chevron" /></router-link></div></article><article class="pv-panel"><h2>Popular Tags</h2><span class="pv-chip-row"><span v-for="topic in contentTopics.guide" :key="topic.name">{{ topic.name }}</span></span></article></aside>
    </div>
  </section>

  <section v-else-if="page === 'guideDetail'" class="pv-page">
    <div v-if="detailGuide" class="pv-content-grid">
      <main class="pv-stack">
        <nav class="pv-breadcrumbs">Guides & FAQ <PvIcon name="chevron" /> {{ detailGuide.category }} <PvIcon name="chevron" /></nav>
        <header class="pv-page-header"><div><h1>{{ detailGuide.title }}</h1><p>{{ detailGuide.excerpt }}</p><span class="pv-muted"><PvIcon name="calendar" /> {{ detailGuide.date }} · <PvIcon name="clock" /> {{ detailGuide.timeLabel }} read · <PvIcon name="eye" /> {{ detailGuide.views }} views</span></div><div class="pv-action-row"><button class="pv-small-button" @click="toggleContentBookmark(detailGuide.slug)"><PvIcon name="bookmark" /> {{ isBookmarkedContent(detailGuide.slug) ? 'Bookmarked' : 'Bookmark' }}</button><button class="pv-primary-button" @click="printCurrentPage"><PvIcon name="download" /> Print / PDF</button></div></header>
        <span class="pv-guide-hero" :style="contentThumbnailStyle(detailGuide)"></span>
        <article class="pv-alert"><PvIcon name="shield" /><strong>Disclaimer</strong><p>This guide is for educational purposes only and does not constitute medical advice. Always follow lab safety best practices and consult a healthcare professional for personal guidance.</p></article>
        <article class="pv-prose">
          <template v-for="block in guideBodyBlocks" :key="block.text">
            <h2 v-if="block.kind === 'heading'">{{ block.text }}</h2>
            <ul v-else-if="block.kind === 'list'"><li v-for="item in block.items" :key="item">{{ item }}</li></ul>
            <p v-else>{{ block.text }}</p>
          </template>
          <h2 v-if="detailGuideSteps.length">Step-by-Step Instructions</h2>
          <div v-if="detailGuideSteps.length" class="pv-step-list"><div v-for="(step, index) in detailGuideSteps" :key="step.title" class="pv-step"><span>{{ index + 1 }}</span><div><strong>{{ step.title }}</strong><p>{{ step.text }}</p></div><PvIcon :name="step.icon" /></div></div>
          <div class="pv-pass-box"><PvIcon name="shield" /> <span><strong>Safety First</strong><br>When in doubt, do not use the product. Your health and safety come first.</span></div>
        </article>
      </main>
      <aside class="pv-stack"><article class="pv-panel"><h2>On This Page</h2><ol class="pv-toc"><li v-for="heading in guideHeadings" :key="heading" :class="{ active: heading === guideHeadings[0] }">{{ heading }}</li></ol></article><article class="pv-panel"><h2>Quick Info</h2><dl class="pv-data-list"><div><dt>Difficulty</dt><dd><span class="pv-green-dot"></span> {{ detailGuide.metadata.difficulty ?? 'Beginner' }}</dd></div><div><dt>Time to Complete</dt><dd>{{ detailGuide.timeLabel }}</dd></div><div><dt>Category</dt><dd>{{ detailGuide.category }}</dd></div><div><dt>Last Updated</dt><dd>{{ detailGuide.date }}</dd></div></dl></article><article class="pv-panel"><h2>Related Guides</h2><div class="pv-mini-list"><router-link v-for="(guide, index) in relatedGuides" :key="guide.title" :to="guide.href" class="pv-mini-row"><span class="pv-mini-thumb" :style="contentThumbnailStyle(guide, index + 1)"></span><span><strong>{{ guide.title }}</strong><small>{{ guide.timeLabel }} read</small></span></router-link></div></article><article class="pv-panel pv-help-card"><h2>Still Need Help?</h2><p>Ask the community or submit a question.</p><router-link to="/discussions" class="pv-primary-button pv-full">Ask a Question</router-link></article></aside>
    </div>
    <div v-else class="pv-empty-route"><h1>Guide not found</h1><p>This guide has not been published or does not exist.</p></div>
  </section>

   <section v-else-if="page === 'members'" class="pv-page">
    <div v-if="showUpgradePrompt" class="pv-upgrade-overlay">
      <div class="pv-upgrade-card">
        <PvIcon name="users" />
        <h2>Premium Feature</h2>
        <p>Upgrade to Premium to browse detailed member profiles with activity history and reputation scores.</p>
        <router-link to="/pricing" class="pv-primary-button">View Pricing</router-link>
      </div>
    </div>
    <div v-else class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header"><div><h1>Members</h1><p>Find contributors, moderators, reviewers, and active community voices.</p></div><router-link to="/settings/profile" class="pv-small-button"><PvIcon name="user" /> Edit Profile</router-link></header>
        <div class="pv-metrics pv-metrics--member">
          <span><PvIcon name="users" /><strong>{{ memberStats.total }}</strong><small>Total Members</small></span>
          <span><PvIcon name="clock" /><strong>{{ memberStats.online }}</strong><small>Online Now</small></span>
          <span><PvIcon name="shield" /><strong>{{ apiMembers.filter(member => member.isModerator).length }}</strong><small>Moderators</small></span>
          <span><PvIcon name="star" /><strong>{{ topContributors.length }}</strong><small>Top Contributors</small></span>
        </div>
        <article class="pv-panel pv-member-directory">
          <header class="pv-panel-header">
            <div>
              <h2>Community Directory</h2>
              <p class="pv-muted">{{ members.length }} showing · {{ memberSortLabel }}</p>
            </div>
            <span class="pv-tag">{{ memberStats.total }} total</span>
          </header>
          <div class="pv-toolbar">
            <label class="pv-input-search">
              <input v-model="memberSearch" placeholder="Search members, bios, roles..." type="search" @keydown.enter="loadMembers">
              <PvIcon name="search" />
            </label>
            <div class="pv-tabs">
              <button :class="{ active: memberFilter === 'all' }" @click="memberFilter = 'all'">All</button>
              <button :class="{ active: memberFilter === 'online' }" @click="memberFilter = 'online'">Online</button>
              <button :class="{ active: memberFilter === 'moderators' }" @click="memberFilter = 'moderators'">Moderators</button>
              <button :class="{ active: memberFilter === 'contributors' }" @click="memberFilter = 'contributors'">Contributors</button>
            </div>
            <button class="pv-small-button" type="button" @click="cycleMemberSort">{{ memberSortLabel }} <PvIcon name="chevron" /></button>
            <button class="pv-icon-button" type="button" @click="loadMembers"><PvIcon name="filter" /></button>
          </div>
          <p v-if="membersLoaded && members.length === 0" class="pv-muted">No member profiles match those filters.</p>
          <div class="pv-member-grid">
            <router-link v-for="member in members" :key="member.slug" :to="member.href" class="pv-member-card">
              <header class="pv-member-card-banner">
                <span v-if="member.avatarUrl" class="pv-avatar pv-avatar--large" :class="member.color"><img :src="assetUrl(member.avatarUrl)" :alt="member.name" class="pv-avatar-img"></span>
                <span v-else class="pv-avatar pv-avatar--large" :class="member.color">{{ member.initial }}</span>
                <span class="pv-member-presence" :class="{ online: member.isOnline }">{{ member.isOnline ? 'Online' : 'Away' }}</span>
              </header>
              <strong>{{ member.name }}</strong>
              <span class="pv-chip-row">
                <span v-if="member.isVerified" class="trusted">Verified</span>
                <span v-if="member.isModerator" class="trusted">Moderator</span>
                <span v-if="member.group">{{ member.group }}</span>
              </span>
              <p>{{ member.bio || 'No bio yet.' }}</p>
              <dl>
                <span><dt>Posts</dt><dd>{{ formatCount(member.stats.posts) }}</dd></span>
                <span><dt>Reviews</dt><dd>{{ formatCount(member.stats.reviews) }}</dd></span>
                <span><dt>Labs</dt><dd>{{ formatCount(member.stats.lab_reports) }}</dd></span>
              </dl>
              <footer><small>{{ member.lastActive || 'No recent activity' }}</small><PvIcon name="chevron" /></footer>
            </router-link>
          </div>
          <PaginationBlock :meta="memberPagination" @page="setMemberPage" />
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Top Contributors</h2><div class="pv-mini-list"><router-link v-for="member in topContributors" :key="member.slug" :to="member.href" class="pv-mini-row"><span class="pv-avatar" :class="member.color">{{ member.initial }}</span><span><strong>{{ member.name }}</strong><small>{{ formatCount(memberEngagementScore(member)) }} contributions</small></span><PvIcon name="chevron" /></router-link></div></article>
        <article class="pv-panel"><h2>Online Members</h2><div class="pv-avatar-stack"><router-link v-for="member in onlineMembers" :key="member.slug" :to="member.href" class="pv-avatar" :class="member.color">{{ member.initial }}</router-link><span v-if="onlineMembers.length === 0" class="pv-muted">Nobody online right now.</span></div></article>
        <article class="pv-panel"><h2>Member Tips</h2><ul class="pv-check-list"><li>Keep your profile bio current</li><li>Use reports for moderation issues</li><li>Message members from their profile</li><li>Reputation grows from useful posts</li></ul></article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'memberDetail'" class="pv-page">
    <MemberProfile v-if="detailMember" :profile="detailMember" />
    <div v-else class="pv-empty-route"><h1>Member not found</h1><p>This member profile has not been published or does not exist.</p></div>
  </section>

  <section v-else-if="page === 'messages'" class="pv-page pv-messages-page">
    <div v-if="showUpgradePrompt" class="pv-upgrade-overlay">
      <div class="pv-upgrade-card">
        <PvIcon name="message" />
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
        <button v-for="chat in chats" :key="chat.id" class="pv-chat-row" :class="{ active: currentThread?.id === chat.id }" @click="openMessageThread(chat.id)"><span class="pv-avatar" :class="chat.participant.color">{{ chat.participant.initial }}</span><span><strong>{{ chat.participant.name }} <em v-if="chat.participant.role" class="pv-tag">{{ chat.participant.role }}</em></strong><small>{{ chat.preview }}</small></span><time>{{ chat.time }}</time></button>
      </aside>
      <main v-if="currentThread" class="pv-chat-panel">
        <header><button class="pv-icon-button pv-icon-button--static pv-mobile-back" @click="openMessagesInbox" aria-label="Back to threads"><PvIcon name="chevron" /></button><span class="pv-avatar" :class="currentThread.participant.color">{{ currentThread.participant.initial }}</span><div><h2>{{ currentThread.participant.name }} <span class="pv-tag">{{ currentThread.participant.role }}</span></h2><small><span class="pv-green-dot"></span> {{ currentThread.participant.lastActive }}</small></div><span class="pv-flex-spacer"></span><router-link :to="currentThread.participant.href" class="pv-icon-button pv-icon-button--static" aria-label="View member profile"><PvIcon name="settings" /></router-link></header>
        <div v-if="showMessageSafetyNotice" class="pv-alert"><PvIcon name="shield" /><span>Messages are visible only to you and the recipient. Do not share personal info or make any transactions.</span><button type="button" class="pv-icon-button" aria-label="Dismiss notice" @click="showMessageSafetyNotice = false"><PvIcon name="close" /></button></div>
        <div class="pv-chat-stream"><span class="pv-date-sep">Messages</span><MessageBubble v-for="message in currentThread.messages" :key="message.id ?? message.time" :side="message.side" :text="message.text" :time="message.time" :file="Boolean(message.attachmentName)" :attachment-name="message.attachmentName ?? ''" :attachment-label="message.attachmentLabel" :avatar-initial="message.avatarInitial || currentThread.participant.initial" :avatar-color="message.avatarColor || currentThread.participant.color" /></div>
        <form class="pv-chat-input" @submit.prevent="sendMessage"><PvIcon name="share" /><input v-model="messageBody" placeholder="Type a message..."><PvIcon name="question" /><button class="pv-primary-button" :disabled="sendingMessage"><PvIcon name="send" /></button></form>
      </main>
      <main v-else class="pv-chat-panel"><article class="pv-panel"><h2>No thread selected</h2><p class="pv-muted">Choose a thread to read messages.</p></article></main>
    </div>
  </section>

  <section v-else-if="page === 'announcements'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>Announcements</h1><p>Official updates and important announcements from the Peptide Vendors team.</p></div>
          <a href="/admin/community-announcements" class="pv-primary-button"><PvIcon name="plus" /> New Announcement</a>
        </header>
        <div class="pv-tabs pv-tabs--line">
          <button :class="{ active: announcementFilter === 'all' }" @click="setAnnouncementFilter('all')">All Announcements</button>
          <button :class="{ active: announcementFilter === 'pinned' }" @click="setAnnouncementFilter('pinned')">Pinned</button>
          <button
            v-for="category in announcementCategories"
            :key="category.slug"
            :class="{ active: announcementFilter === category.slug }"
            @click="setAnnouncementFilter(category.slug)"
          >
            {{ category.name }}
          </button>
        </div>
        <p v-if="announcementStatusMessage" class="pv-form-error">{{ announcementStatusMessage }}</p>
        <p v-if="announcementsLoaded && announcements.length === 0" class="pv-muted">No announcements published yet.</p>
        <router-link v-for="announcement in announcements" :key="announcement.slug" :to="announcement.href" class="pv-announcement-row" :class="{ pinned: announcement.pinned }">
          <span class="pv-large-icon" :class="announcement.tone"><PvIcon :name="announcement.icon" /></span>
          <div>
            <span v-if="announcement.pinned" class="pv-tag">PINNED</span>
            <small :class="announcement.tone">{{ announcement.category }}</small>
            <h2>{{ announcement.title }}</h2>
            <p>{{ announcement.text }}</p>
            <div class="pv-author-line"><span class="pv-avatar purple">{{ announcement.authorInitial }}</span><strong>{{ announcement.author }}</strong><span class="pv-tag">Administrator</span><span>{{ announcement.date }}</span><span><PvIcon name="message" /> {{ announcement.comments }}</span><span><PvIcon name="eye" /> {{ announcement.views }}</span></div>
          </div>
          <span class="pv-icon-button pv-icon-button--static"><PvIcon name="chevron" /></span>
        </router-link>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>About Announcements</h2><span class="pv-icon-tile"><PvIcon name="send" /></span><p>This is where we share official updates, important notices, and community announcements.</p><ul class="pv-check-list"><li>Official updates from admins</li><li>Important safety information</li><li>Platform changes & maintenance</li><li>Community news & events</li></ul></article>
        <article class="pv-panel"><h2>Announcements Overview</h2><dl class="pv-data-list"><div><dt>Total Announcements</dt><dd>{{ announcementStats.total }}</dd></div><div><dt>Pinned</dt><dd>{{ announcementStats.pinned }}</dd></div><div><dt>This Month</dt><dd>{{ announcementStats.this_month }}</dd></div><div><dt>Total Views</dt><dd>{{ formatCount(announcementStats.total_views) }}</dd></div><div><dt>Total Comments</dt><dd>{{ announcementStats.total_comments }}</dd></div></dl></article>
        <article class="pv-panel"><h2>Recent Categories</h2><dl class="pv-data-list"><div v-for="category in announcementCategories" :key="category.slug"><dt><span class="pv-dot" :class="category.tone"></span>{{ category.name }}</dt><dd>{{ category.count }}</dd></div></dl></article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'announcementNew'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack"><nav class="pv-breadcrumbs">Announcements <PvIcon name="chevron" /> Admin</nav><header class="pv-page-header"><div><h1>Create New Announcement</h1><p>Announcements are created and published through the existing admin panel.</p></div></header><article class="pv-form-card"><h2>Use the admin panel</h2><p>The public website reads announcements from the database. Staff can create drafts, publish updates, pin important notices, and hide old posts in the admin area.</p><a href="/admin/community-announcements" class="pv-primary-button"><PvIcon name="send" /> Open Announcement Manager</a></article></main>
      <aside class="pv-stack"><article class="pv-panel"><h2>Announcement Flow</h2><div class="pv-mini-list"><span class="pv-mini-row"><PvIcon name="document" /><span><strong>Create or edit</strong><small>Use the admin form to write the content.</small></span></span><span class="pv-mini-row"><PvIcon name="shield" /><span><strong>Publish safely</strong><small>Only published rows appear on the public site.</small></span></span><span class="pv-mini-row"><PvIcon name="star" /><span><strong>Pin key updates</strong><small>Pinned announcements stay at the top.</small></span></span></div></article></aside>
    </div>
  </section>

  <section v-else-if="page === 'announcementDetail'" class="pv-page">
    <div class="pv-content-grid">
      <main v-if="detailAnnouncement" class="pv-stack">
        <nav class="pv-breadcrumbs">Announcements <PvIcon name="chevron" /> {{ detailAnnouncement.category }} <PvIcon name="chevron" /></nav>
        <header class="pv-page-header">
          <div>
            <span class="pv-tag" :class="detailAnnouncement.tone">{{ detailAnnouncement.category }}</span>
            <h1>{{ detailAnnouncement.title }}</h1>
            <p>{{ detailAnnouncement.text }}</p>
            <span class="pv-muted"><PvIcon name="calendar" /> {{ detailAnnouncement.date }} · <PvIcon name="eye" /> {{ detailAnnouncement.views }} views</span>
          </div>
          <router-link to="/announcements" class="pv-small-button"><PvIcon name="chevron" /> All Announcements</router-link>
        </header>
        <article class="pv-notification-hero">
          <span class="pv-large-icon" :class="detailAnnouncement.tone"><PvIcon :name="detailAnnouncement.icon" /></span>
          <div><div class="pv-author-line"><span class="pv-avatar purple">{{ detailAnnouncement.authorInitial }}</span><strong>{{ detailAnnouncement.author }}</strong><span class="pv-tag">Administrator</span><span>{{ detailAnnouncement.time }}</span></div><h2>{{ detailAnnouncement.title }}</h2><p>{{ detailAnnouncement.text }}</p></div>
        </article>
        <article class="pv-panel pv-prose">
          <p v-for="paragraph in announcementParagraphs" :key="paragraph">{{ paragraph }}</p>
        </article>
      </main>
      <main v-else class="pv-stack"><router-link to="/announcements" class="pv-purple-link">Back to announcements</router-link><article class="pv-panel"><h1>Announcement not found</h1><p class="pv-muted">This announcement may have been unpublished.</p></article></main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Announcement Details</h2><dl class="pv-data-list"><div><dt>Category</dt><dd>{{ detailAnnouncement?.category ?? '' }}</dd></div><div><dt>Published</dt><dd>{{ detailAnnouncement?.date ?? '' }}</dd></div><div><dt>Views</dt><dd>{{ detailAnnouncement?.views ?? '0' }}</dd></div><div><dt>Comments</dt><dd>{{ detailAnnouncement?.comments ?? 0 }}</dd></div></dl></article>
        <article class="pv-panel"><h2>Related Links</h2><div class="pv-filter-list"><router-link to="/announcements"><PvIcon name="megaphone" /> Announcements <PvIcon name="chevron" /></router-link><router-link to="/discussions"><PvIcon name="message" /> Community Discussions <PvIcon name="chevron" /></router-link><router-link to="/lab-results"><PvIcon name="flask" /> Lab Results <PvIcon name="chevron" /></router-link></div></article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'notifications'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>Notifications</h1></div>
          <button v-if="authStore.isAuthenticated" class="pv-small-button" :disabled="markingNotificationsRead" @click="markAllNotificationsRead">Mark all as read</button>
          <router-link to="/settings/notifications" class="pv-icon-button"><PvIcon name="settings" /></router-link>
        </header>
        <div class="pv-tabs pv-tabs--line">
          <button v-for="filter in notificationFilterTabs" :key="filter.slug" :class="{ active: activeNotificationFilter === filter.slug }" @click="setNotificationFilter(filter.slug)">
            {{ filter.label }} <span v-if="filter.count !== undefined">{{ filter.count }}</span>
          </button>
        </div>
        <article class="pv-panel pv-notification-list">
          <p v-if="notificationStatusMessage" class="pv-muted">{{ notificationStatusMessage }}</p>
          <p v-if="notificationsLoaded && notifications.length === 0" class="pv-muted">No notifications yet.</p>
          <router-link v-for="item in notifications" :key="item.slug" :to="item.detailHref" class="pv-notification-row" @click="markNotificationRead(item.slug)">
            <span class="pv-large-icon" :class="item.tone"><PvIcon :name="item.icon" /></span>
            <span><strong>{{ item.title }}</strong><small>{{ item.text }}</small><em class="pv-tag" :class="item.tone">{{ item.category }}</em></span>
            <time>{{ item.time }}</time>
            <b v-if="item.unread"></b>
          </router-link>
        </article>
        <PaginationBlock :meta="notificationPagination" :label="notificationPaginationLabel" @page="setNotificationPage" />
      </main>
      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Filter Notifications</h2>
          <div class="pv-filter-list">
            <button v-for="filter in notificationFilterItems" :key="filter.slug" :class="{ active: activeNotificationFilter === filter.slug }" @click="setNotificationFilter(filter.slug)">
              <PvIcon :name="filter.icon" /> {{ filter.label }} <strong>{{ filter.count }}</strong>
            </button>
          </div>
        </article>
        <article class="pv-panel"><h2>Notification Summary</h2><div class="pv-mini-list"><span v-for="item in notificationSummary" :key="item.label" class="pv-mini-row"><PvIcon :name="item.icon" /><span><strong>{{ item.label }}</strong><small>{{ item.latest }}</small></span><strong>{{ item.count }}</strong></span></div></article>
        <router-link to="/settings/notifications" class="pv-panel pv-settings-link"><PvIcon name="settings" /> View Notification Settings <PvIcon name="chevron" /></router-link>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'notificationDetail'" class="pv-page">
    <div class="pv-content-grid">
      <main v-if="primaryNotification" class="pv-stack"><router-link to="/notifications" class="pv-purple-link">Back</router-link><header class="pv-page-header"><div><h1>Notification</h1><router-link to="/notifications" class="pv-purple-link">Back to all notifications</router-link></div><button v-if="authStore.isAuthenticated && primaryNotification.unread" class="pv-small-button" :disabled="markingNotificationsRead" @click="markNotificationRead(primaryNotification.slug)"><PvIcon name="mail" /> Mark as read</button><router-link v-if="previousNotification" class="pv-icon-button pv-icon-button--static" :to="previousNotification.detailHref" aria-label="Previous notification"><PvIcon name="chevron" /></router-link><router-link v-if="nextNotification" class="pv-icon-button pv-icon-button--static" :to="nextNotification.detailHref" aria-label="Next notification"><PvIcon name="chevron" /></router-link></header><article class="pv-notification-hero"><span class="pv-large-icon" :class="primaryNotification.tone"><PvIcon :name="primaryNotification.icon" /></span><div><div class="pv-author-line"><span class="pv-tag">{{ primaryNotification.category }}</span><strong>{{ primaryNotification.author }}</strong><span>{{ primaryNotification.time }}</span></div><h2>{{ primaryNotification.title }}</h2><p>{{ primaryNotification.text }}</p><router-link :to="primaryNotification.href" class="pv-primary-button">Open {{ primaryNotification.category }} <PvIcon name="share" /></router-link></div></article><article class="pv-panel pv-prose"><h2>Full Message</h2><p v-for="paragraph in primaryNotification.bodyParagraphs" :key="paragraph">{{ paragraph }}</p><hr><h2>What you can do</h2><router-link :to="primaryNotification.href" class="pv-action-card"><PvIcon :name="primaryNotification.icon" /><span><strong>Open related content</strong><small>View the full source item for this notification.</small></span><PvIcon name="chevron" /></router-link><router-link to="/discussions" class="pv-action-card"><PvIcon name="message" /><span><strong>Join the discussion</strong><small>Discuss updates with the community.</small></span><PvIcon name="chevron" /></router-link></article></main>
      <main v-else class="pv-stack"><router-link to="/notifications" class="pv-purple-link">Back</router-link><article class="pv-panel"><h1>Notification not found</h1><p class="pv-muted">There are no live notifications to show yet.</p></article></main>
      <aside class="pv-stack"><article class="pv-panel"><h2>Notification Details</h2><dl class="pv-data-list"><div><dt>Type</dt><dd>{{ primaryNotification?.category ?? '' }}</dd></div><div><dt>From</dt><dd>{{ primaryNotification?.author ?? '' }}</dd></div><div><dt>Received</dt><dd>{{ primaryNotification?.time ?? '' }}</dd></div><div><dt>Status</dt><dd><span class="pv-dot purple"></span> {{ primaryNotification?.unread ? 'Unread' : 'Read' }}</dd></div></dl><hr><h2>Related Links</h2><div class="pv-filter-list"><router-link to="/lab-results"><PvIcon name="flask" /> Lab Results <PvIcon name="chevron" /></router-link><router-link to="/announcements"><PvIcon name="megaphone" /> Announcements <PvIcon name="chevron" /></router-link><router-link to="/discussions"><PvIcon name="message" /> Community Discussions <PvIcon name="chevron" /></router-link></div></article></aside>
    </div>
  </section>

  <section v-else-if="page === 'pricing'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div>
            <h1>Choose Your Plan</h1>
            <p>Upgrade to Premium for full access to vendor reviews, lab results, and member messaging.</p>
          </div>
        </header>

        <div class="pv-pricing-grid">
          <article class="pv-pricing-card pv-pricing-card--free">
            <header>
              <h2>Free</h2>
              <p class="pv-price"><sup>£</sup>0</p>
              <small>Forever free</small>
            </header>
            <ul class="pv-check-list">
              <li>Community Discussions</li>
              <li>Guides &amp; FAQ</li>
              <li>Announcements</li>
              <li>Research Library</li>
              <li class="pv-check-list--disabled">Vendor Reviews &amp; Lab Results</li>
              <li class="pv-check-list--disabled">Member Messaging &amp; Profiles</li>
              <li class="pv-check-list--disabled">5 discussions per day</li>
            </ul>
            <div class="pv-pricing-footer">
              <span v-if="authStore.isAuthenticated && membershipTier === 'free'" class="pv-tag trusted">Current Plan</span>
              <span v-else-if="!authStore.isAuthenticated" class="pv-tag">Always Free</span>
            </div>
          </article>

          <article class="pv-pricing-card pv-pricing-card--premium">
            <header>
              <span class="pv-badge">Popular</span>
              <h2>Premium</h2>
              <p class="pv-price"><sup>£</sup>{{ billingInterval === 'year' ? '10' : 'N/A' }}</p>
              <small>per year</small>
            </header>
            <ul class="pv-check-list">
              <li>Community Discussions</li>
              <li>Guides &amp; FAQ</li>
              <li>Announcements</li>
              <li>Research Library</li>
              <li>Vendor Reviews</li>
              <li>Lab Results</li>
              <li>Member Messaging</li>
              <li>Member Profiles</li>
              <li>Unlimited Discussions</li>
            </ul>
            <div class="pv-pricing-footer">
              <template v-if="authStore.isAuthenticated">
                <span v-if="membershipTier === 'paid'" class="pv-tag trusted">Active</span>
                <div v-else class="pv-pricing-buttons">
                  <button class="pv-primary-button pv-full" @click="subscribeWith('stripe')">
                    <PvIcon name="lock" /> Subscribe with Card
                  </button>
                  <button class="pv-paypal-button pv-full" @click="subscribeWith('paypal')">
                    Subscribe with PayPal
                  </button>
                </div>
              </template>
              <router-link v-else to="/login?redirect=/pricing" class="pv-primary-button pv-full">
                Log In to Subscribe
              </router-link>
            </div>
          </article>
        </div>

        <article class="pv-panel">
          <h2>All Premium Features</h2>
          <div class="pv-feature-grid">
            <div class="pv-feature-item">
              <PvIcon name="shield" />
              <strong>Vendor Reviews</strong>
              <small>Read and write verified vendor reviews with ratings, photos, and detailed feedback.</small>
            </div>
            <div class="pv-feature-item">
              <PvIcon name="flask" />
              <strong>Lab Results</strong>
              <small>Access independent lab testing reports including COAs, purity analysis, and batch data.</small>
            </div>
            <div class="pv-feature-item">
              <PvIcon name="message" />
              <strong>Messaging</strong>
              <small>Direct message other premium members for private discussions and collaboration.</small>
            </div>
            <div class="pv-feature-item">
              <PvIcon name="users" />
              <strong>Member Profiles</strong>
              <small>Browse detailed member profiles with activity history and reputation scores.</small>
            </div>
            <div class="pv-feature-item">
              <PvIcon name="discussions" />
              <strong>Unlimited Discussions</strong>
              <small>Create and participate in unlimited community discussions with no daily limits.</small>
            </div>
            <div class="pv-feature-item">
              <PvIcon name="upload" />
              <strong>File Uploads</strong>
              <small>Attach files, images, and documents to your discussions and messages.</small>
            </div>
          </div>
        </article>

        <p v-if="paymentStatusMessage" class="pv-alert pv-alert--compact">{{ paymentStatusMessage }}</p>
      </main>
    </div>
  </section>

  <section v-else-if="page.startsWith('settings')" class="pv-page">
    <SettingsScreens :page="page" />
  </section>

  <section v-else-if="page === 'telegramUpdates'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div>
            <h1>Telegram Updates</h1>
            <p>Follow community alerts, announcements, and status updates from the Peptide Vendors team.</p>
          </div>
          <a :href="publicTelegramUrl" target="_blank" rel="noreferrer" class="pv-primary-button"><PvIcon name="send" /> Join Telegram</a>
        </header>
        <article class="pv-panel pv-telegram-updates">
          <span class="pv-large-icon purple"><PvIcon name="send" /></span>
          <div>
            <h2>Community Broadcasts</h2>
            <p class="pv-muted">Telegram is used for quick notices when new announcements, lab results, or platform updates are published.</p>
            <a :href="publicTelegramUrl" target="_blank" rel="noreferrer" class="pv-small-button">Open Telegram <PvIcon name="share" /></a>
          </div>
        </article>
        <div class="pv-profile-grid">
          <article class="pv-panel"><h2>Latest Announcements</h2><div class="pv-mini-list"><router-link v-for="announcement in announcementPreview" :key="announcement.slug" :to="announcement.href" class="pv-mini-row"><PvIcon :name="announcement.icon" /><span><strong>{{ announcement.title }}</strong><small>{{ announcement.time }}</small></span><PvIcon name="chevron" /></router-link><p v-if="announcementPreview.length === 0" class="pv-muted">No announcements published yet.</p></div></article>
          <article class="pv-panel"><h2>Notification Controls</h2><p class="pv-muted">Choose email, push, and sound preferences from account settings.</p><router-link to="/settings/notifications" class="pv-small-button">Open Notification Settings</router-link></article>
        </div>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Telegram Link</h2><p class="pv-muted">{{ publicTelegramUrl }}</p><a :href="publicTelegramUrl" target="_blank" rel="noreferrer" class="pv-primary-button pv-full">Join Telegram</a></article>
        <article class="pv-panel"><h2>Community Updates</h2><dl class="pv-data-list"><div><dt>Announcements</dt><dd>{{ announcementStats.total }}</dd></div><div><dt>Notifications</dt><dd>{{ notificationCounts.total }}</dd></div><div><dt>Unread</dt><dd>{{ notificationCounts.unread }}</dd></div></dl></article>
      </aside>
    </div>
  </section>

  <section v-else-if="page.startsWith('legal')" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <article class="pv-panel pv-prose">
          <router-link to="/login" class="pv-purple-link">Back to sign in</router-link>
          <h1>{{ legalPage.title }}</h1>
          <p v-for="paragraph in legalPage.paragraphs" :key="paragraph">{{ paragraph }}</p>
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Community Documents</h2>
          <div class="pv-filter-list">
            <router-link to="/terms"><PvIcon name="document" /> Terms of Service <PvIcon name="chevron" /></router-link>
            <router-link to="/privacy"><PvIcon name="lock" /> Privacy Policy <PvIcon name="chevron" /></router-link>
            <router-link to="/community-rules"><PvIcon name="shield" /> Community Rules <PvIcon name="chevron" /></router-link>
          </div>
        </article>
      </aside>
    </div>
  </section>

  <section v-else class="pv-page">
    <div class="pv-empty-route">
      <h1>{{ fallbackTitle }}</h1>
      <p>The requested page does not exist.</p>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, onUnmounted, ref, watch, type PropType } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import CharacterCount from '@tiptap/extension-character-count'
import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import { Table, TableCell, TableHeader, TableRow } from '@tiptap/extension-table'
import Youtube from '@tiptap/extension-youtube'
import StarterKit from '@tiptap/starter-kit'
import PvIcon from '@/components/peptide/PvIcon.vue'
import EmojiPicker from '@/components/ui/EmojiPicker.vue'
import GiphyPicker from '@/components/ui/GiphyPicker.vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import heroImage from '@/assets/peptide/hero-vials.png'
import researchImage from '@/assets/peptide/research-thumbnails.png'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const page = computed(() => String(route.meta.page ?? 'home'))
const fallbackTitle = computed(() => String(route.meta.title ?? ''))

interface UiDiscussion {
  id?: number
  title: string
  excerpt: string
  author: string
  authorUsername: string
  authorOnline: boolean
  authorId?: number
  time: string
  replies: number
  views: string
  initial: string
  color: string
  tag: string
  href: string
  slug?: string
  body?: string
  category?: string
  categorySlug?: string
  lastActivity?: string
  isPinned: boolean
  isLocked: boolean
  premiumOnly: boolean
  voteScore: number
  viewerVote: -1 | 0 | 1
  avatarUrl?: string | null
  latestReply?: {
    author: string
    username?: string
    timeAgo: string
    avatar?: string | null
    initial: string
  } | null
}

interface PaginationMeta {
  current_page?: number
  last_page?: number
  per_page?: number
  total?: number
  from?: number | null
  to?: number | null
}

interface UiReply {
  id?: number
  author: string
  initial: string
  color: string
  avatarUrl?: string | null
  badge?: string
  time: string
  text: string
  votes: number
  file?: string
  attachmentUrl?: string | null
  attachmentMeta: Record<string, unknown>
  viewerVote: -1 | 0 | 1
  authorId?: number
  authorUsername: string
  authorOnline: boolean
}

interface ApiDiscussion {
  id?: number
  title: string
  slug: string
  tag?: string
  excerpt?: string | null
  body?: string | null
  replies?: number
  views?: number
  href?: string
  time_ago?: string | null
  last_activity?: string | null
  is_pinned?: boolean
  is_locked?: boolean
  category?: { name?: string | null; slug?: string | null; color?: string | null } | null
  author?: { id?: number; name?: string | null; username?: string | null; initial?: string | null; avatar?: string | null; is_online?: boolean } | null
  vote_score?: number
  viewer_vote?: number
  reply_items?: ApiReply[]
  participants?: ApiMemberProfile[]
  similar_discussions?: ApiDiscussion[]
  last_reply?: {
    author: string
    username?: string | null
    time_ago: string
    avatar?: string | null
    initial: string
  } | null
}

interface ApiCategory {
  id?: number
  name: string
  slug: string
  icon: string
  color?: string
  premium_only?: boolean
  count: number
}

interface ApiReply {
  id?: number
  body?: string | null
  attachment_name?: string | null
  attachment_url?: string | null
  attachment_meta?: Record<string, any> | null
  votes?: number
  viewer_vote?: number
  time_ago?: string | null
  author?: { id?: number; name?: string | null; username?: string | null; initial?: string | null; badge?: string | null; avatar?: string | null; is_online?: boolean } | null
}

interface DiscussionIndexResponse {
  data: ApiDiscussion[]
  meta?: PaginationMeta & {
    categories?: ApiCategory[]
    stats?: {
      total_discussions?: number
      total_replies?: number
    }
  }
}

interface DiscussionDetailResponse {
  data: ApiDiscussion
}

interface UiLabResult {
  id?: number
  name: string
  slug: string
  type: string
  use: string
  vendor: string
  batch: string
  lab: string
  date: string
  receivedDate: string
  reportId: string
  sampleType: string
  sampleCondition: string
  purity: string
  purityPercent?: number | null
  waterContent: string
  peptideContent: string
  identityResult: string
  overallResult: string
  coaFilename?: string | null
  notes: string
  views: string
  comments: number
  color: string
  href: string
  submittedBy?: string
  status?: string
  isVerified?: boolean
}

interface ApiLabResult {
  id?: number
  compound_name: string
  slug: string
  compound_type?: string | null
  use_case?: string | null
  vendor_name: string
  batch_code: string
  lab_name: string
  tested_at?: string | null
  tested_date?: string | null
  received_at?: string | null
  received_date?: string | null
  report_id?: string | null
  sample_type?: string | null
  sample_condition?: string | null
  purity_percent?: number | null
  purity?: string | null
  water_content_percent?: number | null
  peptide_content_percent?: number | null
  identity_result?: string | null
  overall_result?: string | null
  coa_filename?: string | null
  notes?: string | null
  status?: string | null
  is_verified?: boolean
  views?: number
  comments?: number
  href?: string
  submitted_by?: { name?: string | null } | null
}

interface LabStats {
  total?: number
  batches?: number
  avg_purity?: number
  labs?: number
}

interface LabFilterOptions {
  compound_types: string[]
  compounds: string[]
  vendors: string[]
  labs: string[]
}

interface LabResultIndexResponse {
  data: ApiLabResult[]
  meta?: PaginationMeta & {
    stats?: LabStats
    filters?: Partial<LabFilterOptions>
  }
}

interface LabResultDetailResponse {
  data: ApiLabResult
}

interface RatingDistributionRow {
  rating: number
  count: number
  percent: number
}

interface ProductVariant {
  label: string
  price: number | null
  availability: string
}

interface VendorProduct {
  id?: number
  vendorId?: number
  name: string
  slug: string
  category: string
  strength: string
  packageSize: string
  purityLabel: string
  description: string
  variants: ProductVariant[]
  price: number | null
  priceLabel: string
  currencyCode: string
  availability: string
  availabilityLabel: string
  imageUrl?: string | null
  tags: string[]
  rating: number
  ratingLabel: string
  reviews: number
  sortOrder: number
  status: string
  href?: string | null
}

interface ApiVendorProduct {
  id?: number
  vendor_id?: number
  name: string
  slug: string
  category?: string | null
  strength?: string | null
  package_size?: string | null
  purity_label?: string | null
  description?: string | null
  variants?: ProductVariant[]
  price?: number | null
  price_label?: string | null
  currency_code?: string | null
  availability?: string | null
  availability_label?: string | null
  image_url?: string | null
  tags?: string[]
  average_rating?: number
  rating_label?: string | null
  review_count?: number
  sort_order?: number
  status?: string | null
  href?: string | null
}

interface VendorDocument {
  id?: number
  vendorId?: number
  title: string
  filePath: string
  fileType: string
  category?: string | null
  description?: string | null
  status: string
  url: string
  createdAt?: string
  updatedAt?: string
}

interface ApiVendorDocument {
  id?: number
  vendor_id?: number
  title: string
  file_path: string
  file_type: string
  category?: string | null
  description?: string | null
  status: string
  url: string
  created_at?: string
  updated_at?: string
}

interface VendorContact {
  email?: string | null
  telegram?: string | null
  signal?: string | null
  discord?: string | null
  supportUrl?: string | null
  responsePolicy?: string | null
  publicNotes?: string | null
}

interface UiVendor {
  id?: number
  ownerUserId?: number | null
  claimStatus: string
  isOwnedByViewer: boolean
  name: string
  slug: string
  logo: string
  logoText: string
  imageUrl?: string | null
  class: string
  status: string
  publishStatus: string
  statusClass: string
  country: string
  rating: string
  reviews: number
  since: string
  buyAgain: string
  chips: string[]
  tone: string
  href: string
  description: string
  websiteUrl?: string | null
  contact: VendorContact
  lastActive: string
  responseRate: string
  avgResponseTime: string
  productCount: number
  products: VendorProduct[]
  topProducts: VendorProduct[]
  documents: VendorDocument[]
  ratingDistribution: RatingDistributionRow[]
}

interface UiVendorReview {
  id?: number
  author: string
  initial: string
  color: string
  date: string
  rating: number
  title: string
  text: string
  productName?: string | null
  chips: string[]
  photoUrls: string[]
  helpful: number
  verifiedBuyer: boolean
  vendorResponse?: string | null
  respondedAt?: string | null
}

interface ApiVendorReview {
  id?: number
  title: string
  body: string
  rating: number
  product_name?: string | null
  helpful_count?: number
  vendor_response?: string | null
  responded_at?: string | null
  is_verified_buyer?: boolean
  tags?: string[]
  photo_urls?: string[]
  status?: string
  reviewed_date?: string | null
  author?: { name?: string | null; initial?: string | null } | null
}

interface ApiVendor {
  id?: number
  owner_user_id?: number | null
  claim_status?: string | null
  is_owned_by_viewer?: boolean
  name: string
  slug: string
  logo_initials?: string | null
  logo_text?: string | null
  logo_class?: string | null
  status_label?: string | null
  status_class?: string | null
  country?: string | null
  status?: string | null
  tone?: string | null
  description?: string | null
  website_url?: string | null
  image_url?: string | null
  contact?: {
    email?: string | null
    telegram?: string | null
    signal?: string | null
    discord?: string | null
    support_url?: string | null
    response_policy?: string | null
    public_notes?: string | null
  } | null
  member_since_label?: string | null
  last_active_label?: string | null
  review_count?: number
  rating_label?: string | null
  average_rating?: number
  would_buy_again_label?: string | null
  response_rate_label?: string | null
  avg_response_time?: string | null
  tags?: string[]
  product_count?: number
  products?: ApiVendorProduct[]
  top_products?: ApiVendorProduct[]
  href?: string
  rating_distribution?: RatingDistributionRow[]
  review_items?: ApiVendorReview[]
  documents?: ApiVendorDocument[]
}

interface VendorStats {
  vendors_reviewed: number
  total_reviews: number
  average_rating: number
  would_buy_again: number
}

interface VendorFilterStatus {
  slug: string
  name: string
  count: number
}

interface VendorFilterOptions {
  statuses: VendorFilterStatus[]
  ratings: number[]
  tags: string[]
}

interface VendorIndexResponse {
  data: ApiVendor[]
  meta?: PaginationMeta & {
    stats?: VendorStats
    top_vendors?: ApiVendor[]
    filters?: Partial<VendorFilterOptions>
  }
}

interface VendorDetailResponse {
  data: ApiVendor
}

interface VendorProfileResponse {
  data: ApiVendor | null
  is_approved_vendor?: boolean
  can_create_vendor_profile?: boolean
}

interface UiAnnouncement {
  id?: number
  title: string
  slug: string
  category: string
  icon: string
  tone: string
  text: string
  body: string
  date: string
  time: string
  comments: number
  views: string
  pinned: boolean
  href: string
  author: string
  authorInitial: string
}

interface ApiAnnouncement {
  id?: number
  title: string
  slug: string
  category: string
  category_slug: string
  icon?: string | null
  tone?: string | null
  excerpt?: string | null
  body?: string | null
  comments?: number
  views?: number
  is_pinned?: boolean
  status?: string
  published_at?: string | null
  published_label?: string | null
  time_ago?: string | null
  href?: string
  author?: { name?: string | null; initial?: string | null } | null
}

interface AnnouncementCategory {
  name: string
  slug: string
  tone: string
  count: number
}

interface AnnouncementStats {
  total: number
  pinned: number
  this_month: number
  total_views: number
  total_comments: number
}

interface AnnouncementIndexResponse {
  data: ApiAnnouncement[]
  meta?: {
    stats?: AnnouncementStats
    categories?: AnnouncementCategory[]
  }
}

interface AnnouncementDetailResponse {
  data: ApiAnnouncement
}

interface UiContentItem {
  id?: number
  type: 'research' | 'guide' | 'faq'
  title: string
  slug: string
  tag: string
  category: string
  excerpt: string
  body: string
  date: string
  time: string
  views: string
  downloads: string
  comments: number
  readMinutes: number
  timeLabel: string
  href: string
  imageIndex: number
  imageUrl?: string | null
  author: string
  authorInitial: string
  authorBadge?: string | null
  metadata: Record<string, any>
}

interface ApiContentItem {
  id?: number
  type: 'research' | 'guide' | 'faq'
  title: string
  slug: string
  category: string
  category_slug: string
  tag?: string | null
  excerpt?: string | null
  body?: string | null
  image_index?: number
  image_url?: string | null
  read_minutes?: number
  read_label?: string | null
  views?: number
  downloads?: number
  comments?: number
  metadata?: Record<string, any> | null
  published_label?: string | null
  time_ago?: string | null
  href?: string
  author?: { name?: string | null; initial?: string | null; badge?: string | null } | null
}

interface ContentCategory {
  name: string
  slug: string
  count: number
}

interface ContentTopic {
  name: string
  count: number
}

interface ContentSortOption {
  value: string
  label: string
}

interface ContentFilterOptions {
  categories: ContentCategory[]
  tags: string[]
  compounds: string[]
  sorts: ContentSortOption[]
  date_bounds?: {
    from?: string | null
    to?: string | null
  }
}

interface ContentIndexResponse {
  data: ApiContentItem[]
  meta?: PaginationMeta & {
    categories?: ContentCategory[]
    topics?: ContentTopic[]
    filters?: Partial<ContentFilterOptions>
  }
}

interface ContentDetailResponse {
  data: ApiContentItem
}

interface UiMemberProfile {
  id?: number
  name: string
  username: string
  slug: string
  initial: string
  color: string
  role: string
  group: string
  badge?: string | null
  bio: string
  location: string
  websiteUrl?: string | null
  isOnline: boolean
  isVerified: boolean
  isModerator: boolean
  joined: string
  lastActive: string
  interests: string[]
  stats: Record<string, any>
  badges: string[]
  href: string
  activities: UiMemberActivity[]
  tabData: MemberTabData
  avatarUrl?: string | null
}

interface UiMemberActivity {
  icon: string
  tone: string
  title: string
  subtitle?: string | null
  category?: string | null
  time: string
}

interface UiMemberTabItem {
  type: string
  title: string
  text?: string | null
  href?: string | null
  time?: string | null
}

interface MemberTabData {
  overview?: UiMemberTabItem[]
  activity?: UiMemberActivity[]
  posts?: UiMemberTabItem[]
  reviews?: UiMemberTabItem[]
  guides?: UiMemberTabItem[]
  badges?: string[]
}

type MemberTabKey = 'overview' | 'activity' | 'posts' | 'reviews' | 'guides' | 'badges'

interface ApiMemberProfile {
  id?: number
  display_name: string
  username: string
  slug: string
  initial: string
  color?: string | null
  role_label?: string | null
  group_label?: string | null
  badge_label?: string | null
  bio?: string | null
  location?: string | null
  website_url?: string | null
  is_online?: boolean
  is_verified?: boolean
  is_moderator?: boolean
  joined_label?: string | null
  last_active_label?: string | null
  interests?: string[]
  stats?: Record<string, unknown>
  avatar?: string | null
  badges?: string[]
  href?: string
  activities?: ApiMemberActivity[]
  tab_data?: {
    overview?: UiMemberTabItem[]
    activity?: ApiMemberActivity[]
    posts?: UiMemberTabItem[]
    reviews?: UiMemberTabItem[]
    guides?: UiMemberTabItem[]
    badges?: string[]
  }
}

interface ApiMemberActivity {
  icon?: string | null
  tone?: string | null
  title: string
  subtitle?: string | null
  category?: string | null
  time_ago?: string | null
}

interface MemberIndexResponse {
  data: ApiMemberProfile[]
  meta?: PaginationMeta & {
    stats?: { total?: number; online?: number }
    top_contributors?: ApiMemberProfile[]
    online_members?: ApiMemberProfile[]
  }
}

interface MemberDetailResponse {
  data: ApiMemberProfile
}

interface UiMessageThread {
  id: number
  participant: UiMemberProfile
  preview: string
  time: string
  unread: number
  messages: UiMessage[]
}

interface UiMessage {
  id?: number
  side: 'in' | 'out'
  text: string
  time: string
  attachmentName?: string | null
  attachmentMeta?: Record<string, any>
  attachmentLabel: string
  avatarInitial: string
  avatarColor: string
}

interface ApiMessageThread {
  id: number
  participant: ApiMemberProfile
  preview?: string | null
  unread_count?: number
  last_message_label?: string | null
  messages?: ApiMessage[]
}

interface ApiMessage {
  id?: number
  side: 'in' | 'out'
  body: string
  attachment_name?: string | null
  attachment_meta?: Record<string, unknown> | null
  sender?: ApiMemberProfile | null
  sent_label?: string | null
  time_ago?: string | null
}

interface MessageIndexResponse {
  data: ApiMessageThread[]
}

interface MessageDetailResponse {
  data: ApiMessageThread
}

interface UiNotification {
  id?: number
  slug: string
  title: string
  text: string
  body: string
  bodyParagraphs: string[]
  category: string
  categorySlug: string
  time: string
  icon: string
  tone: string
  unread: boolean
  href: string
  detailHref: string
  author: string
  views: string
}

interface ApiNotification {
  id?: number
  title: string
  slug: string
  category: string
  category_slug: string
  icon: string
  tone: string
  excerpt?: string | null
  body?: string | null
  source_url?: string | null
  views?: number
  published_label?: string | null
  time_ago?: string | null
  read_at?: string | null
  unread?: boolean
  detail_href?: string
  href?: string
  author?: { name?: string | null; initial?: string | null } | null
}

interface NotificationStats {
  total: number
  unread: number
  announcements: number
  lab_results: number
  discussions: number
  vendors: number
  replies_unread?: number
  mentions_unread?: number
  messages_unread?: number
  message_unread?: number
  system_unread?: number
}

interface NotificationCategory {
  name: string
  slug: string
  icon: string
  count: number
  unread: number
  latest?: string | null
}

interface NotificationIndexResponse {
  data: ApiNotification[]
  meta?: PaginationMeta & {
    stats?: NotificationStats
    categories?: NotificationCategory[]
  }
}

interface NotificationDetailResponse {
  data: ApiNotification
}

interface UserSettingsPayload {
  email_notifications: boolean
  push_notifications: boolean
  sound_enabled: boolean
  show_online: boolean
  public_profile: boolean
  profile_visibility: 'everyone' | 'members_only' | 'nobody'
  direct_messages: 'everyone' | 'members_only' | 'nobody'
  show_read_topics: boolean
  show_typing: boolean
  show_recent_activity: boolean
  personalize_experience: boolean
  allow_analytics: boolean
  compact_discussions: boolean
  show_online_members: boolean
  remember_content_filters: boolean
  theme: 'light' | 'dark' | 'system'
  language: string
}

type BooleanUserSettingKey = {
  [K in keyof UserSettingsPayload]: UserSettingsPayload[K] extends boolean ? K : never
}[keyof UserSettingsPayload]

interface UserSessionSummary {
  id: string
  kind?: 'browser' | 'token'
  ipAddress?: string | null
  userAgent?: string | null
  lastActivity?: string | null
  isCurrent?: boolean
  name?: string
  createdAt?: string | null
  expiresAt?: string | null
}

interface ApiTokenSummary {
  id: number
  name: string
  abilities?: string[]
  last_used_at?: string | null
  expires_at?: string | null
  created_at?: string | null
}

interface SessionsResponse {
  sessions?: UserSessionSummary[]
  tokens?: UserSessionSummary[]
}

interface ApiTokenIndexResponse {
  data: ApiTokenSummary[]
}

interface TwoFactorStatus {
  enabled: boolean
  confirmed_at?: string | null
}

interface TwoFactorSetup {
  secret: string
  qr_code: string
  qr_code_url: string
}

interface LegalPageContent {
  title: string
  paragraphs: string[]
}

interface PublicSettingsResponse {
  telegram_url?: string | null
  legal_pages?: {
    terms?: LegalPageContent
    privacy?: LegalPageContent
    rules?: LegalPageContent
  }
}

interface UserActionsResponse {
  data: {
    followed_discussions?: string[]
    saved_discussions?: string[]
    bookmarked_content?: string[]
    bookmarked_products?: string[]
    followed_members?: string[]
  }
}

interface BlockedUsersResponse {
  data: ApiMemberProfile[]
}

type ComposeAttachmentType = 'image' | 'video' | 'link' | 'poll' | 'file'

const avatarColors = ['purple', 'blue', 'green', 'pink', 'orange', 'teal']

const apiDiscussions = ref<UiDiscussion[]>([])
const apiDetailDiscussion = ref<UiDiscussion | null>(null)
const apiReplies = ref<UiReply[]>([])
const apiCategories = ref<ApiCategory[]>([])
const apiDiscussionParticipants = ref<UiMemberProfile[]>([])
const apiSimilarDiscussions = ref<UiDiscussion[]>([])
const discussionsLoaded = ref(false)
const discussionPagination = ref<PaginationMeta | null>(null)
const discussionPage = ref(1)
const discussionSearch = ref('')
const activeCategory = ref('rules')
const discussionStatusMessage = ref('')
const showNewDiscussion = ref(false)
const creatingDiscussion = ref(false)
const discussionFormError = ref('')
const replyBody = ref('')
const submittingReply = ref(false)
const replyStatusMessage = ref('')
const discussionVoteLoading = ref(false)
const isEditingDiscussion = ref(false)
const editDiscussionTitle = ref('')
const editDiscussionBody = ref('')
const editDiscussionTag = ref('')
const editDiscussionCategory = ref('')
const discussionEditError = ref('')
const discussionEditSaving = ref(false)
const replyVoteLoading = ref('')
const showReportModal = ref(false)
const reportTarget = ref<{ type: 'discussion' } | { type: 'reply'; reply: UiReply } | null>(null)
const reportReason = ref('source-discussion')
const reportDetails = ref('')
const reportSubmitting = ref(false)
const replyAttachmentFile = ref<File | null>(null)
const replyAttachmentPreviewUrl = ref('')
const replyAttachmentGifUrl = ref('')

const discussionSort = ref<'latest' | 'replies' | 'views'>('latest')
const actionStatusMessage = ref('')
const followedDiscussionSlugs = ref<string[]>([])
const savedDiscussionSlugs = ref<string[]>([])
const followedMemberSlugs = ref<string[]>([])
const userActionsLoaded = ref(false)
const apiLabResults = ref<UiLabResult[]>([])
const apiDetailLabResult = ref<UiLabResult | null>(null)
const labResultsLoaded = ref(false)
const labPagination = ref<PaginationMeta | null>(null)
const labPage = ref(1)
const labStats = ref<LabStats | null>(null)
const labSort = ref<'latest' | 'purity' | 'compound'>('latest')
const labDetailTab = ref<'overview' | 'certificate' | 'raw' | 'batch'>('overview')
const labSearch = ref('')
const labTypeFilter = ref('')
const labCompoundFilter = ref('')
const labVendorFilter = ref('')
const labLabFilter = ref('')
const labFilterOptions = ref<LabFilterOptions>({
  compound_types: [],
  compounds: [],
  vendors: [],
  labs: [],
})
const labStatusMessage = ref('')
const showSubmitLabResult = ref(false)
const submittingLabResult = ref(false)
const labFormError = ref('')
const apiVendors = ref<UiVendor[]>([])
const apiTopVendors = ref<UiVendor[]>([])
const apiDetailVendor = ref<UiVendor | null>(null)
const apiVendorReviews = ref<UiVendorReview[]>([])
const vendorsLoaded = ref(false)
const vendorPagination = ref<PaginationMeta | null>(null)
const vendorPage = ref(1)
const vendorSearch = ref('')
const vendorStatusFilter = ref('')
const vendorRatingFilter = ref('')
const vendorTagFilter = ref('')
const vendorFilterOptions = ref<VendorFilterOptions>({
  statuses: [],
  ratings: [],
  tags: [],
})
const vendorStatusMessage = ref('')
const apiMyVendor = ref<UiVendor | null>(null)
const vendorPortalAccessApproved = ref(false)
const vendorAccessRequested = ref(false)
const vendorPortalLoaded = ref(false)
const vendorPortalStatusMessage = ref('')
const vendorPortalFormError = ref('')
const savingVendorProfile = ref(false)
const vendorImageFileInput = ref<HTMLInputElement | null>(null)
const uploadingVendorImage = ref(false)
const savingVendorDocument = ref(false)
const vendorDocumentFormError = ref('')
const vendorDocumentStatusMessage = ref('')
const vendorDocumentFileInput = ref<HTMLInputElement | null>(null)
const vendorDocumentFilePreview = ref('')
const vendorDocumentForm = ref({
  title: '',
  category: '',
  description: '',
})
const vendorPortalForm = ref({
  name: '',
  slug: '',
  description: '',
  website_url: '',
  image_url: '',
  contact_email: '',
  contact_telegram: '',
  contact_signal: '',
  contact_discord: '',
  support_url: '',
  response_policy: '',
  public_contact_notes: '',
  tags: '',
})
const membershipPlans = ref<MembershipPlan[]>([])
const membershipTier = ref('free')
const membershipStatus = ref<Record<string, unknown> | null>(null)
const billingInterval = ref<'month' | 'year'>('year')
const paymentStatusMessage = ref('')
const subscribing = ref(false)

const showUpgradePrompt = computed(() => authStore.isAuthenticated && membershipTier.value !== 'paid')

interface MembershipPlan {
  id: number
  name: string
  slug: string
  description: string
  price_monthly: number
  price_yearly: number
  features: string[]
}

const vendorProductFormError = ref('')
const vendorProductStatusMessage = ref('')
const savingVendorProduct = ref(false)
const editingVendorProductId = ref<number | null>(null)
const vendorProductImageInput = ref<HTMLInputElement | null>(null)
const vendorProductImageFile = ref<File | null>(null)
const vendorProductImagePreview = ref('')
const vendorProductForm = ref({
  name: '',
  slug: '',
  category: '',
  strength: '',
  package_size: '',
  purity_label: '',
  description: '',
  price: '',
  variants: [] as ProductVariant[],
  currency_code: 'USD',
  availability: 'in_stock',
  image_url: '',
  tags: '',
  sort_order: '0',
  status: 'published',
})
const vendorReviewStatusMessage = ref('')
const vendorReviewFormError = ref('')
const submittingVendorReview = ref(false)
const vendorReviewPhotoInput = ref<HTMLInputElement | null>(null)
const vendorReviewPhotos = ref<File[]>([])
const vendorReviewPhotoPreviews = ref<string[]>([])
const markingReviewHelpful = ref<number | undefined>()
const respondingReviewId = ref<number | undefined>()
const reviewResponseText = ref('')
const submittingReviewResponse = ref(false)
const vendorSort = ref<'rating' | 'reviews' | 'name'>('rating')
const vendorReviewRatingFilter = ref('')
const vendorReviewProductFilter = ref('')
const vendorReviewTimeFilter = ref('all')
const vendorReviewSort = ref<'helpful' | 'recent'>('helpful')
const vendorProductSearch = ref('')
const vendorProductCategoryFilter = ref('')
const vendorProductAvailabilityFilter = ref('')
const vendorProductSort = ref<'featured' | 'price-low' | 'price-high' | 'name'>('featured')
const vendorDetailTab = ref<'overview' | 'reviews' | 'products' | 'documents' | 'about'>('products')
const vendorStats = ref<VendorStats>({
  vendors_reviewed: 0,
  total_reviews: 0,
  average_rating: 0,
  would_buy_again: 0,
})
const apiAnnouncements = ref<UiAnnouncement[]>([])
const apiDetailAnnouncement = ref<UiAnnouncement | null>(null)
const announcementsLoaded = ref(false)
const announcementFilter = ref('all')
const announcementStatusMessage = ref('')
const announcementCategories = ref<AnnouncementCategory[]>([])
const announcementStats = ref<AnnouncementStats>({
  total: 0,
  pinned: 0,
  this_month: 0,
  total_views: 0,
  total_comments: 0,
})
const apiResearchArticles = ref<UiContentItem[]>([])
const apiDetailResearchArticle = ref<UiContentItem | null>(null)
const apiGuides = ref<UiContentItem[]>([])
const apiDetailGuide = ref<UiContentItem | null>(null)
const apiFaqs = ref<UiContentItem[]>([])
const researchPagination = ref<PaginationMeta | null>(null)
const guidePagination = ref<PaginationMeta | null>(null)
type ContentKind = 'research' | 'guide' | 'faq'

const contentCategories = ref<Record<ContentKind, ContentCategory[]>>({
  research: [],
  guide: [],
  faq: [],
})
const contentTopics = ref<Record<ContentKind, ContentTopic[]>>({
  research: [],
  guide: [],
  faq: [],
})
const emptyContentFilterOptions = (): ContentFilterOptions => ({
  categories: [],
  tags: [],
  compounds: [],
  sorts: [{ value: 'latest', label: 'Latest Added' }],
  date_bounds: {},
})
const contentFilterOptions = ref<Record<ContentKind, ContentFilterOptions>>({
  research: emptyContentFilterOptions(),
  guide: emptyContentFilterOptions(),
  faq: emptyContentFilterOptions(),
})
const contentLoaded = ref(false)
const contentStatusMessage = ref('')
const activeResearchCategory = ref('')
const activeGuideCategory = ref('')
const researchSearch = ref('')
const researchTagFilter = ref('')
const researchCompoundFilter = ref('')
const researchSort = ref('title')
const researchDetailTab = ref<'article' | 'data' | 'references' | 'comments'>('article')
const researchPublishedFrom = ref('')
const researchPublishedTo = ref('')
const researchPage = ref(1)
const guideSearch = ref('')
const guidePage = ref(1)
const bookmarkedContentSlugs = ref<string[]>([])
const bookmarkedProductKeys = ref<string[]>([])
const apiMembers = ref<UiMemberProfile[]>([])
const apiTopContributorMembers = ref<UiMemberProfile[]>([])
const apiOnlineMemberSummaries = ref<UiMemberProfile[]>([])
const apiDetailMember = ref<UiMemberProfile | null>(null)
const memberStats = ref({ total: 0, online: 0 })
const membersLoaded = ref(false)
const memberPagination = ref<PaginationMeta | null>(null)
const memberPage = ref(1)
const memberSearch = ref('')
const memberFilter = ref<'all' | 'online' | 'moderators' | 'contributors'>('all')
const memberSort = ref<'active' | 'posts' | 'reputation'>('active')
const apiMessageThreads = ref<UiMessageThread[]>([])
const apiCurrentMessageThread = ref<UiMessageThread | null>(null)
const messagesLoaded = ref(false)
const messagesStatusMessage = ref('')
const messageSearch = ref('')
const messageRecipientSearch = ref('')
const messageBody = ref('')
const sendingMessage = ref(false)
const startingMessageUserId = ref<number | null>(null)
const showMessageSafetyNotice = ref(true)
const apiNotifications = ref<UiNotification[]>([])
const apiDetailNotification = ref<UiNotification | null>(null)
const notificationsLoaded = ref(false)
const notificationPagination = ref<PaginationMeta | null>(null)
const notificationPage = ref(1)
const notificationStatusMessage = ref('')
const activeNotificationFilter = ref('all')
const markingNotificationsRead = ref(false)
const notificationStats = ref<NotificationStats>({
  total: 0,
  unread: 0,
  announcements: 0,
  lab_results: 0,
  discussions: 0,
  vendors: 0,
})
const notificationCategories = ref<NotificationCategory[]>([])
const accountForm = ref({
  username: '',
  name: '',
  email: '',
  bio: '',
  timezone: '',
  locale: 'en',
  website_url: '',
})
const passwordSettingsForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})
const userSettings = ref<UserSettingsPayload>(defaultUserSettings())
const settingsLoaded = ref(false)
const savingSettings = ref(false)
const changingSettingsPassword = ref(false)
const settingsStatusMessage = ref('')
const userSessions = ref<UserSessionSummary[]>([])
const userApiTokens = ref<ApiTokenSummary[]>([])
const apiTokenForm = ref({
  name: '',
})
const newPlainApiToken = ref('')
const avatarFileInput = ref<HTMLInputElement | null>(null)
const uploadingAvatar = ref(false)
const exportingAccountData = ref(false)
const signingOutEverywhere = ref(false)
const twoFactorStatus = ref<TwoFactorStatus | null>(null)
const twoFactorSetup = ref<TwoFactorSetup | null>(null)
const twoFactorCode = ref('')
const twoFactorPassword = ref('')
const twoFactorRecoveryCodes = ref<string[]>([])
const loadingTwoFactor = ref(false)
const savingTwoFactor = ref(false)
const publicSettingsLoaded = ref(false)
const publicTelegramUrl = ref('https://t.me/peptidevendors')
const publicLegalPages = ref<Record<'terms' | 'privacy' | 'rules', LegalPageContent>>({
  terms: {
    title: 'Terms of Service',
    paragraphs: [
      'By using Peptide Vendors, you agree to use the community for lawful, educational discussion and to follow all community rules and moderation decisions.',
      'Content is provided by community members and administrators for informational purposes. You are responsible for verifying information before relying on it.',
      'Accounts may be restricted or removed for abuse, unsafe conduct, spam, evasion, or attempts to use the site as a marketplace.',
    ],
  },
  privacy: {
    title: 'Privacy Policy',
    paragraphs: [
      'Peptide Vendors keeps account data, profile preferences, messages, submissions, and moderation records only for operating the private community.',
      'We use your information to authenticate access, show community content, support account settings, prevent abuse, and improve reliability.',
      'Do not post private medical, payment, or identifying information in public areas. You can manage profile visibility, notifications, privacy, sessions, and API tokens from account settings.',
    ],
  },
  rules: {
    title: 'Community Rules',
    paragraphs: [
      'Keep discussion educational, evidence-led, and respectful. Personal attacks, harassment, spam, scams, and transaction coordination are not allowed.',
      'Vendor reviews and lab-result submissions should be specific, honest, and batch-aware where possible. Moderators may hide incomplete, unsafe, promotional, or unverifiable submissions.',
      'This community is for information sharing and harm-reduction context only. It is not medical advice, legal advice, or a marketplace.',
    ],
  },
})
const blockedUsers = ref<UiMemberProfile[]>([])
const blockedUsersLoaded = ref(false)
const blockingUserId = ref<number | null>(null)
const blockingUsername = ref(false)
const blockUserSearch = ref('')
const blockUsername = ref('')
const revokingSessionId = ref('')
const discussionTags = ['Discussion', 'Question', 'Guide', 'Review', 'Showcase', 'Tutorial', 'News', 'Tip']
const discussionFileInput = ref<HTMLInputElement | null>(null)
const discussionUploading = ref(false)
const newDiscussionKey = ref(0)
const newDiscussion = ref({
  title: '',
  body: '',
  category_slug: '',
  tag: '',
})
const newDiscussionDraftKey = 'pv:new-discussion-draft:v1'
const discussionDraftStatus = ref('Draft auto-saved locally')
const newLabResult = ref({
  compound_name: '',
  compound_type: '',
  use_case: '',
  vendor_name: '',
  batch_code: '',
  lab_name: '',
  tested_at: '',
  purity_percent: undefined as number | undefined,
  coa_filename: '',
  notes: '',
})
const newVendorReview = ref({
  rating: 0,
  title: '',
  body: '',
  would_buy_again: true,
})

const discussions = computed(() => {
  const items = [...apiDiscussions.value]

  if (discussionSort.value === 'replies') {
    return items.sort((a, b) => b.replies - a.replies)
  }

  if (discussionSort.value === 'views') {
    return items.sort((a, b) => parseCount(b.views) - parseCount(a.views))
  }

  return items
})
const replies = computed(() => apiReplies.value.filter(r => r.text !== '[deleted]'))
const discussionCategories = computed(() => apiCategories.value)
const vendors = computed(() => {
  const items = [...apiVendors.value]

  if (vendorSort.value === 'reviews') {
    return items.sort((a, b) => b.reviews - a.reviews)
  }

  if (vendorSort.value === 'name') {
    return items.sort((a, b) => a.name.localeCompare(b.name))
  }

  return items.sort((a, b) => Number.parseFloat(b.rating || '0') - Number.parseFloat(a.rating || '0'))
})
const reviews = computed(() => {
  let items = [...apiVendorReviews.value]

  if (vendorReviewRatingFilter.value) {
    items = items.filter(review => String(review.rating) === vendorReviewRatingFilter.value)
  }

  if (vendorReviewProductFilter.value) {
    items = items.filter(review => review.productName === vendorReviewProductFilter.value)
  }

  if (vendorReviewTimeFilter.value === 'recent') {
    items.sort((a, b) => (Date.parse(b.date) || 0) - (Date.parse(a.date) || 0))
  }

  if (vendorReviewSort.value === 'helpful') {
    items.sort((a, b) => b.helpful - a.helpful)
  }

  return items
})
const announcements = computed(() => apiAnnouncements.value)
const announcementPreview = computed(() => announcements.value.slice(0, 3))
const articles = computed(() => apiResearchArticles.value)
const guides = computed(() => apiGuides.value)
const faqs = computed(() => apiFaqs.value)
const popularTopics = computed(() => contentTopics.value.research.length > 0 ? contentTopics.value.research : contentTopics.value.guide)
const researchSortLabel = computed(() => contentFilterOptions.value.research.sorts.find(sort => sort.value === researchSort.value)?.label ?? 'Latest Added')
const memberEngagementScore = (member: UiMemberProfile) => Number(member.stats.posts ?? 0) + Number(member.stats.reviews ?? 0) + Number(member.stats.lab_reports ?? 0) + Number(member.stats.solutions ?? 0)
const members = computed(() => {
  let items = [...apiMembers.value]

  if (memberFilter.value === 'online') {
    items = items.filter(member => member.isOnline)
  } else if (memberFilter.value === 'moderators') {
    items = items.filter(member => member.isModerator)
  } else if (memberFilter.value === 'contributors') {
    items = items.filter(member => memberEngagementScore(member) > 0)
  }

  if (memberSort.value === 'posts') {
    return items.sort((a, b) => Number(b.stats.posts ?? 0) - Number(a.stats.posts ?? 0))
  }

  if (memberSort.value === 'reputation') {
    return items.sort((a, b) => memberEngagementScore(b) - memberEngagementScore(a))
  }

  return items.sort((a, b) => Number(b.isOnline) - Number(a.isOnline) || memberEngagementScore(b) - memberEngagementScore(a))
})
const memberSortLabel = computed(() => {
  if (memberSort.value === 'posts') return 'Most Posts'
  if (memberSort.value === 'reputation') return 'Reputation'
  return 'Recently Active'
})
const topContributors = computed(() => apiTopContributorMembers.value.length > 0
  ? apiTopContributorMembers.value
  : [...apiMembers.value]
    .sort((a, b) => memberEngagementScore(b) - memberEngagementScore(a))
    .slice(0, 5))
const onlineMembers = computed(() => apiOnlineMemberSummaries.value.length > 0
  ? apiOnlineMemberSummaries.value
  : apiMembers.value.filter(member => member.isOnline).slice(0, 6))
const chats = computed(() => {
  const search = messageSearch.value.trim().toLowerCase()
  if (!search) {
    return apiMessageThreads.value
  }

  return apiMessageThreads.value.filter(thread => [
    thread.participant.name,
    thread.participant.username,
    thread.preview,
  ].some(value => value.toLowerCase().includes(search)))
})
const messageRecipientOptions = computed(() => {
  const search = messageRecipientSearch.value.trim().toLowerCase()
  if (!search) {
    return []
  }

  return apiMembers.value
    .filter(member => Boolean(member.id) && member.id !== authStore.user?.id)
    .filter(member => [
      member.name,
      member.username,
      member.role,
      member.bio,
    ].some(value => (value ?? '').toLowerCase().includes(search)))
    .slice(0, 6)
})
const currentThread = computed(() => apiCurrentMessageThread.value)
const categoryFilters = computed<ApiCategory[]>(() => discussionCategories.value)
const discussionSortLabel = computed(() => {
  if (discussionSort.value === 'replies') return 'Most Replies'
  if (discussionSort.value === 'views') return 'Most Viewed'
  return 'Latest Activity'
})
const isFollowingDiscussion = computed(() => Boolean(detailDiscussion.value?.slug && followedDiscussionSlugs.value.includes(detailDiscussion.value.slug)))
const isSavedDiscussion = computed(() => Boolean(detailDiscussion.value?.slug && savedDiscussionSlugs.value.includes(detailDiscussion.value.slug)))
const currentDiscussionSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailDiscussion = computed(() => {
  return apiDetailDiscussion.value
    ?? discussions.value.find(topic => topic.slug === currentDiscussionSlug.value || topic.href.endsWith(`/${currentDiscussionSlug.value}`))
    ?? null
})
const detailParagraphs = computed(() => {
  const body = detailDiscussion.value?.body ?? detailDiscussion.value?.excerpt ?? ''
  return body.split(/\n+/).map(paragraph => paragraph.trim()).filter(Boolean)
})
const detailCategoryLabel = computed(() => detailDiscussion.value?.category || detailDiscussion.value?.tag || 'Discussion')
const replyAttachmentCount = computed(() => replies.value.filter(reply => Boolean(reply.file || reply.attachmentUrl)).length)
const discussionParticipants = computed<UiMemberProfile[]>(() => {
  if (apiDiscussionParticipants.value.length > 0) {
    return apiDiscussionParticipants.value
  }

  const participants = new Map<string, { name: string; initial: string; color: string }>()
  if (detailDiscussion.value) {
    participants.set(detailDiscussion.value.author, {
      name: detailDiscussion.value.author,
      initial: detailDiscussion.value.initial,
      color: detailDiscussion.value.color,
    })
  }
  replies.value.forEach((reply) => {
    participants.set(reply.author, {
      name: reply.author,
      initial: reply.initial,
      color: reply.color,
    })
  })

  return Array.from(participants.values()).map(member => ({
    id: undefined,
    name: member.name,
    username: member.name,
    slug: member.name,
    initial: member.initial,
    color: member.color,
    role: '',
    group: '',
    badge: null,
    bio: '',
    location: '',
    websiteUrl: null,
    isOnline: false,
    isVerified: false,
    isModerator: false,
    joined: '',
    lastActive: '',
    interests: [],
    stats: {},
    badges: [],
    href: '/members',
    activities: [],
    tabData: {},
  }))
})
const similarDiscussionTopics = computed(() => apiSimilarDiscussions.value.length > 0
  ? apiSimilarDiscussions.value
  : discussions.value
    .filter(topic => topic.slug !== currentDiscussionSlug.value && topic.href !== detailDiscussion.value?.href)
    .slice(0, 3))
const currentAnnouncementSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailAnnouncement = computed(() => {
  return apiDetailAnnouncement.value
    ?? announcements.value.find(announcement => announcement.slug === currentAnnouncementSlug.value || announcement.href.endsWith(`/${currentAnnouncementSlug.value}`))
    ?? null
})
const announcementParagraphs = computed(() => {
  const body = detailAnnouncement.value?.body ?? detailAnnouncement.value?.text ?? ''
  return body.split(/\n+/).map(paragraph => paragraph.trim()).filter(Boolean)
})
const currentContentSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailArticle = computed(() => {
  return apiDetailResearchArticle.value
    ?? articles.value.find(article => article.slug === currentContentSlug.value || article.href.endsWith(`/${currentContentSlug.value}`))
    ?? null
})
const detailGuide = computed(() => {
  return apiDetailGuide.value
    ?? guides.value.find(guide => guide.slug === currentContentSlug.value || guide.href.endsWith(`/${currentContentSlug.value}`))
    ?? null
})
const relatedArticles = computed(() => {
  const currentSlug = detailArticle.value?.slug

  return articles.value
    .filter(item => item.slug !== currentSlug)
    .slice(0, 3)
})
const relatedGuides = computed(() => {
  const currentSlug = detailGuide.value?.slug

  return guides.value
    .filter(item => item.slug !== currentSlug)
    .slice(0, 3)
})
const detailGuideSteps = computed(() => {
  const steps = detailGuide.value?.metadata?.steps
  return Array.isArray(steps) ? steps : []
})
const articleBodyBlocks = computed(() => parseContentBlocks(detailArticle.value?.body ?? ''))
const guideBodyBlocks = computed(() => parseContentBlocks(detailGuide.value?.body ?? ''))
const articleHeadings = computed(() => articleBodyBlocks.value.filter(block => block.kind === 'heading').map(block => block.text))
const guideHeadings = computed(() => guideBodyBlocks.value.filter(block => block.kind === 'heading').map(block => block.text))
const currentMemberSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailMember = computed(() => {
  return apiDetailMember.value
    ?? apiMembers.value.find(member => member.slug === currentMemberSlug.value || member.href.endsWith(`/${currentMemberSlug.value}`))
    ?? null
})
const blockableMembers = computed(() => {
  const currentUserId = Number(authUserValue('id') ?? 0)
  const blockedIds = new Set(blockedUsers.value.map(member => member.id).filter(Boolean))
  const search = blockUserSearch.value.trim().toLowerCase()

  return apiMembers.value
    .filter(member => member.id && member.id !== currentUserId && !blockedIds.has(member.id))
    .filter(member => !search || [member.name, member.username, member.role, member.group].some(value => value.toLowerCase().includes(search)))
    .slice(0, 8)
})

function defaultUserSettings(): UserSettingsPayload {
  return {
    email_notifications: true,
    push_notifications: true,
    sound_enabled: true,
    show_online: true,
    public_profile: true,
    profile_visibility: 'everyone',
    direct_messages: 'members_only',
    show_read_topics: true,
    show_typing: true,
    show_recent_activity: true,
    personalize_experience: true,
    allow_analytics: false,
    compact_discussions: false,
    show_online_members: true,
    remember_content_filters: true,
    theme: 'dark',
    language: 'en',
  }
}

function userRecord(): Record<string, any> {
  return (authStore.user as Record<string, any> | null) ?? {}
}

function backendAssetOrigin(): string {
  const configured = String(import.meta.env.VITE_API_URL || '').replace(/\/$/, '')

  if (configured) {
    return configured
  }

  if (window.location.port.startsWith('517')) {
    return `${window.location.protocol}//${window.location.hostname}:8001`
  }

  return window.location.origin
}

const countryFlags: Record<string, string> = {
  china: '🇨🇳', usa: '🇺🇸', 'united states': '🇺🇸', uk: '🇬🇧', 'united kingdom': '🇬🇧',
  canada: '🇨🇦', australia: '🇦🇺', germany: '🇩🇪', france: '🇫🇷', netherlands: '🇳🇱',
  india: '🇮🇳', japan: '🇯🇵', 'south korea': '🇰🇷', singapore: '🇸🇬', switzerland: '🇨🇭',
  spain: '🇪🇸', italy: '🇮🇹', mexico: '🇲🇽', brazil: '🇧🇷', thailand: '🇹🇭',
  poland: '🇵🇱', sweden: '🇸🇪', norway: '🇳🇴', denmark: '🇩🇰', finland: '🇫🇮',
}

function countryFlag(country: string): string {
  return countryFlags[country.trim().toLowerCase()] ?? ''
}

function assetUrl(value?: string | null): string {
  const path = String(value || '')

  if (!path) {
    return ''
  }

  if (/^https?:\/\/localhost\/storage\//i.test(path)) {
    return `${backendAssetOrigin()}${new URL(path).pathname}`
  }

  if (/^(https?:|data:|blob:)/i.test(path)) {
    return path
  }

  if (path.startsWith('/storage/')) {
    return `${backendAssetOrigin()}${path}`
  }

  return path
}

function extractPagination(meta?: PaginationMeta | null): PaginationMeta | null {
  if (!meta || meta.current_page === undefined || meta.last_page === undefined) {
    return null
  }

  return {
    current_page: Number(meta.current_page ?? 1),
    last_page: Number(meta.last_page ?? 1),
    per_page: Number(meta.per_page ?? 0),
    total: Number(meta.total ?? 0),
    from: meta.from ?? null,
    to: meta.to ?? null,
  }
}

function paginationText(meta: PaginationMeta | null, noun: string): string {
  if (!meta || !meta.total) {
    return `Showing 0 ${noun}`
  }

  return `Showing ${meta.from ?? 1} to ${meta.to ?? 0} of ${meta.total} ${noun}`
}

async function loadPublicCommunitySettings(force = false): Promise<void> {
  if (publicSettingsLoaded.value && !force) {
    return
  }

  try {
    const response = await api.get<PublicSettingsResponse>('/api/v1/settings/public', {
      cacheTTL: 60000,
      skipDeduplication: true,
    })
    publicTelegramUrl.value = response.data.telegram_url || publicTelegramUrl.value
    publicLegalPages.value = {
      terms: response.data.legal_pages?.terms ?? publicLegalPages.value.terms,
      privacy: response.data.legal_pages?.privacy ?? publicLegalPages.value.privacy,
      rules: response.data.legal_pages?.rules ?? publicLegalPages.value.rules,
    }
    publicSettingsLoaded.value = true
  } catch {
    publicSettingsLoaded.value = true
  }
}

function setAuthUser(user: Record<string, any>): void {
  authStore.user = user as any
  localStorage.setItem('user', JSON.stringify(user))
}

function hydrateSettingsFromUser(user: Record<string, any>): void {
  accountForm.value = {
    username: String(user.username ?? ''),
    name: String(user.name ?? user.username ?? ''),
    email: String(user.email ?? ''),
    bio: String(user.bio ?? ''),
    timezone: String(user.timezone ?? ''),
    locale: String(user.locale ?? 'en'),
    website_url: String(user.website_url ?? ''),
  }

  const defaults = defaultUserSettings()
  userSettings.value = {
    email_notifications: Boolean(user.email_notifications ?? defaults.email_notifications),
    push_notifications: Boolean(user.push_notifications ?? defaults.push_notifications),
    sound_enabled: Boolean(user.sound_enabled ?? defaults.sound_enabled),
    show_online: Boolean(user.show_online ?? defaults.show_online),
    public_profile: Boolean(user.public_profile ?? defaults.public_profile),
    profile_visibility: (user.profile_visibility ?? defaults.profile_visibility) as UserSettingsPayload['profile_visibility'],
    direct_messages: (user.direct_messages ?? defaults.direct_messages) as UserSettingsPayload['direct_messages'],
    show_read_topics: Boolean(user.show_read_topics ?? defaults.show_read_topics),
    show_typing: Boolean(user.show_typing ?? defaults.show_typing),
    show_recent_activity: Boolean(user.show_recent_activity ?? defaults.show_recent_activity),
    personalize_experience: Boolean(user.personalize_experience ?? defaults.personalize_experience),
    allow_analytics: Boolean(user.allow_analytics ?? defaults.allow_analytics),
    compact_discussions: Boolean(user.compact_discussions ?? defaults.compact_discussions),
    show_online_members: Boolean(user.show_online_members ?? defaults.show_online_members),
    remember_content_filters: Boolean(user.remember_content_filters ?? defaults.remember_content_filters),
    theme: (user.theme ?? defaults.theme) as UserSettingsPayload['theme'],
    language: String(user.language ?? defaults.language),
  }
}

async function loadUserSettings(): Promise<void> {
  if (!page.value.startsWith('settings')) {
    return
  }

  settingsStatusMessage.value = ''

  if (!authStore.isAuthenticated) {
    settingsLoaded.value = true
    userSessions.value = []
    userApiTokens.value = []
    return
  }

  try {
    const response = await api.get<Record<string, any>>('/api/v1/user', {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    setAuthUser(response.data)
    hydrateSettingsFromUser(response.data)
    settingsLoaded.value = true
  } catch {
    settingsStatusMessage.value = 'Unable to load account settings.'
    settingsLoaded.value = true
  }

  await Promise.all([
    loadUserSessions(),
    loadUserApiTokens(),
    loadTwoFactorStatus(),
  ])
}

async function loadUserSessions(): Promise<void> {
  if (!authStore.isAuthenticated) {
    return
  }

  try {
    const response = await api.get<SessionsResponse>('/api/v1/user/sessions', {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    userSessions.value = [
      ...(response.data.sessions ?? []).map(session => ({ ...session, kind: (session.kind ?? 'browser') as UserSessionSummary['kind'] })),
      ...(response.data.tokens ?? []).map(session => ({ ...session, kind: (session.kind ?? 'token') as UserSessionSummary['kind'] })),
    ]
  } catch {
    userSessions.value = []
  }
}

async function loadUserApiTokens(): Promise<void> {
  if (!authStore.isAuthenticated) {
    return
  }

  try {
    const response = await api.get<ApiTokenIndexResponse>('/api/v1/user/api-tokens', {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    userApiTokens.value = response.data.data
  } catch {
    userApiTokens.value = []
  }
}

async function saveUserSettings(payload: Partial<UserSettingsPayload> = userSettings.value): Promise<void> {
  if (!authStore.isAuthenticated) {
    settingsStatusMessage.value = 'Please log in to update settings.'
    return
  }

  savingSettings.value = true
  settingsStatusMessage.value = ''

  try {
    const response = await api.patch<{ user?: Record<string, any> }>('/api/v1/user', payload)
    if (response.data.user) {
      setAuthUser(response.data.user)
      hydrateSettingsFromUser(response.data.user)
    }
    settingsStatusMessage.value = 'Settings saved.'
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, 'Unable to save settings.')
  } finally {
    savingSettings.value = false
  }
}

async function saveAccountProfile(): Promise<void> {
  if (!authStore.isAuthenticated) {
    settingsStatusMessage.value = 'Please log in to update your account.'
    return
  }

  savingSettings.value = true
  settingsStatusMessage.value = ''

  try {
    const response = await api.patch<{ user?: Record<string, any> }>('/api/v1/user/profile', {
      username: accountForm.value.username,
      name: accountForm.value.name,
      email: accountForm.value.email,
      bio: accountForm.value.bio,
      timezone: accountForm.value.timezone,
      locale: accountForm.value.locale,
      website_url: accountForm.value.website_url,
    })
    if (response.data.user) {
      setAuthUser(response.data.user)
      hydrateSettingsFromUser(response.data.user)
    }
    settingsStatusMessage.value = 'Profile saved.'
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, 'Unable to save profile.')
  } finally {
    savingSettings.value = false
  }
}

async function changeSettingsPassword(): Promise<void> {
  if (!authStore.isAuthenticated) {
    settingsStatusMessage.value = 'Please log in to change your password.'
    return
  }

  if (passwordSettingsForm.value.new_password !== passwordSettingsForm.value.new_password_confirmation) {
    settingsStatusMessage.value = 'Password confirmation does not match.'
    return
  }

  changingSettingsPassword.value = true
  settingsStatusMessage.value = ''

  try {
    await api.post('/api/v1/user/change-password', passwordSettingsForm.value)
    passwordSettingsForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: '',
    }
    settingsStatusMessage.value = 'Password updated.'
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, 'Unable to update password.')
  } finally {
    changingSettingsPassword.value = false
  }
}

async function toggleUserSetting(key: BooleanUserSettingKey): Promise<void> {
  const next = { ...userSettings.value, [key]: !userSettings.value[key] }
  userSettings.value = next
  await saveUserSettings({ [key]: next[key] } as Partial<UserSettingsPayload>)
}

async function setUserSetting<K extends keyof UserSettingsPayload>(key: K, value: UserSettingsPayload[K]): Promise<void> {
  userSettings.value = { ...userSettings.value, [key]: value }
  await saveUserSettings({ [key]: value } as Partial<UserSettingsPayload>)
}

async function createApiToken(): Promise<void> {
  if (!authStore.isAuthenticated) {
    settingsStatusMessage.value = 'Please log in to create API tokens.'
    return
  }

  settingsStatusMessage.value = ''
  const name = apiTokenForm.value.name.trim()

  if (!name) {
    settingsStatusMessage.value = 'Enter a token name first.'
    return
  }

  try {
    const response = await api.post<{ plain_text_token: string }>('/api/v1/user/api-tokens', {
      name,
      abilities: ['*'],
    })
    newPlainApiToken.value = response.data.plain_text_token
    apiTokenForm.value.name = ''
    settingsStatusMessage.value = 'API token created.'
    await loadUserApiTokens()
    await loadUserSessions()
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, 'Unable to create API token.')
  }
}

async function deleteApiToken(tokenId: number): Promise<void> {
  if (!authStore.isAuthenticated) {
    return
  }

  const isCurrentToken = userSessions.value.some(session => session.kind === 'token' && String(session.id) === String(tokenId) && session.isCurrent)

  try {
    await api.delete(`/api/v1/user/api-tokens/${tokenId}`)

    if (isCurrentToken) {
      authStore.user = null as any
      localStorage.removeItem('user')
      localStorage.removeItem('auth_token')
      await router.push('/login')
      return
    }

    await loadUserApiTokens()
    await loadUserSessions()
    settingsStatusMessage.value = 'API token revoked.'
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, 'Unable to revoke API token.')
  }
}

async function revokeUserSession(session: UserSessionSummary): Promise<void> {
  if (!authStore.isAuthenticated || !session.id) {
    return
  }

  revokingSessionId.value = session.id
  settingsStatusMessage.value = ''

  try {
    await api.delete(`/api/v1/user/sessions/${encodeURIComponent(session.id)}`, {
      skipDeduplication: true,
    })

    if (session.isCurrent) {
      authStore.user = null as any
      localStorage.removeItem('user')
      localStorage.removeItem('auth_token')
      await router.push('/login')
      return
    }

    await Promise.all([loadUserSessions(), loadUserApiTokens()])
    settingsStatusMessage.value = session.kind === 'browser' ? 'Session revoked.' : 'Token revoked.'
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, 'Unable to revoke session.')
  } finally {
    revokingSessionId.value = ''
  }
}

async function copyPlainApiToken(): Promise<void> {
  if (!newPlainApiToken.value) {
    return
  }

  try {
    await navigator.clipboard.writeText(newPlainApiToken.value)
    settingsStatusMessage.value = 'API token copied.'
  } catch {
    settingsStatusMessage.value = 'Copy failed. Select the token text manually.'
  }
}

function settingsApiError(error: unknown, fallback: string): string {
  const apiError = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
  const errors = apiError.response?.data?.errors

  return errors ? Object.values(errors)[0]?.[0] ?? fallback : apiError.response?.data?.message ?? fallback
}

async function uploadProfileAvatar(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) {
    return
  }

  uploadingAvatar.value = true
  settingsStatusMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('avatar', file)
    const response = await api.post<{ user?: Record<string, any> }>('/api/v1/user/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      skipDeduplication: true,
    })
    if (response.data.user) {
      setAuthUser(response.data.user)
      hydrateSettingsFromUser(response.data.user)
    }
    settingsStatusMessage.value = 'Profile photo updated.'
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, 'Unable to upload profile photo.')
  } finally {
    uploadingAvatar.value = false
    input.value = ''
  }
}

async function loadTwoFactorStatus(): Promise<void> {
  if (!authStore.isAuthenticated) {
    twoFactorStatus.value = null
    return
  }

  loadingTwoFactor.value = true

  try {
    const response = await api.get<TwoFactorStatus>('/api/v1/2fa/status', {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    twoFactorStatus.value = response.data
  } catch {
    twoFactorStatus.value = null
  } finally {
    loadingTwoFactor.value = false
  }
}

async function startTwoFactorSetup(): Promise<void> {
  if (!authStore.isAuthenticated) {
    settingsStatusMessage.value = 'Please log in to manage two-factor authentication.'
    return
  }

  savingTwoFactor.value = true
  settingsStatusMessage.value = ''
  twoFactorRecoveryCodes.value = []

  try {
    const response = await api.post<TwoFactorSetup>('/api/v1/2fa/setup', {}, {
      skipDeduplication: true,
    })
    twoFactorSetup.value = response.data
    twoFactorCode.value = ''
    settingsStatusMessage.value = 'Scan the QR code, then enter the 6-digit code from your authenticator app.'
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, 'Unable to start 2FA setup.')
  } finally {
    savingTwoFactor.value = false
  }
}

async function confirmTwoFactorSetup(): Promise<void> {
  if (!twoFactorCode.value.trim()) {
    settingsStatusMessage.value = 'Enter the 6-digit authenticator code.'
    return
  }

  savingTwoFactor.value = true
  settingsStatusMessage.value = ''

  try {
    const response = await api.post<{ message?: string; recovery_codes?: string[] }>('/api/v1/2fa/confirm', {
      code: twoFactorCode.value.trim(),
    }, {
      skipDeduplication: true,
    })
    twoFactorRecoveryCodes.value = response.data.recovery_codes ?? []
    twoFactorSetup.value = null
    twoFactorCode.value = ''
    settingsStatusMessage.value = response.data.message ?? 'Two-factor authentication enabled.'
    await loadTwoFactorStatus()
    await authStore.fetchUser()
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, 'Unable to confirm 2FA setup.')
  } finally {
    savingTwoFactor.value = false
  }
}

async function disableTwoFactor(): Promise<void> {
  if (!twoFactorPassword.value) {
    settingsStatusMessage.value = 'Enter your current password to disable 2FA.'
    return
  }

  savingTwoFactor.value = true
  settingsStatusMessage.value = ''

  try {
    await api.post('/api/v1/2fa/disable', {
      password: twoFactorPassword.value,
    }, {
      skipDeduplication: true,
    })
    twoFactorPassword.value = ''
    twoFactorRecoveryCodes.value = []
    settingsStatusMessage.value = 'Two-factor authentication disabled.'
    await loadTwoFactorStatus()
    await authStore.fetchUser()
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, 'Unable to disable 2FA.')
  } finally {
    savingTwoFactor.value = false
  }
}

function cancelTwoFactorSetup(): void {
  twoFactorSetup.value = null
  twoFactorCode.value = ''
  twoFactorRecoveryCodes.value = []
  settingsStatusMessage.value = ''
}

async function exportAccountData(): Promise<void> {
  if (!authStore.isAuthenticated) {
    settingsStatusMessage.value = 'Please log in to export account data.'
    return
  }

  exportingAccountData.value = true
  settingsStatusMessage.value = ''

  try {
    await Promise.all([
      loadUserSessions(),
      loadUserApiTokens(),
    ])

    const payload = {
      exported_at: new Date().toISOString(),
      user: userRecord(),
      settings: userSettings.value,
      sessions: userSessions.value,
      api_tokens: userApiTokens.value.map(token => ({
        id: token.id,
        name: token.name,
        abilities: token.abilities ?? [],
        last_used_at: token.last_used_at ?? null,
        expires_at: token.expires_at ?? null,
        created_at: token.created_at ?? null,
      })),
    }
    const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    const name = accountName().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || 'account'
    link.href = url
    link.download = `peptide-vendors-${name}-account-data.json`
    link.click()
    URL.revokeObjectURL(url)
    settingsStatusMessage.value = 'Account data exported.'
  } catch {
    settingsStatusMessage.value = 'Unable to export account data.'
  } finally {
    exportingAccountData.value = false
  }
}

async function signOutEverywhere(): Promise<void> {
  if (!authStore.isAuthenticated) {
    return
  }

  signingOutEverywhere.value = true
  settingsStatusMessage.value = ''

  try {
    await api.post('/api/v1/logout-all', {}, {
      skipDeduplication: true,
    })
  } catch {
    // Local logout still protects the browser even if the server call fails.
  } finally {
    authStore.user = null as any
    localStorage.removeItem('user')
    localStorage.removeItem('auth_token')
    signingOutEverywhere.value = false
    await router.push('/login')
  }
}

function parseContentBlocks(body: string): Array<{ kind: 'heading' | 'paragraph' | 'list'; text: string; items?: string[] }> {
  const blocks: Array<{ kind: 'heading' | 'paragraph' | 'list'; text: string; items?: string[] }> = []
  let listItems: string[] = []

  const flushList = () => {
    if (listItems.length > 0) {
      blocks.push({ kind: 'list', text: listItems.join('|'), items: listItems })
      listItems = []
    }
  }

  body.split(/\n+/).map(line => line.trim()).filter(Boolean).forEach((line) => {
    if (line.startsWith('- ')) {
      listItems.push(line.slice(2))
      return
    }

    flushList()

    if (line.startsWith('## ')) {
      blocks.push({ kind: 'heading', text: line.slice(3) })
      return
    }

    if (line.startsWith('### ')) {
      blocks.push({ kind: 'heading', text: line.slice(4) })
      return
    }

    blocks.push({ kind: 'paragraph', text: line })
  })

  flushList()

  return blocks
}

function formatCount(value: number | undefined): string {
  const count = Number(value ?? 0)

  if (count >= 1000) {
    return `${(count / 1000).toFixed(count >= 10000 ? 0 : 1).replace(/\.0$/, '')}K`
  }

  return String(count)
}

function formatDate(value: string): string {
  if (!value) {
    return 'Unknown'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return date.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function parseCount(value: string | number | undefined): number {
  if (typeof value === 'number') {
    return value
  }

  const raw = String(value ?? '0').trim().toLowerCase()
  const multiplier = raw.endsWith('k') ? 1000 : 1
  const numeric = Number.parseFloat(raw.replace(/[^0-9.]/g, ''))

  return Number.isFinite(numeric) ? numeric * multiplier : 0
}

function readLocalList(key: string): string[] {
  try {
    return JSON.parse(localStorage.getItem(key) || '[]')
  } catch {
    return []
  }
}

function writeLocalList(key: string, values: string[]): void {
  localStorage.setItem(key, JSON.stringify(values))
}

function toggleLocalValue(list: typeof followedDiscussionSlugs, key: string, value: string, addedMessage: string, removedMessage: string): void {
  const exists = list.value.includes(value)
  list.value = exists ? list.value.filter(item => item !== value) : [...list.value, value]
  writeLocalList(key, list.value)
  actionStatusMessage.value = exists ? removedMessage : addedMessage
}

function loadLocalActionState(): void {
  followedDiscussionSlugs.value = readLocalList('pv.followedDiscussions')
  savedDiscussionSlugs.value = readLocalList('pv.savedDiscussions')
  followedMemberSlugs.value = readLocalList('pv.followedMembers')
  bookmarkedContentSlugs.value = readLocalList('pv.bookmarkedContent')
  bookmarkedProductKeys.value = readLocalList('pv.bookmarkedProducts')
}

function applyUserActionPayload(payload: UserActionsResponse['data']): void {
  followedDiscussionSlugs.value = payload.followed_discussions ?? []
  savedDiscussionSlugs.value = payload.saved_discussions ?? []
  followedMemberSlugs.value = payload.followed_members ?? []
  bookmarkedContentSlugs.value = payload.bookmarked_content ?? []
  bookmarkedProductKeys.value = payload.bookmarked_products ?? []
}

async function loadUserActions(force = false): Promise<void> {
  if (!authStore.isAuthenticated) {
    userActionsLoaded.value = false
    return
  }

  if (userActionsLoaded.value && !force) {
    return
  }

  try {
    const response = await api.get<UserActionsResponse>('/api/v1/community/user-actions', {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    applyUserActionPayload(response.data.data)
    userActionsLoaded.value = true
  } catch {
    userActionsLoaded.value = true
  }
}

async function toggleCommunityAction(
  action: 'follow' | 'save' | 'bookmark',
  targetType: 'discussion' | 'content' | 'member' | 'product',
  targetKey: string,
  addedMessage: string,
  removedMessage: string,
): Promise<void> {
  if (!authStore.isAuthenticated) {
    const localKey = targetType === 'discussion' && action === 'follow'
      ? 'pv.followedDiscussions'
      : targetType === 'discussion' && action === 'save'
        ? 'pv.savedDiscussions'
        : targetType === 'member'
          ? 'pv.followedMembers'
          : targetType === 'product'
            ? 'pv.bookmarkedProducts'
            : 'pv.bookmarkedContent'
    const localList = targetType === 'discussion' && action === 'follow'
      ? followedDiscussionSlugs
      : targetType === 'discussion' && action === 'save'
        ? savedDiscussionSlugs
        : targetType === 'member'
          ? followedMemberSlugs
          : targetType === 'product'
            ? bookmarkedProductKeys
            : bookmarkedContentSlugs

    toggleLocalValue(localList, localKey, targetKey, addedMessage, removedMessage)
    return
  }

  try {
    const response = await api.post<UserActionsResponse & { active?: boolean }>('/api/v1/community/user-actions/toggle', {
      action,
      target_type: targetType,
      target_key: targetKey,
    }, {
      skipDeduplication: true,
    })
    applyUserActionPayload(response.data.data)
    actionStatusMessage.value = response.data.active ? addedMessage : removedMessage
  } catch {
    actionStatusMessage.value = 'Unable to update that action.'
  }
}

function syncFiltersFromRouteQuery(): void {
  const query = typeof route.query.q === 'string' ? route.query.q : ''

  if (page.value === 'discussions') discussionSearch.value = query
  if (page.value === 'labResults') labSearch.value = query
  if (page.value === 'vendorReviews') vendorSearch.value = query
  if (page.value === 'researchLibrary') researchSearch.value = query
  if (page.value === 'guides') guideSearch.value = query
  if (page.value === 'members') memberSearch.value = query
  if (page.value === 'messages') messageSearch.value = query
}

function cycleDiscussionSort(): void {
  discussionSort.value = discussionSort.value === 'latest' ? 'replies' : discussionSort.value === 'replies' ? 'views' : 'latest'
}

function cycleLabSort(): void {
  labSort.value = labSort.value === 'latest' ? 'purity' : labSort.value === 'purity' ? 'compound' : 'latest'
}

function cycleVendorSort(): void {
  vendorSort.value = vendorSort.value === 'rating' ? 'reviews' : vendorSort.value === 'reviews' ? 'name' : 'rating'
}

function toggleVendorReviewSort(): void {
  vendorReviewSort.value = vendorReviewSort.value === 'helpful' ? 'recent' : 'helpful'
}

function showVendorContactSection(): void {
  vendorDetailTab.value = 'about'
  window.setTimeout(() => {
    document.querySelector('.pv-tabs--line')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, 50)
}

function cycleResearchSort(): void {
  const options = contentFilterOptions.value.research.sorts.length > 0
    ? contentFilterOptions.value.research.sorts
    : [{ value: 'latest', label: 'Latest Added' }]
  const currentIndex = Math.max(0, options.findIndex(sort => sort.value === researchSort.value))
  researchSort.value = options[(currentIndex + 1) % options.length]?.value ?? 'latest'
  researchPage.value = 1
  void loadResearchContent()
}

function cycleMemberSort(): void {
  memberSort.value = memberSort.value === 'active' ? 'posts' : memberSort.value === 'posts' ? 'reputation' : 'active'
}

function startEditDiscussion(): void {
  if (!detailDiscussion.value) return
  editDiscussionTitle.value = detailDiscussion.value.title
  editDiscussionBody.value = detailDiscussion.value.body ?? ''
  editDiscussionTag.value = detailDiscussion.value.tag ?? ''
  editDiscussionCategory.value = detailDiscussion.value.categorySlug ?? ''
  isEditingDiscussion.value = true
  discussionEditError.value = ''
}

function cancelEditDiscussion(): void {
  isEditingDiscussion.value = false
  editDiscussionTitle.value = ''
  editDiscussionBody.value = ''
  discussionEditError.value = ''
}

async function saveEditDiscussion(): Promise<void> {
  if (!detailDiscussion.value?.slug) return
  const title = editDiscussionTitle.value.trim()
  const body = normalizeRichText(editDiscussionBody.value)
  if (!title || isRichTextEmpty(body)) {
    discussionEditError.value = 'Title and body are required.'
    return
  }
  discussionEditSaving.value = true
  discussionEditError.value = ''
  try {
    await api.patch(`/api/v1/community/discussions/${detailDiscussion.value.slug}`, {
      title, body,
      tag: editDiscussionTag.value || undefined,
      category_slug: editDiscussionCategory.value || undefined,
    })
    await loadDiscussionDetail()
    isEditingDiscussion.value = false
  } catch (err: any) {
    discussionEditError.value = err?.response?.data?.message ?? 'Failed to update discussion.'
  } finally {
    discussionEditSaving.value = false
  }
}

async function toggleDiscussionFollow(): Promise<void> {
  if (!detailDiscussion.value?.slug) return
  await toggleCommunityAction(
    'follow',
    'discussion',
    detailDiscussion.value.slug,
    'You are following this discussion.',
    'You are no longer following this discussion.',
  )
}

const showPostMenu = ref(false)
const activeReplyMenu = ref<number | null>(null)

function togglePostMenu() {
  showPostMenu.value = !showPostMenu.value
}

function toggleReplyMenu(index: number) {
  activeReplyMenu.value = activeReplyMenu.value === index ? null : index
}

// Close menus on click outside
if (typeof document !== 'undefined') {
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement
    if (!target.closest('.dots-dropdown') && !target.closest('.dots') && !target.closest('.op-dots')) {
      showPostMenu.value = false
      activeReplyMenu.value = null
    }
  })
}

const deletingDiscussion = ref(false)
async function deleteDiscussion(): Promise<void> {
  if (!detailDiscussion.value?.id) return
  if (!confirm('Delete this discussion? This cannot be undone.')) return
  deletingDiscussion.value = true
  try {
    await api.delete(`/api/v1/community/discussions/${detailDiscussion.value.id}`)
    apiDiscussions.value = apiDiscussions.value.filter(t => t.slug !== detailDiscussion.value?.slug)
    await router.push('/discussions')
  } catch {
    alert('Failed to delete discussion.')
  } finally {
    deletingDiscussion.value = false
  }
}

async function toggleDiscussionSave(): Promise<void> {
  if (!detailDiscussion.value?.slug) return
  await toggleCommunityAction(
    'save',
    'discussion',
    detailDiscussion.value.slug,
    'Discussion saved.',
    'Discussion removed from saved items.',
  )
}

function isFollowingMember(member: UiMemberProfile): boolean {
  return followedMemberSlugs.value.includes(member.slug)
}

async function toggleMemberFollow(member: UiMemberProfile): Promise<void> {
  await toggleCommunityAction(
    'follow',
    'member',
    member.slug,
    `Following ${member.name}.`,
    `Stopped following ${member.name}.`,
  )
}

function isBookmarkedContent(slug: string): boolean {
  return bookmarkedContentSlugs.value.includes(slug)
}

async function toggleContentBookmark(slug: string): Promise<void> {
  await toggleCommunityAction(
    'bookmark',
    'content',
    slug,
    'Content bookmarked.',
    'Bookmark removed.',
  )
}

function productBookmarkKey(product: VendorProduct): string {
  const vendor = detailVendor.value?.slug ?? String(product.vendorId ?? 'vendor')
  return `${vendor}:${product.slug || product.id || product.name}`
}

function isBookmarkedProduct(product: VendorProduct): boolean {
  return bookmarkedProductKeys.value.includes(productBookmarkKey(product))
}

async function toggleProductBookmark(product: VendorProduct): Promise<void> {
  await toggleCommunityAction(
    'bookmark',
    'product',
    productBookmarkKey(product),
    `${product.name} bookmarked.`,
    `${product.name} removed from bookmarks.`,
  )
}

async function shareCurrentPage(title = document.title): Promise<void> {
  const url = window.location.href

  try {
    if (navigator.share) {
      await navigator.share({ title, url })
    } else {
      await navigator.clipboard.writeText(url)
    }

    actionStatusMessage.value = 'Link copied.'
    labStatusMessage.value = 'Link copied.'
    contentStatusMessage.value = 'Link copied.'
  } catch {
    actionStatusMessage.value = 'Unable to share this page.'
  }
}

function printCurrentPage(): void {
  window.print()
}

function downloadLabReport(): void {
  if (!detailLabResult.value) return

  const result = detailLabResult.value
  const report = [
    result.name,
    `Vendor: ${result.vendor}`,
    `Batch: ${result.batch}`,
    `Lab: ${result.lab}`,
    `Tested: ${result.date}`,
    `Report ID: ${result.reportId}`,
    `Identity: ${result.identityResult}`,
    `Purity: ${result.purity}`,
    `Water Content: ${result.waterContent}`,
    `Peptide Content: ${result.peptideContent}`,
    '',
    result.notes,
  ].join('\n')

  const blob = new Blob([report], { type: 'text/plain;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${result.slug || 'lab-report'}.txt`
  link.click()
  URL.revokeObjectURL(url)
  labStatusMessage.value = 'Report downloaded.'
}

async function findCurrentBatchResults(): Promise<void> {
  if (!detailLabResult.value) return

  labSearch.value = detailLabResult.value.batch
  labVendorFilter.value = ''
  await router.push('/lab-results')
  await loadLabResults()
}

async function findCurrentVendorLabResults(): Promise<void> {
  if (!detailLabResult.value) return

  labSearch.value = ''
  labVendorFilter.value = detailLabResult.value.vendor
  await router.push('/lab-results')
  await loadLabResults()
}

function jumpToReplyComposer(): void {
  document.getElementById('reply-composer')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

function prepareReply(reply: UiReply | null, quote = false): void {
  if (!reply) {
    const text = plainTextFromRichText(detailDiscussion.value?.body ?? '') || detailParagraphs.value.join('\n') || detailDiscussion.value?.title || ''
    replyBody.value = quote && text ? quoteHtml(text) : ''
    jumpToReplyComposer()
    return
  }

  replyBody.value = quote ? quoteHtml(plainTextFromRichText(reply.text)) : `<p>@${escapeHtml(reply.authorUsername || reply.author)}&nbsp;</p>`
  jumpToReplyComposer()
}

async function voteOnDiscussion(value: 1 | -1): Promise<void> {
  if (!detailDiscussion.value?.slug || !ensureAuthenticated('vote')) return

  discussionVoteLoading.value = true
  try {
    const response = await api.post<DiscussionDetailResponse>(`/api/v1/community/discussions/${detailDiscussion.value.slug}/vote`, { value })
    const updated = mapDiscussion(response.data.data)
    apiDetailDiscussion.value = updated
    actionStatusMessage.value = updated.viewerVote === value
      ? (value === 1 ? 'Upvote recorded.' : 'Downvote recorded.')
      : 'Vote cleared.'
  } catch {
    actionStatusMessage.value = 'Unable to update vote.'
  } finally {
    discussionVoteLoading.value = false
  }
}

async function voteOnReply(reply: UiReply, value: 1 | -1): Promise<void> {
  if (!reply.id || !ensureAuthenticated('vote')) return

  replyVoteLoading.value = `${reply.id}:${value}`
  try {
    const response = await api.post<{ data: ApiReply }>(`/api/v1/community/discussion-replies/${reply.id}/vote`, { value })
    const updated = mapReply(response.data.data)
    apiReplies.value = apiReplies.value.map(item => item.id === updated.id ? updated : item)
    replyStatusMessage.value = updated.viewerVote === value
      ? (value === 1 ? 'Upvote recorded.' : 'Downvote recorded.')
      : 'Vote cleared.'
  } catch {
    replyStatusMessage.value = 'Unable to update vote.'
  } finally {
    replyVoteLoading.value = ''
  }
}

function openDiscussionReport(): void {
  if (!ensureAuthenticated('report content')) return
  reportTarget.value = { type: 'discussion' }
  reportReason.value = 'source-discussion'
  reportDetails.value = ''
  showReportModal.value = true
}

function openReplyReport(reply: UiReply): void {
  if (!ensureAuthenticated('report content')) return
  reportTarget.value = { type: 'reply', reply }
  reportReason.value = 'source-discussion'
  reportDetails.value = ''
  showReportModal.value = true
}

function closeReportModal(): void {
  showReportModal.value = false
  reportTarget.value = null
  reportDetails.value = ''
}

async function submitReport(): Promise<void> {
  if (!reportTarget.value) return

  reportSubmitting.value = true
  try {
    const payload = { reason: reportReason.value, details: reportDetails.value || undefined }
    if (reportTarget.value.type === 'discussion') {
      await api.post(`/api/v1/community/discussions/${currentDiscussionSlug.value}/report`, payload)
    } else if (reportTarget.value.reply.id) {
      await api.post(`/api/v1/community/discussion-replies/${reportTarget.value.reply.id}/report`, payload)
    }

    actionStatusMessage.value = 'Report sent to moderators.'
    replyStatusMessage.value = 'Report sent to moderators.'
    closeReportModal()
  } catch {
    actionStatusMessage.value = 'Unable to submit report.'
  } finally {
    reportSubmitting.value = false
  }
}

async function deleteReply(reply: UiReply): Promise<void> {
  if (!reply.id || !confirm('Delete this reply?')) return

  try {
    await api.delete(`/api/v1/community/discussion-replies/${reply.id}`)
    if (apiDetailDiscussion.value) {
      apiDetailDiscussion.value = { ...apiDetailDiscussion.value, replies: Math.max(0, (apiDetailDiscussion.value.replies ?? 1) - 1) }
    }
    await loadDiscussionDetail()
  } catch {
    replyStatusMessage.value = 'Unable to delete reply.'
  }
}

function handleReplyAttachment(event: Event): void {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null

  if (replyAttachmentPreviewUrl.value) {
    URL.revokeObjectURL(replyAttachmentPreviewUrl.value)
  }

  replyAttachmentFile.value = file
  replyAttachmentGifUrl.value = ''
  replyAttachmentPreviewUrl.value = file ? URL.createObjectURL(file) : ''
}

async function handleDiscussionFileUpload(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  discussionUploading.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    const res = await api.post<{ url?: string; path?: string }>('/api/v1/upload/image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const url = res.data.url || res.data.path
    if (url) {
      const isImage = file.type.startsWith('image/')
      const isVideo = file.type.startsWith('video/')
      const isPdf = file.type === 'application/pdf'
      if (isPdf) {
        appendNewDiscussionHtml(`<p><a href="${escapeHtml(url)}" target="_blank" rel="noreferrer">📄 ${escapeHtml(file.name)}</a></p>`)
      } else if (isVideo) {
        appendNewDiscussionHtml(`<p><a href="${escapeHtml(url)}" target="_blank" rel="noreferrer">${escapeHtml(file.name)}</a></p>`)
      } else if (isImage) {
        appendNewDiscussionHtml(`<figure><img src="${escapeHtml(url)}" alt="${escapeHtml(file.name)}"></figure>`)
      } else {
        appendNewDiscussionHtml(`<p><a href="${escapeHtml(url)}" target="_blank" rel="noreferrer">${escapeHtml(file.name)}</a></p>`)
    }
    }
  } catch {
    alert('Failed to upload file.')
  } finally {
    discussionUploading.value = false
    input.value = ''
  }
}

function onGifSelect(url: string): void {
  if (replyAttachmentPreviewUrl.value) {
    URL.revokeObjectURL(replyAttachmentPreviewUrl.value)
  }
  replyAttachmentFile.value = null
  replyAttachmentPreviewUrl.value = ''
  replyAttachmentGifUrl.value = url
}

function clearReplyAttachment(): void {
  if (replyAttachmentPreviewUrl.value) {
    URL.revokeObjectURL(replyAttachmentPreviewUrl.value)
  }

  replyAttachmentFile.value = null
  replyAttachmentPreviewUrl.value = ''
  replyAttachmentGifUrl.value = ''
}

function replyAttachmentName(): string {
  if (replyAttachmentFile.value) return replyAttachmentFile.value.name
  if (replyAttachmentGifUrl.value) return replyAttachmentGifUrl.value.split('/').filter(Boolean).pop()?.split('?')[0] || 'Linked GIF'
  return ''
}

function isVisualAttachment(reply: UiReply): boolean {
  const type = String(reply.attachmentMeta?.type ?? '')
  return Boolean(reply.attachmentUrl && ['image', 'gif'].includes(type))
}

function attachmentLabel(reply: UiReply): string {
  const type = String(reply.attachmentMeta?.type ?? 'file').toUpperCase()
  const size = Number(reply.attachmentMeta?.size ?? 0)
  return [type, size > 0 ? `${Math.round(size / 1024)} KB` : ''].filter(Boolean).join(' · ')
}

function jumpToVendorReviews(): void {
  document.querySelector('.pv-review-summary')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function formatMetadataKey(key: string): string {
  return key.replace(/_/g, ' ').replace(/\b\w/g, character => character.toUpperCase())
}

function formatMetadataValue(value: unknown): string {
  if (Array.isArray(value)) {
    return value.join(', ')
  }

  if (value && typeof value === 'object') {
    return JSON.stringify(value)
  }

  return String(value ?? '')
}

function normalizedVote(value?: number | null): -1 | 0 | 1 {
  if (value === 1 || value === -1) {
    return value
  }

  return 0
}

function colorForName(value: string): string {
  const seed = value.charCodeAt(0) || 0
  return avatarColors[seed % avatarColors.length] ?? 'purple'
}

function mapDiscussion(item: ApiDiscussion): UiDiscussion {
  const author = item.author?.name ?? ''
  const initial = item.author?.initial ?? author.charAt(0).toUpperCase()
  const category = item.category?.name ?? undefined
  const categorySlug = item.category?.slug ?? undefined
  const color = item.category?.color && avatarColors.includes(item.category.color)
    ? item.category.color
    : colorForName(author)
  const avatarUrl = item.author?.avatar ?? null

  return {
    id: item.id,
    title: item.title,
    excerpt: item.excerpt ?? item.body?.slice(0, 140) ?? '',
    author,
    authorUsername: item.author?.username ?? author,
    authorOnline: item.author?.is_online ?? false,
    authorId: item.author?.id,
    avatarUrl,
    time: item.time_ago ?? '',
    replies: item.replies ?? 0,
    views: formatCount(item.views),
    initial,
    color,
    tag: item.tag ?? 'Discussion',
    href: item.href ?? `/discussions/${item.slug}`,
    slug: item.slug,
    body: item.body ?? undefined,
    category,
    categorySlug,
    lastActivity: item.last_activity ?? undefined,
    isPinned: Boolean(item.is_pinned),
    isLocked: Boolean(item.is_locked),
    premiumOnly: Boolean(item.premium_only),
    voteScore: Number(item.vote_score ?? 0),
    viewerVote: normalizedVote(item.viewer_vote),
    latestReply: item.last_reply
      ? {
          author: item.last_reply.author,
          username: item.last_reply.username ?? item.last_reply.author,
          timeAgo: item.last_reply.time_ago,
          avatar: item.last_reply.avatar ?? null,
          initial: item.last_reply.initial,
        }
      : null,
  }
}

function mapReply(item: ApiReply): UiReply {
  const author = item.author?.name ?? ''
  const replyAvatarUrl = item.author?.avatar ?? null

  return {
    id: item.id,
    author,
    authorId: item.author?.id,
    authorUsername: item.author?.username ?? author,
    initial: item.author?.initial ?? author.charAt(0).toUpperCase(),
    color: colorForName(author),
    avatarUrl: replyAvatarUrl,
    badge: item.author?.badge ?? undefined,
    time: item.time_ago ?? '',
    text: item.body ?? '',
    file: item.attachment_name ?? undefined,
    attachmentUrl: item.attachment_url ?? null,
    attachmentMeta: item.attachment_meta ?? {},
    votes: Number(item.votes ?? 0),
    viewerVote: normalizedVote(item.viewer_vote),
    authorOnline: item.author?.is_online ?? false,
  }
}

async function loadDiscussions(): Promise<void> {
  try {
    const response = await api.get<DiscussionIndexResponse>('/api/v1/community/discussions', {
      cacheTTL: 0,
      params: {
        search: discussionSearch.value || undefined,
        category: activeCategory.value || undefined,
        page: discussionPage.value,
      },
    })
    apiDiscussions.value = response.data.data.map(mapDiscussion)
    apiCategories.value = response.data.meta?.categories ?? apiCategories.value
    discussionPagination.value = extractPagination(response.data.meta)
    discussionsLoaded.value = true
  } catch {
    apiDiscussions.value = []
    apiCategories.value = []
    discussionPagination.value = null
    discussionsLoaded.value = true
    discussionStatusMessage.value = 'Unable to load discussions from the API.'
  }
}

async function loadDiscussionDetail(): Promise<void> {
  if (page.value !== 'discussionDetail') {
    return
  }

  try {
    const response = await api.get<DiscussionDetailResponse>(`/api/v1/community/discussions/${currentDiscussionSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailDiscussion.value = mapDiscussion(response.data.data)
    apiReplies.value = (response.data.data.reply_items ?? []).map(mapReply)
    apiDiscussionParticipants.value = (response.data.data.participants ?? []).map(mapMember)
    apiSimilarDiscussions.value = (response.data.data.similar_discussions ?? []).map(mapDiscussion)
  } catch {
    apiDetailDiscussion.value = null
    apiReplies.value = []
    apiDiscussionParticipants.value = []
    apiSimilarDiscussions.value = []
  }
}

async function syncCommunityContent(): Promise<void> {
  syncFiltersFromRouteQuery()
  await Promise.all([
    loadPublicCommunitySettings(),
    loadUserActions(),
  ])

  switch (page.value) {
    case 'home':
      await Promise.all([
        loadDiscussions(),
        loadLabResults(),
        loadVendors(),
        loadAnnouncements(),
        loadMembers(),
      ])
      return
    case 'discussions':
      await Promise.all([loadDiscussions(), loadMembers()])
      return
    case 'discussionDetail':
      await Promise.all([loadDiscussionDetail(), loadDiscussions()])
      return
    case 'labResults':
      await loadLabResults()
      return
    case 'labReport':
      await loadLabResultDetail()
      return
    case 'vendorReviews':
      await loadVendors()
      return
    case 'vendorDetail':
    case 'reviewModal':
      await Promise.all([loadVendorDetail(), loadDiscussions()])
      return
    case 'vendorPortal':
      await Promise.all([loadVendorProfile(), loadVendors()])
      return
    case 'researchLibrary':
      await loadResearchContent()
      return
    case 'researchArticle':
      await Promise.all([loadContentDetails(), loadResearchContent()])
      return
    case 'guides':
      await Promise.all([loadGuidesContent(), loadFaqContent()])
      return
    case 'guideDetail':
      await Promise.all([loadContentDetails(), loadGuidesContent()])
      return
    case 'members':
      await loadMembers()
      return
    case 'memberDetail':
      await Promise.all([loadMemberDetail(), loadMembers()])
      return
    case 'messages':
      await Promise.all([loadMessages(), loadMembers()])
      return
    case 'announcements':
      await loadAnnouncements()
      return
    case 'announcementDetail':
      await loadAnnouncementDetail()
      return
    case 'notifications':
      await loadNotifications()
      return
    case 'notificationDetail':
      await Promise.all([loadNotificationDetail(), loadNotifications()])
      return
    case 'telegramUpdates':
      await Promise.all([loadAnnouncements(), loadNotifications()])
      return
    case 'settingsBlocked':
      await Promise.all([loadUserSettings(), loadMembers(), loadBlockedUsers()])
      return
    default:
      if (page.value.startsWith('settings')) {
        await loadUserSettings()
      }
  }
}

function ensureAuthenticated(action: string): boolean {
  if (authStore.isAuthenticated) {
    return true
  }

  const message = `Please log in to ${action}.`
  discussionFormError.value = message
  replyStatusMessage.value = message
  discussionStatusMessage.value = message
  return false
}

function openNewDiscussion(): void {
  discussionFormError.value = ''
  const savedDraft = loadNewDiscussionDraft()
  if (!newDiscussion.value.title && !newDiscussion.value.body && savedDraft) {
    newDiscussion.value = savedDraft
    discussionDraftStatus.value = 'Draft restored'
  }
  if (!newDiscussion.value.category_slug) {
    newDiscussion.value.category_slug = discussionCategories.value[0]?.slug ?? ''
  }
  showNewDiscussion.value = true
}

function clearNewDiscussion(): void {
  newDiscussion.value = { title: '', body: '', category_slug: '', tag: '' }
  newDiscussionKey.value++
}

function closeNewDiscussion(): void {
  showNewDiscussion.value = false
  discussionFormError.value = ''
}

function loadNewDiscussionDraft(): typeof newDiscussion.value | null {
  if (typeof window === 'undefined') return null
  try {
    const raw = window.localStorage.getItem(newDiscussionDraftKey)
    if (!raw) return null
    const parsed = JSON.parse(raw) as Partial<typeof newDiscussion.value>
    return {
      title: String(parsed.title || ''),
      body: String(parsed.body || ''),
      category_slug: String(parsed.category_slug || ''),
      tag: String(parsed.tag || ''),
    }
  } catch {
    return null
  }
}

function saveNewDiscussionDraft(): void {
  if (typeof window === 'undefined') return
  const draft = newDiscussion.value
  const hasDraft = Boolean(draft.title.trim() || plainTextFromRichText(draft.body).trim() || draft.tag)
  if (!hasDraft) {
    window.localStorage.removeItem(newDiscussionDraftKey)
    discussionDraftStatus.value = 'Draft auto-saved locally'
    return
  }

  window.localStorage.setItem(newDiscussionDraftKey, JSON.stringify(draft))
  discussionDraftStatus.value = 'Draft saved just now'
}

function clearNewDiscussionDraft(): void {
  if (typeof window === 'undefined') return
  window.localStorage.removeItem(newDiscussionDraftKey)
  discussionDraftStatus.value = 'Draft auto-saved locally'
}

watch(newDiscussion, () => {
  if (!showNewDiscussion.value) return
  saveNewDiscussionDraft()
}, { deep: true })

function appendNewDiscussionHtml(html: string): void {
  const current = newDiscussion.value.body.trim()
  newDiscussion.value.body = current ? `${current}${html}` : html
}

function insertDiscussionAttachment(type: ComposeAttachmentType): void {
  if (type === 'image' || type === 'video' || type === 'file') {
    discussionFileInput.value?.click()
    return
  }

  if (type === 'link') {
    const url = window.prompt('Link URL')
    const label = url ? window.prompt('Link label', 'Related link') || 'Related link' : ''
    if (url) appendNewDiscussionHtml(`<p><a href="${escapeHtml(safeExternalUrl(url))}">${escapeHtml(label)}</a></p>`)
    return
  }

  if (type === 'poll') {
    appendNewDiscussionHtml('<blockquote><p><strong>Poll:</strong> Add your question here</p><ul><li>Option 1</li><li>Option 2</li></ul></blockquote>')
  }
}

async function submitNewDiscussion(): Promise<void> {
  if (!ensureAuthenticated('post a discussion')) {
    return
  }

  const title = newDiscussion.value.title.trim()
  const body = normalizeRichText(newDiscussion.value.body)
  if (!title || isRichTextEmpty(body)) {
    discussionFormError.value = 'Title and discussion body are required.'
    return
  }

  creatingDiscussion.value = true
  discussionFormError.value = ''

  try {
    const response = await api.post<DiscussionDetailResponse>('/api/v1/community/discussions', {
      title,
      body,
      category_slug: newDiscussion.value.category_slug || undefined,
      tag: newDiscussion.value.tag || undefined,
    })
    const created = mapDiscussion(response.data.data)
    apiDiscussions.value = [created, ...apiDiscussions.value.filter(topic => topic.slug !== created.slug)]
    newDiscussion.value = { title: '', body: '', category_slug: '', tag: '' }
    clearNewDiscussionDraft()
    showNewDiscussion.value = false
    discussionStatusMessage.value = 'Discussion posted.'
    await router.push(created.href)
  } catch (error) {
    const apiError = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const errors = apiError.response?.data?.errors
    discussionFormError.value = errors ? Object.values(errors)[0]?.[0] ?? 'Unable to post discussion.' : apiError.response?.data?.message ?? 'Unable to post discussion.'
  } finally {
    creatingDiscussion.value = false
  }
}

async function submitReply(): Promise<void> {
  if (!ensureAuthenticated('reply')) {
    return
  }

  const body = normalizeRichText(replyBody.value)
  if (isRichTextEmpty(body)) {
    replyStatusMessage.value = 'Write a reply first.'
    return
  }

  submittingReply.value = true
  replyStatusMessage.value = ''

  try {
    const hasAttachment = Boolean(replyAttachmentFile.value || replyAttachmentGifUrl.value.trim())
    const payload = hasAttachment ? new FormData() : { body }

    if (payload instanceof FormData) {
      payload.append('body', body)
      if (replyAttachmentFile.value) {
        payload.append('attachment', replyAttachmentFile.value)
      } else if (replyAttachmentGifUrl.value.trim()) {
        payload.append('attachment_url', replyAttachmentGifUrl.value.trim())
        payload.append('attachment_name', replyAttachmentName())
        payload.append('attachment_type', 'gif')
      }
    }

    const response = await api.post<{ data: ApiReply }>(
      `/api/v1/community/discussions/${currentDiscussionSlug.value}/replies`,
      payload,
      payload instanceof FormData ? { headers: { 'Content-Type': 'multipart/form-data' } } : undefined,
    )
    apiReplies.value = [...apiReplies.value, mapReply(response.data.data)]
    if (detailDiscussion.value) {
      apiDetailDiscussion.value = {
        ...detailDiscussion.value,
        replies: detailDiscussion.value.replies + 1,
        lastActivity: 'just now',
      }
    }
    replyBody.value = ''
    clearReplyAttachment()
    replyStatusMessage.value = 'Reply posted.'
    await loadDiscussions()
  } catch (error) {
    const apiError = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const errors = apiError.response?.data?.errors
    replyStatusMessage.value = errors ? Object.values(errors)[0]?.[0] ?? 'Unable to post reply.' : apiError.response?.data?.message ?? 'Unable to post reply.'
  } finally {
    submittingReply.value = false
  }
}

function setDiscussionCategory(slug: string): void {
  activeCategory.value = activeCategory.value === slug ? '' : slug
  discussionPage.value = 1
  void loadDiscussions()
}

function applyDiscussionFilters(): void {
  discussionPage.value = 1
  void loadDiscussions()
}

function clearDiscussionFilters(): void {
  discussionSearch.value = ''
  activeCategory.value = ''
  discussionPage.value = 1
  void loadDiscussions()
}

function setDiscussionPage(pageNumber: number): void {
  discussionPage.value = pageNumber
  void loadDiscussions()
}

function goToDiscussion(topic: UiDiscussion): void {
  void router.push(topic.href)
}

async function loadMembershipPlans(): Promise<void> {
  try {
    const response = await api.get<{ data: MembershipPlan[] }>('/api/v1/membership/plans', { cacheTTL: 300000 })
    membershipPlans.value = response.data.data
  } catch {
    // silent
  }
}

async function loadMembershipStatus(): Promise<void> {
  if (!authStore.isAuthenticated) return
  try {
    const response = await api.get<{ tier: string; subscription: Record<string, unknown> | null }>('/api/v1/membership/status')
    membershipTier.value = response.data.tier
    membershipStatus.value = response.data.subscription
  } catch {
    // silent
  }
}

function toggleBillingInterval(): void {
  billingInterval.value = billingInterval.value === 'month' ? 'year' : 'month'
}

async function subscribeWith(provider: 'stripe' | 'paypal'): Promise<void> {
  if (!authStore.isAuthenticated) {
    void router.push('/login?redirect=/pricing')
    return
  }

  const plan = membershipPlans.value[0]
  if (!plan) {
    paymentStatusMessage.value = 'No membership plans available.'
    return
  }

  subscribing.value = true
  paymentStatusMessage.value = ''

  try {
    if (provider === 'stripe') {
      const response = await api.post<{ url: string }>('/api/v1/membership/stripe/create-checkout', {
        plan_id: plan.id,
        interval: billingInterval.value,
      })
      window.location.href = response.data.url
    } else {
      const response = await api.post<{ id: string; approval_url: string | null }>('/api/v1/membership/paypal/create-order', {
        plan_id: plan.id,
        interval: billingInterval.value,
      })
      if (response.data.approval_url) {
        window.location.href = response.data.approval_url
      } else {
        paymentStatusMessage.value = 'PayPal subscription created. Please check your PayPal account to approve.'
      }
    }
  } catch (error) {
    const apiError = error as { response?: { data?: { error?: string } } }
    paymentStatusMessage.value = apiError.response?.data?.error ?? `Failed to create ${provider} subscription.`
  } finally {
    subscribing.value = false
  }
}

async function cancelSubscription(): Promise<void> {
  if (!confirm('Are you sure you want to cancel your subscription?')) return
  try {
    await api.post('/api/v1/membership/cancel')
    await loadMembershipStatus()
    paymentStatusMessage.value = 'Subscription cancelled.'
  } catch (error) {
    const apiError = error as { response?: { data?: { error?: string } } }
    paymentStatusMessage.value = apiError.response?.data?.error ?? 'Failed to cancel subscription.'
  }
}

onMounted(() => {
  loadLocalActionState()
  void syncCommunityContent()
  void loadMembershipPlans()
  void loadMembershipStatus()

  if (authStore.isAuthenticated && authStore.user?.id) {
    import('@/composables/usePushNotifications').then(({ usePushNotifications }) => {
      const { init } = usePushNotifications()
      init(authStore.user!.id)
    })
  }
})

watch(() => route.fullPath, () => {
  void syncCommunityContent()
  if (page.value !== 'reviewModal') {
    clearVendorReviewPhotos()
  }
})

onUnmounted(() => {
  clearVendorReviewPhotos()
  if (vendorProductImagePreview.value && vendorProductImagePreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(vendorProductImagePreview.value)
  }
})

const labResults = computed(() => {
  const items = [...apiLabResults.value]

  if (labSort.value === 'purity') {
    return items.sort((a, b) => Number(b.purityPercent ?? 0) - Number(a.purityPercent ?? 0))
  }

  if (labSort.value === 'compound') {
    return items.sort((a, b) => a.name.localeCompare(b.name))
  }

  return items
})
const currentLabSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailLabResult = computed(() => {
  return apiDetailLabResult.value
    ?? labResults.value.find(result => result.slug === currentLabSlug.value || result.href.endsWith(`/${currentLabSlug.value}`))
    ?? null
})
const labSortLabel = computed(() => {
  if (labSort.value === 'purity') return 'Highest Purity'
  if (labSort.value === 'compound') return 'Compound A-Z'
  return 'Latest First'
})
const labHasActiveFilters = computed(() => Boolean(labSearch.value || labTypeFilter.value || labCompoundFilter.value || labVendorFilter.value || labLabFilter.value))
const detailLabRingStyle = computed(() => {
  const score = detailLabResult.value?.purityPercent ?? Number.parseFloat(detailLabResult.value?.purity ?? '0')
  return { '--score': `${Number.isFinite(score) ? score : 0}%` } as Record<string, string>
})

function formatPercent(value: number | null | undefined, fallback = ''): string {
  if (value === null || value === undefined) {
    return fallback
  }

  return `${Number(value).toFixed(2).replace(/\.?0+$/, '')}%`
}

function mapLabResult(item: ApiLabResult): UiLabResult {
  const name = item.compound_name

  return {
    id: item.id,
    name,
    slug: item.slug,
    type: item.compound_type ?? 'Unspecified',
    use: item.use_case ?? 'Unspecified',
    vendor: item.vendor_name,
    batch: item.batch_code,
    lab: item.lab_name,
    date: item.tested_date ?? item.tested_at ?? '',
    receivedDate: item.received_date ?? item.received_at ?? '',
    reportId: item.report_id ?? '',
    sampleType: item.sample_type ?? '',
    sampleCondition: item.sample_condition ?? '',
    purity: item.purity ?? formatPercent(item.purity_percent),
    purityPercent: item.purity_percent,
    waterContent: formatPercent(item.water_content_percent),
    peptideContent: formatPercent(item.peptide_content_percent, item.purity ?? ''),
    identityResult: item.identity_result ?? '',
    overallResult: item.overall_result ?? '',
    coaFilename: item.coa_filename,
    notes: item.notes ?? '',
    views: formatCount(item.views),
    comments: item.comments ?? 0,
    color: colorForName(name),
    href: item.href ?? `/lab-results/${item.slug}`,
    submittedBy: item.submitted_by?.name ?? undefined,
    status: item.status ?? undefined,
    isVerified: item.is_verified,
  }
}

async function loadLabResults(): Promise<void> {
  try {
    const response = await api.get<LabResultIndexResponse>('/api/v1/community/lab-results', {
      cacheTTL: 0,
      params: {
        search: labSearch.value || undefined,
        compound_type: labTypeFilter.value || undefined,
        compound: labCompoundFilter.value || undefined,
        vendor: labVendorFilter.value || undefined,
        lab: labLabFilter.value || undefined,
        page: labPage.value,
      },
    })
    apiLabResults.value = response.data.data.map(mapLabResult)
    labStats.value = response.data.meta?.stats ?? labStats.value
    labPagination.value = extractPagination(response.data.meta)
    labFilterOptions.value = {
      compound_types: response.data.meta?.filters?.compound_types ?? [],
      compounds: response.data.meta?.filters?.compounds ?? [],
      vendors: response.data.meta?.filters?.vendors ?? [],
      labs: response.data.meta?.filters?.labs ?? [],
    }
    labResultsLoaded.value = true
  } catch {
    apiLabResults.value = []
    labStats.value = { total: 0, batches: 0, avg_purity: 0, labs: 0 }
    labPagination.value = null
    labFilterOptions.value = { compound_types: [], compounds: [], vendors: [], labs: [] }
    labResultsLoaded.value = true
    labStatusMessage.value = 'Unable to load lab results from the API.'
  }
}

async function loadLabResultDetail(): Promise<void> {
  if (page.value !== 'labReport') {
    return
  }

  try {
    const response = await api.get<LabResultDetailResponse>(`/api/v1/community/lab-results/${currentLabSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailLabResult.value = mapLabResult(response.data.data)
  } catch {
    apiDetailLabResult.value = null
  }
}

function setLabTypeFilter(type: string): void {
  labTypeFilter.value = type
  labPage.value = 1
  void loadLabResults()
}

function applyLabFilters(): void {
  labPage.value = 1
  void loadLabResults()
}

function clearLabFilters(): void {
  labSearch.value = ''
  labTypeFilter.value = ''
  labCompoundFilter.value = ''
  labVendorFilter.value = ''
  labLabFilter.value = ''
  labPage.value = 1
  void loadLabResults()
}

function setLabPage(pageNumber: number): void {
  labPage.value = pageNumber
  void loadLabResults()
}

function openSubmitLabResult(): void {
  labFormError.value = ''
  showSubmitLabResult.value = true
}

function closeSubmitLabResult(): void {
  showSubmitLabResult.value = false
  labFormError.value = ''
}

async function submitLabResult(): Promise<void> {
  if (!authStore.isAuthenticated) {
    labFormError.value = 'Please log in to submit a lab result.'
    labStatusMessage.value = labFormError.value
    return
  }

  submittingLabResult.value = true
  labFormError.value = ''

  try {
    const payload = Object.fromEntries(
      Object.entries(newLabResult.value).filter(([, value]) => value !== '' && value !== undefined && value !== null),
    )
    const response = await api.post<LabResultDetailResponse>('/api/v1/community/lab-results', payload)
    const created = mapLabResult(response.data.data)
    labStatusMessage.value = `${created.name} was submitted for admin review.`
    showSubmitLabResult.value = false
    newLabResult.value = {
      compound_name: '',
      compound_type: 'Peptide',
      use_case: '',
      vendor_name: '',
      batch_code: '',
      lab_name: '',
      tested_at: '',
      purity_percent: undefined,
      coa_filename: '',
      notes: '',
    }
  } catch (error) {
    const apiError = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const errors = apiError.response?.data?.errors
    labFormError.value = errors ? Object.values(errors)[0]?.[0] ?? 'Unable to submit lab result.' : apiError.response?.data?.message ?? 'Unable to submit lab result.'
  } finally {
    submittingLabResult.value = false
  }
}

const currentVendorSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailVendor = computed(() => {
  return apiDetailVendor.value
    ?? vendors.value.find(vendor => vendor.slug === currentVendorSlug.value || vendor.href.endsWith(`/${currentVendorSlug.value}`))
    ?? null
})
const topVendors = computed(() => apiTopVendors.value.length > 0 ? apiTopVendors.value : vendors.value.slice(0, 5))
const vendorHasActiveFilters = computed(() => Boolean(vendorSearch.value || vendorStatusFilter.value || vendorRatingFilter.value || vendorTagFilter.value))
const vendorSortLabel = computed(() => {
  if (vendorSort.value === 'reviews') return 'Most Reviewed'
  if (vendorSort.value === 'name') return 'Name A-Z'
  return 'Highest Rated'
})
const vendorReviewProductOptions = computed(() => [...new Set(apiVendorReviews.value.map(review => review.productName).filter(Boolean) as string[])])
const vendorReviewSortLabel = computed(() => vendorReviewSort.value === 'helpful' ? 'Most Helpful' : 'Recent First')
const detailVendorContactLinks = computed(() => detailVendor.value ? vendorContactLinks(detailVendor.value) : [])
const vendorProductCategoryOptions = computed(() => [
  ...new Set((detailVendor.value?.products ?? []).map(product => product.category).filter(Boolean)),
])
function productEffectivePrice(product: VendorProduct): number | null {
  if (product.variants && product.variants.length > 0) {
    const prices = product.variants.map(v => v.price).filter(p => p !== null && p !== undefined) as number[]
    return prices.length > 0 ? Math.min(...prices) : null
  }
  return product.price
}

const filteredVendorProducts = computed(() => {
  const search = vendorProductSearch.value.trim().toLowerCase()
  const products = [...(detailVendor.value?.products ?? [])]
    .filter(product => product.status === 'published')
    .filter(product => !search || [product.name, product.category, product.strength, product.description, ...product.tags].some(value => value.toLowerCase().includes(search)))
    .filter(product => !vendorProductCategoryFilter.value || product.category === vendorProductCategoryFilter.value)
    .filter(product => !vendorProductAvailabilityFilter.value || product.availability === vendorProductAvailabilityFilter.value)

  return products.sort((a, b) => {
    if (vendorProductSort.value === 'price-low') return (productEffectivePrice(a) ?? Number.MAX_SAFE_INTEGER) - (productEffectivePrice(b) ?? Number.MAX_SAFE_INTEGER)
    if (vendorProductSort.value === 'price-high') return (productEffectivePrice(b) ?? -1) - (productEffectivePrice(a) ?? -1)
    if (vendorProductSort.value === 'name') return a.name.localeCompare(b.name)
    return a.sortOrder - b.sortOrder || b.rating - a.rating || b.reviews - a.reviews || a.name.localeCompare(b.name)
  })
})
const vendorProductManageList = computed(() => [...(apiMyVendor.value?.products ?? [])].sort((a, b) => a.sortOrder - b.sortOrder || a.name.localeCompare(b.name)))
const documentManageList = computed(() => [...(apiMyVendor.value?.documents ?? [])].sort((a, b) => (a.createdAt ?? '').localeCompare(b.createdAt ?? '')).reverse())

function mapVendorProduct(item: ApiVendorProduct): VendorProduct {
  return {
    id: item.id,
    vendorId: item.vendor_id,
    name: item.name,
    slug: item.slug,
    category: item.category ?? '',
    strength: item.strength ?? '',
    packageSize: item.package_size ?? '',
    purityLabel: item.purity_label ?? '',
    description: item.description ?? '',
    variants: item.variants ?? [],
    price: item.price ?? null,
    priceLabel: item.price_label ?? '',
    currencyCode: item.currency_code ?? 'USD',
    availability: item.availability ?? 'in_stock',
    availabilityLabel: item.availability_label ?? 'In stock',
    imageUrl: assetUrl(item.image_url),
    tags: item.tags ?? [],
    rating: item.average_rating ?? 0,
    ratingLabel: item.rating_label ?? (item.average_rating !== undefined ? Number(item.average_rating).toFixed(1) : '0.0'),
    reviews: item.review_count ?? 0,
    sortOrder: item.sort_order ?? 0,
    status: item.status ?? 'published',
    href: item.href ?? null,
  }
}

function variantPrice(variant: ProductVariant): string {
  if (variant.price === null || variant.price === undefined) return 'Contact for price'
  return '$' + Number(variant.price).toFixed(2)
}

function productCatalogPriceLabel(product: VendorProduct): string {
  const prices = product.variants
    .map(variant => variant.price)
    .filter((price): price is number => price !== null && price !== undefined)

  if (prices.length > 0) {
    return `From $${Math.min(...prices).toFixed(2)}`
  }

  return product.priceLabel || 'Contact'
}

function variantAvailability(variant: ProductVariant): string {
  if (variant.availability === 'out_of_stock') return 'Out of stock'
  if (variant.availability === 'limited') return 'Limited'
  return 'In stock'
}

function mapVendor(item: ApiVendor): UiVendor {
  const products = (item.products ?? []).map(mapVendorProduct)

  function mapVendorDocument(doc: ApiVendorDocument): VendorDocument {
    return {
      id: doc.id,
      vendorId: doc.vendor_id,
      title: doc.title,
      filePath: doc.file_path,
      fileType: doc.file_type,
      category: doc.category ?? null,
      description: doc.description ?? null,
      status: doc.status,
      url: doc.url,
      createdAt: doc.created_at,
      updatedAt: doc.updated_at,
    }
  }

  return {
    id: item.id,
    ownerUserId: item.owner_user_id ?? null,
    claimStatus: item.claim_status ?? 'unclaimed',
    isOwnedByViewer: Boolean(item.is_owned_by_viewer),
    name: item.name,
    slug: item.slug,
    logo: item.logo_initials ?? item.name.slice(0, 2).toUpperCase(),
    logoText: item.logo_text ?? item.name,
    imageUrl: assetUrl(item.image_url),
    class: item.logo_class ?? '',
    status: item.status_label ?? '',
    publishStatus: item.status ?? 'published',
    statusClass: item.status_class ?? '',
    country: item.country ?? '',
    rating: item.rating_label ?? (item.average_rating !== undefined ? String(item.average_rating) : ''),
    reviews: item.review_count ?? 0,
    since: item.member_since_label ?? '',
    buyAgain: item.would_buy_again_label ?? '',
    chips: item.tags ?? [],
    tone: item.tone ?? '',
    href: item.href ?? `/vendor-reviews/${item.slug}`,
    description: item.description ?? '',
    websiteUrl: item.website_url ?? null,
    contact: {
      email: item.contact?.email ?? '',
      telegram: item.contact?.telegram ?? '',
      signal: item.contact?.signal ?? '',
      discord: item.contact?.discord ?? '',
      supportUrl: item.contact?.support_url ?? '',
      responsePolicy: item.contact?.response_policy ?? '',
      publicNotes: item.contact?.public_notes ?? '',
    },
    lastActive: item.last_active_label ?? '',
    responseRate: item.response_rate_label ?? '',
    avgResponseTime: item.avg_response_time ?? '',
    productCount: item.product_count ?? products.filter(product => product.status === 'published').length,
    products,
    topProducts: (item.top_products ?? []).map(mapVendorProduct),
    documents: (item.documents ?? []).map(mapVendorDocument),
    ratingDistribution: item.rating_distribution ?? [],
  }
}

function vendorChipIcon(chip: string): string {
  const value = chip.toLowerCase()

  if (value.includes('pep') || value.includes('lab') || value.includes('research')) return 'flask'
  if (value.includes('sale') || value.includes('deal')) return 'tag'
  if (value.includes('support') || value.includes('contact')) return 'message'
  if (value.includes('vendor') || value.includes('member') || value.includes('nick')) return 'user'

  return 'tag'
}

function normalizeHandle(value?: string | null): string {
  return (value ?? '').trim().replace(/^@+/, '')
}

function vendorContactLinks(vendor: UiVendor): Array<{ label: string; value: string; href?: string; icon: string }> {
  const links: Array<{ label: string; value: string; href?: string; icon: string }> = []
  const contact = vendor.contact

  if (vendor.websiteUrl) {
    links.push({ label: 'Website', value: vendor.websiteUrl, href: vendor.websiteUrl, icon: 'share' })
  }

  if (contact.supportUrl) {
    links.push({ label: 'Support', value: contact.supportUrl, href: contact.supportUrl, icon: 'question' })
  }

  if (contact.email) {
    links.push({ label: 'Email', value: contact.email, href: `mailto:${contact.email}`, icon: 'mail' })
  }

  const telegram = normalizeHandle(contact.telegram)
  if (telegram) {
    links.push({ label: 'Telegram', value: `@${telegram}`, href: `https://t.me/${telegram}`, icon: 'send' })
  }

  if (contact.signal) {
    links.push({ label: 'Signal', value: contact.signal, icon: 'message' })
  }

  if (contact.discord) {
    links.push({ label: 'Discord', value: contact.discord, icon: 'discussions' })
  }

  return links
}

function hasVendorContact(vendor: UiVendor): boolean {
  return vendorContactLinks(vendor).length > 0 || Boolean(vendor.contact.responsePolicy || vendor.contact.publicNotes)
}

function hydrateVendorPortalForm(vendor: UiVendor | null = apiMyVendor.value): void {
  vendorPortalForm.value = {
    name: vendor?.name ?? '',
    slug: vendor?.slug ?? '',
    description: vendor?.description ?? '',
    website_url: vendor?.websiteUrl ?? '',
    image_url: vendor?.imageUrl ?? '',
    contact_email: vendor?.contact.email ?? '',
    contact_telegram: vendor?.contact.telegram ?? '',
    contact_signal: vendor?.contact.signal ?? '',
    contact_discord: vendor?.contact.discord ?? '',
    support_url: vendor?.contact.supportUrl ?? '',
    response_policy: vendor?.contact.responsePolicy ?? '',
    public_contact_notes: vendor?.contact.publicNotes ?? '',
    tags: vendor?.chips.join(', ') ?? '',
  }
}

function vendorPortalPayload() {
  const form = vendorPortalForm.value

  return {
    name: form.name.trim(),
    slug: form.slug.trim() || undefined,
    description: form.description.trim() || undefined,
    website_url: form.website_url.trim() || undefined,
    image_url: form.image_url.trim() || undefined,
    contact_email: form.contact_email.trim() || undefined,
    contact_telegram: form.contact_telegram.trim() || undefined,
    contact_signal: form.contact_signal.trim() || undefined,
    contact_discord: form.contact_discord.trim() || undefined,
    support_url: form.support_url.trim() || undefined,
    response_policy: form.response_policy.trim() || undefined,
    public_contact_notes: form.public_contact_notes.trim() || undefined,
    tags: form.tags.split(',').map(tag => tag.trim()).filter(Boolean),
  }
}

function vendorPortalError(error: unknown, fallback: string): string {
  const apiError = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
  const errors = apiError.response?.data?.errors
  return errors ? Object.values(errors)[0]?.[0] ?? fallback : apiError.response?.data?.message ?? fallback
}

function emptyVendorProductVariant(): ProductVariant {
  return { label: '', price: null, availability: 'in_stock' }
}

function addVendorProductVariant(): void {
  vendorProductForm.value.variants.push(emptyVendorProductVariant())
}

function removeVendorProductVariant(index: number): void {
  vendorProductForm.value.variants.splice(index, 1)
}

function resetVendorProductForm(): void {
  if (vendorProductImagePreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(vendorProductImagePreview.value)
  }

  editingVendorProductId.value = null
  vendorProductImageFile.value = null
  vendorProductImagePreview.value = ''
  vendorProductForm.value = {
    name: '',
    slug: '',
    category: '',
    strength: '',
    package_size: '',
    purity_label: '',
    description: '',
    price: '',
    variants: [],
    currency_code: 'USD',
    availability: 'in_stock',
    image_url: '',
    tags: '',
    sort_order: String((apiMyVendor.value?.products.length ?? 0) + 1),
    status: 'published',
  }
}

function editVendorProduct(product: VendorProduct): void {
  if (vendorProductImagePreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(vendorProductImagePreview.value)
  }

  editingVendorProductId.value = product.id ?? null
  vendorProductImageFile.value = null
  vendorProductImagePreview.value = product.imageUrl ?? ''
  vendorProductForm.value = {
    name: product.name,
    slug: product.slug,
    category: product.category,
    strength: product.strength,
    package_size: product.packageSize,
    purity_label: product.purityLabel,
    description: product.description,
    price: product.price !== null ? String(product.price) : '',
    variants: product.variants.map(v => ({ ...v })),
    currency_code: product.currencyCode,
    availability: product.availability,
    image_url: product.imageUrl ?? '',
    tags: product.tags.join(', '),
    sort_order: String(product.sortOrder),
    status: product.status,
  }
  vendorProductFormError.value = ''
}

function selectVendorProductImage(event: Event): void {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file) {
    return
  }

  if (vendorProductImagePreview.value && vendorProductImagePreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(vendorProductImagePreview.value)
  }

  vendorProductImageFile.value = file
  vendorProductImagePreview.value = URL.createObjectURL(file)
  input.value = ''
}

function vendorProductPayload(): FormData {
  const form = vendorProductForm.value
  const payload = new FormData()

  payload.append('name', form.name.trim())
  if (form.slug.trim()) payload.append('slug', form.slug.trim())
  if (form.category.trim()) payload.append('category', form.category.trim())
  if (form.strength.trim()) payload.append('strength', form.strength.trim())
  if (form.package_size.trim()) payload.append('package_size', form.package_size.trim())
  if (form.purity_label.trim()) payload.append('purity_label', form.purity_label.trim())
  if (form.description.trim()) payload.append('description', form.description.trim())
  if (form.price !== '') payload.append('price', form.price)
  if (form.variants.length > 0) {
    payload.append('variants', JSON.stringify(form.variants))
  }
  payload.append('currency_code', form.currency_code.trim().toUpperCase() || 'USD')
  payload.append('availability', form.availability)
  if (form.image_url.trim() && !vendorProductImageFile.value) payload.append('image_url', form.image_url.trim())
  form.tags.split(',').map(tag => tag.trim()).filter(Boolean).forEach(tag => payload.append('tags[]', tag))
  payload.append('sort_order', form.sort_order || '0')
  payload.append('status', form.status)
  if (vendorProductImageFile.value) payload.append('image', vendorProductImageFile.value)

  return payload
}

async function saveVendorProduct(): Promise<void> {
  if (!authStore.isAuthenticated) {
    vendorProductFormError.value = 'Please log in to manage products.'
    return
  }

  if (!vendorPortalAccessApproved.value || !apiMyVendor.value) {
    vendorProductFormError.value = 'Create an approved vendor profile before adding products.'
    return
  }

  savingVendorProduct.value = true
  vendorProductFormError.value = ''
  vendorProductStatusMessage.value = ''

  try {
    const requestConfig = {
      headers: { 'Content-Type': 'multipart/form-data' },
      skipDeduplication: true,
    }
    const payload = vendorProductPayload()
    const request = editingVendorProductId.value
      ? api.post<{ data: ApiVendorProduct }>(`/api/v1/community/vendor-profile/products/${editingVendorProductId.value}`, payload, requestConfig)
      : api.post<{ data: ApiVendorProduct }>('/api/v1/community/vendor-profile/products', payload, requestConfig)
    const response = await request
    const savedProduct = mapVendorProduct(response.data.data)
    if (apiMyVendor.value) {
      const products = apiMyVendor.value.products.filter(product => product.id !== savedProduct.id)
      apiMyVendor.value = {
        ...apiMyVendor.value,
        products: [...products, savedProduct],
        productCount: Math.max(apiMyVendor.value.productCount, products.length + 1),
      }
    }
    const currentDetailVendor = apiDetailVendor.value
    if (currentDetailVendor && currentDetailVendor.id === apiMyVendor.value?.id) {
      const products = currentDetailVendor.products.filter(product => product.id !== savedProduct.id)
      apiDetailVendor.value = {
        ...currentDetailVendor,
        products: [...products, savedProduct],
        productCount: Math.max(currentDetailVendor.productCount, products.length + 1),
      }
    }
    vendorProductStatusMessage.value = editingVendorProductId.value ? 'Product updated.' : 'Product added.'
    resetVendorProductForm()
    await loadVendorProfile()
    if (detailVendor.value?.isOwnedByViewer) {
      await loadVendorDetail()
    }
  } catch (error) {
    vendorProductFormError.value = vendorPortalError(error, 'Unable to save product.')
  } finally {
    savingVendorProduct.value = false
  }
}

async function deleteVendorProduct(product: VendorProduct): Promise<void> {
  if (!product.id || !window.confirm(`Remove ${product.name} from the public catalog?`)) {
    return
  }

  vendorProductStatusMessage.value = ''
  vendorProductFormError.value = ''

  try {
    await api.delete(`/api/v1/community/vendor-profile/products/${product.id}`)
    vendorProductStatusMessage.value = 'Product removed.'
    if (editingVendorProductId.value === product.id) {
      resetVendorProductForm()
    }
    await loadVendorProfile()
  } catch (error) {
    vendorProductFormError.value = vendorPortalError(error, 'Unable to remove product.')
  }
}

function mapVendorReview(item: ApiVendorReview): UiVendorReview {
  const author = item.author?.name ?? ''

  return {
    id: item.id,
    author,
    initial: item.author?.initial ?? author.charAt(0).toUpperCase(),
    color: colorForName(author),
    date: item.reviewed_date ?? '',
    rating: item.rating,
    title: item.title,
    text: item.body,
    productName: item.product_name,
    chips: item.tags ?? [],
    photoUrls: (item.photo_urls ?? []).map(assetUrl).filter(Boolean),
    helpful: item.helpful_count ?? 0,
    verifiedBuyer: Boolean(item.is_verified_buyer),
    vendorResponse: item.vendor_response ?? null,
    respondedAt: item.responded_at ?? null,
  }
}

async function loadVendors(): Promise<void> {
  try {
    const response = await api.get<VendorIndexResponse>('/api/v1/community/vendors', {
      cacheTTL: 0,
      params: {
        search: vendorSearch.value || undefined,
        status: vendorStatusFilter.value || undefined,
        rating_min: vendorRatingFilter.value || undefined,
        tag: vendorTagFilter.value || undefined,
        page: vendorPage.value,
      },
    })
    apiVendors.value = response.data.data.map(mapVendor)
    apiTopVendors.value = (response.data.meta?.top_vendors ?? []).map(mapVendor)
    vendorStats.value = response.data.meta?.stats ?? vendorStats.value
    vendorPagination.value = extractPagination(response.data.meta)
    vendorFilterOptions.value = {
      statuses: response.data.meta?.filters?.statuses ?? [],
      ratings: response.data.meta?.filters?.ratings ?? [],
      tags: response.data.meta?.filters?.tags ?? [],
    }
    vendorsLoaded.value = true
  } catch {
    apiVendors.value = []
    apiTopVendors.value = []
    vendorPagination.value = null
    vendorsLoaded.value = true
    vendorStats.value = {
      vendors_reviewed: 0,
      total_reviews: 0,
      average_rating: 0,
      would_buy_again: 0,
    }
    vendorStatusMessage.value = 'Unable to load vendors from the API.'
  }
}

async function loadVendorDetail(): Promise<void> {
  if (page.value !== 'vendorDetail' && page.value !== 'reviewModal') {
    return
  }

  try {
    const response = await api.get<VendorDetailResponse>(`/api/v1/community/vendors/${currentVendorSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailVendor.value = mapVendor(response.data.data)
    apiVendorReviews.value = (response.data.data.review_items ?? []).map(mapVendorReview)
  } catch {
    apiDetailVendor.value = null
    apiVendorReviews.value = []
  }
}

async function loadVendorProfile(): Promise<void> {
  if (page.value !== 'vendorPortal') {
    return
  }

  vendorPortalLoaded.value = false

  try {
    const response = await api.get<VendorProfileResponse>('/api/v1/community/vendor-profile', {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiMyVendor.value = response.data.data ? mapVendor(response.data.data) : null
    vendorPortalAccessApproved.value = Boolean(response.data.is_approved_vendor)
    hydrateVendorPortalForm(apiMyVendor.value)
    if (!editingVendorProductId.value) {
      resetVendorProductForm()
    }
    vendorPortalLoaded.value = true
  } catch {
    apiMyVendor.value = null
    vendorPortalAccessApproved.value = false
    vendorPortalLoaded.value = true
    vendorPortalStatusMessage.value = 'Unable to load your vendor profile.'
  }
}

async function saveVendorProfile(): Promise<void> {
  if (!authStore.isAuthenticated) {
    vendorPortalFormError.value = 'Please log in to manage a vendor profile.'
    return
  }

  if (!vendorPortalAccessApproved.value) {
    vendorPortalFormError.value = 'An admin must approve this account as a vendor before you can manage a vendor profile.'
    return
  }

  savingVendorProfile.value = true
  vendorPortalFormError.value = ''
  vendorPortalStatusMessage.value = ''

  try {
    const creatingProfile = !apiMyVendor.value
    const request = apiMyVendor.value
      ? api.patch<{ data: ApiVendor }>('/api/v1/community/vendor-profile', vendorPortalPayload())
      : api.post<{ data: ApiVendor }>('/api/v1/community/vendor-profile', vendorPortalPayload())
    const response = await request
    apiMyVendor.value = mapVendor(response.data.data)
    vendorPortalAccessApproved.value = true
    hydrateVendorPortalForm(apiMyVendor.value)
    vendorPortalStatusMessage.value = creatingProfile ? 'Vendor profile published.' : 'Vendor profile saved.'
    await loadVendors()
    if (detailVendor.value?.isOwnedByViewer) {
      await loadVendorDetail()
    }
  } catch (error) {
    vendorPortalFormError.value = vendorPortalError(error, 'Unable to save vendor profile.')
  } finally {
    savingVendorProfile.value = false
  }
}

async function requestVendorAccess(): Promise<void> {
  vendorAccessRequested.value = true
  try {
    await api.post('/api/v1/vendor-access/request')
    vendorPortalStatusMessage.value = 'Vendor access request submitted.'
  } catch {
    vendorAccessRequested.value = false
    vendorPortalStatusMessage.value = 'Failed to submit vendor access request.'
  }
}

async function approveVendorAccess(): Promise<void> {
  const userId = authStore.user?.id
  if (!userId) return
  try {
    await api.patch(`/admin/users/${userId}/vendor-access`, { approved: true })
    vendorPortalAccessApproved.value = true
  } catch {
    alert('Failed to approve vendor access.')
  }
}

async function uploadVendorImage(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file) {
    return
  }

  if (!authStore.isAuthenticated) {
    vendorPortalFormError.value = 'Please log in to upload a vendor image.'
    input.value = ''
    return
  }

  if (!vendorPortalAccessApproved.value) {
    vendorPortalFormError.value = 'An admin must approve this account as a vendor before you can upload a vendor image.'
    input.value = ''
    return
  }

  uploadingVendorImage.value = true
  vendorPortalFormError.value = ''
  vendorPortalStatusMessage.value = ''

  try {
    const form = new FormData()
    form.append('image', file)

    const response = await api.post<{ image_url?: string; data?: ApiVendor | null }>('/api/v1/community/vendor-profile/image', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      skipDeduplication: true,
    })

    if (response.data.data) {
      apiMyVendor.value = mapVendor(response.data.data)
      hydrateVendorPortalForm(apiMyVendor.value)
    } else if (response.data.image_url) {
      vendorPortalForm.value.image_url = assetUrl(response.data.image_url)
    }

    vendorPortalStatusMessage.value = apiMyVendor.value
      ? 'Vendor image updated.'
      : 'Vendor image uploaded. Save the profile to publish it.'
  } catch (error) {
    vendorPortalFormError.value = vendorPortalError(error, 'Unable to upload vendor image.')
  } finally {
    uploadingVendorImage.value = false
    input.value = ''
  }
}

function selectVendorDocumentFile(event: Event): void {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) {
    vendorDocumentFilePreview.value = file.name
  }
  input.value = ''
}

function resetVendorDocumentForm(): void {
  vendorDocumentForm.value = { title: '', category: '', description: '' }
  vendorDocumentFilePreview.value = ''
  vendorDocumentFormError.value = ''
  vendorDocumentStatusMessage.value = ''
}

async function saveVendorDocument(): Promise<void> {
  if (!authStore.isAuthenticated) {
    vendorDocumentFormError.value = 'Please log in to upload documents.'
    return
  }
  if (!vendorPortalAccessApproved.value || !apiMyVendor.value) {
    vendorDocumentFormError.value = 'Create an approved vendor profile before uploading documents.'
    return
  }
  const fileInput = vendorDocumentFileInput.value
  if (!fileInput?.files?.length) {
    vendorDocumentFormError.value = 'Please select a file to upload.'
    return
  }
  savingVendorDocument.value = true
  vendorDocumentFormError.value = ''
  vendorDocumentStatusMessage.value = ''
  try {
    const form = new FormData()
    form.append('title', vendorDocumentForm.value.title)
    form.append('category', vendorDocumentForm.value.category)
    form.append('description', vendorDocumentForm.value.description)
    form.append('file', fileInput.files[0])
    const response = await api.post<{ data: ApiVendorDocument }>('/api/v1/community/vendor-profile/documents', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      skipDeduplication: true,
    })
    const savedDoc: VendorDocument = {
      id: response.data.data.id,
      vendorId: response.data.data.vendor_id,
      title: response.data.data.title,
      filePath: response.data.data.file_path,
      fileType: response.data.data.file_type,
      category: response.data.data.category ?? null,
      description: response.data.data.description ?? null,
      status: response.data.data.status,
      url: response.data.data.url,
      createdAt: response.data.data.created_at,
      updatedAt: response.data.data.updated_at,
    }
    if (apiMyVendor.value) {
      apiMyVendor.value = {
        ...apiMyVendor.value,
        documents: [...apiMyVendor.value.documents, savedDoc],
      }
    }
    vendorDocumentStatusMessage.value = 'Document uploaded.'
    resetVendorDocumentForm()
    await loadVendorProfile()
    if (detailVendor.value?.isOwnedByViewer) {
      await loadVendorDetail()
    }
  } catch (error) {
    vendorDocumentFormError.value = vendorPortalError(error, 'Unable to upload document.')
  } finally {
    savingVendorDocument.value = false
  }
}

async function deleteVendorDocument(doc: VendorDocument): Promise<void> {
  if (!doc.id || !window.confirm(`Remove "${doc.title}" from your documents?`)) {
    return
  }
  vendorDocumentStatusMessage.value = ''
  vendorDocumentFormError.value = ''
  try {
    await api.delete(`/api/v1/community/vendor-profile/documents/${doc.id}`)
    vendorDocumentStatusMessage.value = 'Document removed.'
    await loadVendorProfile()
  } catch (error) {
    vendorDocumentFormError.value = vendorPortalError(error, 'Unable to remove document.')
  }
}

function selectVendorReviewPhotos(event: Event): void {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? []).slice(0, 5)

  vendorReviewPhotoPreviews.value.forEach(url => URL.revokeObjectURL(url))
  vendorReviewPhotos.value = files
  vendorReviewPhotoPreviews.value = files.map(file => URL.createObjectURL(file))
  input.value = ''
}

function removeVendorReviewPhoto(index: number): void {
  const preview = vendorReviewPhotoPreviews.value[index]

  if (preview) {
    URL.revokeObjectURL(preview)
  }

  vendorReviewPhotos.value = vendorReviewPhotos.value.filter((_, itemIndex) => itemIndex !== index)
  vendorReviewPhotoPreviews.value = vendorReviewPhotoPreviews.value.filter((_, itemIndex) => itemIndex !== index)
}

function clearVendorReviewPhotos(): void {
  vendorReviewPhotoPreviews.value.forEach(url => URL.revokeObjectURL(url))
  vendorReviewPhotos.value = []
  vendorReviewPhotoPreviews.value = []
}

function cancelVendorResponse(): void {
  respondingReviewId.value = undefined
  reviewResponseText.value = ''
  submittingReviewResponse.value = false
}

async function respondToReview(review: UiVendorReview): Promise<void> {
  if (!authStore.isAuthenticated) {
    vendorReviewStatusMessage.value = 'Please log in to respond to reviews.'
    return
  }

  if (!review.id) return

  const text = reviewResponseText.value.trim()
  if (!text) {
    vendorReviewStatusMessage.value = 'Enter a response before submitting.'
    return
  }

  submittingReviewResponse.value = true
  vendorReviewStatusMessage.value = ''

  try {
    const response = await api.post<{ data: ApiVendorReview }>(`/api/v1/community/vendor-reviews/${review.id}/respond`, {
      vendor_response: text,
    })
    const updated = mapVendorReview(response.data.data)
    apiVendorReviews.value = apiVendorReviews.value.map(item => item.id === updated.id ? updated : item)
    vendorReviewStatusMessage.value = 'Response posted.'
    cancelVendorResponse()
  } catch {
    vendorReviewStatusMessage.value = 'Unable to post response.'
  } finally {
    submittingReviewResponse.value = false
  }
}

function setVendorStatusFilter(status: string): void {
  vendorStatusFilter.value = status
  vendorPage.value = 1
  void loadVendors()
}

function applyVendorFilters(): void {
  vendorPage.value = 1
  void loadVendors()
}

function clearVendorFilters(): void {
  vendorSearch.value = ''
  vendorStatusFilter.value = ''
  vendorRatingFilter.value = ''
  vendorTagFilter.value = ''
  vendorPage.value = 1
  void loadVendors()
}

function setVendorPage(pageNumber: number): void {
  vendorPage.value = pageNumber
  void loadVendors()
}

async function submitVendorReview(): Promise<void> {
  if (!authStore.isAuthenticated) {
    vendorReviewFormError.value = 'Please log in to submit a vendor review.'
    return
  }

  if (!detailVendor.value) {
    vendorReviewFormError.value = 'Vendor not found.'
    return
  }

  submittingVendorReview.value = true
  vendorReviewFormError.value = ''

  if (newVendorReview.value.rating === 0 || !newVendorReview.value.title.trim() || !newVendorReview.value.body.trim()) {
    vendorReviewFormError.value = 'Please fill in all required fields: rating, title, and your review.'
    submittingVendorReview.value = false
    return
  }

  if (vendorReviewPhotos.value.length === 0) {
    vendorReviewFormError.value = 'You must upload at least one photo showing the product with your username and the date.'
    submittingVendorReview.value = false
    return
  }

  try {
    const form = new FormData()
    form.append('rating', String(newVendorReview.value.rating))
    form.append('title', newVendorReview.value.title)
    form.append('body', newVendorReview.value.body)
    form.append('would_buy_again', newVendorReview.value.would_buy_again ? '1' : '0')

    vendorReviewPhotos.value.forEach(file => form.append('photos[]', file))

    await api.post(`/api/v1/community/vendors/${detailVendor.value.slug}/reviews`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      skipDeduplication: true,
    })

    vendorReviewStatusMessage.value = 'Review posted.'
    newVendorReview.value = {
      rating: 5,
      title: '',
      body: '',
      product_name: '',
      tags: '',
      would_buy_again: true,
    }
    clearVendorReviewPhotos()
    await router.push(detailVendor.value.href)
  } catch (error) {
    const apiError = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const errors = apiError.response?.data?.errors
    vendorReviewFormError.value = errors ? Object.values(errors)[0]?.[0] ?? 'Unable to submit review.' : apiError.response?.data?.message ?? 'Unable to submit review.'
  } finally {
    submittingVendorReview.value = false
  }
}

async function markReviewHelpful(review: UiVendorReview): Promise<void> {
  if (!authStore.isAuthenticated) {
    vendorReviewStatusMessage.value = 'Please log in to mark reviews helpful.'
    return
  }

  if (!review.id) {
    return
  }

  markingReviewHelpful.value = review.id
  vendorReviewStatusMessage.value = ''

  try {
    const response = await api.post<{ data: ApiVendorReview }>(`/api/v1/community/vendor-reviews/${review.id}/helpful`)
    const updated = mapVendorReview(response.data.data)
    apiVendorReviews.value = apiVendorReviews.value.map(item => item.id === updated.id ? updated : item)
    vendorReviewStatusMessage.value = 'Thanks for the feedback.'
  } catch {
    vendorReviewStatusMessage.value = 'Unable to mark review helpful.'
  } finally {
    markingReviewHelpful.value = undefined
  }
}

function mapAnnouncement(item: ApiAnnouncement): UiAnnouncement {
  const author = item.author?.name ?? ''

  return {
    id: item.id,
    title: item.title,
    slug: item.slug,
    category: item.category,
    icon: item.icon ?? 'megaphone',
    tone: item.tone ?? 'purple',
    text: item.excerpt ?? item.body?.slice(0, 180) ?? '',
    body: item.body ?? item.excerpt ?? '',
    date: item.published_label ?? '',
    time: item.time_ago ?? '',
    comments: item.comments ?? 0,
    views: formatCount(item.views),
    pinned: item.is_pinned ?? false,
    href: item.href ?? `/announcements/${item.slug}`,
    author,
    authorInitial: item.author?.initial ?? author.charAt(0).toUpperCase(),
  }
}

async function loadAnnouncements(): Promise<void> {
  try {
    const response = await api.get<AnnouncementIndexResponse>('/api/v1/community/announcements', {
      cacheTTL: 0,
      params: {
        pinned: announcementFilter.value === 'pinned' ? true : undefined,
        category: announcementFilter.value !== 'all' && announcementFilter.value !== 'pinned' ? announcementFilter.value : undefined,
      },
    })
    apiAnnouncements.value = response.data.data.map(mapAnnouncement)
    announcementStats.value = response.data.meta?.stats ?? announcementStats.value
    announcementCategories.value = response.data.meta?.categories ?? announcementCategories.value
    announcementsLoaded.value = true
    announcementStatusMessage.value = ''
  } catch {
    apiAnnouncements.value = []
    announcementCategories.value = []
    announcementStats.value = {
      total: 0,
      pinned: 0,
      this_month: 0,
      total_views: 0,
      total_comments: 0,
    }
    announcementsLoaded.value = true
    announcementStatusMessage.value = 'Unable to load announcements from the API.'
  }
}

async function loadAnnouncementDetail(): Promise<void> {
  if (page.value !== 'announcementDetail') {
    return
  }

  try {
    const response = await api.get<AnnouncementDetailResponse>(`/api/v1/community/announcements/${currentAnnouncementSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailAnnouncement.value = mapAnnouncement(response.data.data)
  } catch {
    apiDetailAnnouncement.value = null
  }
}

function setAnnouncementFilter(filter: string): void {
  announcementFilter.value = filter
  void loadAnnouncements()
}

function mapContent(item: ApiContentItem): UiContentItem {
  const author = item.author?.name ?? ''

  return {
    id: item.id,
    type: item.type,
    title: item.title,
    slug: item.slug,
    tag: item.tag ?? item.category,
    category: item.category,
    excerpt: item.excerpt ?? '',
    body: item.body ?? '',
    date: item.published_label ?? '',
    time: item.time_ago ?? '',
    views: formatCount(item.views),
    downloads: formatCount(item.downloads),
    comments: item.comments ?? 0,
    readMinutes: item.read_minutes ?? 5,
    timeLabel: item.read_label ?? `${item.read_minutes ?? 5} min`,
    href: item.href ?? (item.type === 'guide' ? `/guides/${item.slug}` : `/research-library/${item.slug}`),
    imageIndex: item.image_index ?? 0,
    imageUrl: assetUrl(item.image_url),
    author,
    authorInitial: item.author?.initial ?? author.slice(0, 2).toUpperCase(),
    authorBadge: item.author?.badge ?? null,
    metadata: item.metadata ?? {},
  }
}

function mapMemberActivity(activity: ApiMemberActivity): UiMemberActivity {
  return {
    icon: activity.icon ?? 'message',
    tone: activity.tone ?? 'purple',
    title: activity.title,
    subtitle: activity.subtitle,
    category: activity.category,
    time: activity.time_ago ?? '',
  }
}

function mapMember(item: ApiMemberProfile): UiMemberProfile {
  const activities = (item.activities ?? []).map(mapMemberActivity)

  return {
    id: item.id,
    name: item.display_name,
    username: item.username,
    slug: item.slug,
    initial: item.initial,
    color: item.color ?? colorForName(item.display_name),
    avatarUrl: item.avatar ?? null,
    role: item.role_label ?? '',
    group: item.group_label ?? '',
    badge: item.badge_label ?? null,
    bio: item.bio ?? '',
    location: item.location ?? '',
    websiteUrl: item.website_url ?? null,
    isOnline: item.is_online ?? false,
    isVerified: item.is_verified ?? false,
    isModerator: item.is_moderator ?? false,
    joined: item.joined_label ?? '',
    lastActive: item.last_active_label ?? '',
    interests: item.interests ?? [],
    stats: item.stats ?? {},
    badges: item.badges ?? [],
    href: item.href ?? `/members/${item.slug}`,
    activities,
    tabData: {
      overview: item.tab_data?.overview ?? [],
      activity: item.tab_data?.activity?.map(mapMemberActivity) ?? activities,
      posts: item.tab_data?.posts ?? [],
      reviews: item.tab_data?.reviews ?? [],
      guides: item.tab_data?.guides ?? [],
      badges: item.tab_data?.badges ?? item.badges ?? [],
    },
  }
}

function mapMessageThread(item: ApiMessageThread): UiMessageThread {
  const participant = mapMember(item.participant)

  return {
    id: item.id,
    participant,
    preview: item.preview ?? '',
    time: item.last_message_label ?? '',
    unread: item.unread_count ?? 0,
    messages: (item.messages ?? []).map(message => mapMessage(message, participant)),
  }
}

function mapMessage(item: ApiMessage, participant?: UiMemberProfile): UiMessage {
  const sender = item.sender ? mapMember(item.sender) : (item.side === 'in' ? participant : null)
  const attachmentMeta = item.attachment_meta ?? {}
  const attachmentType = String(attachmentMeta.type ?? '')
  const attachmentSize = String(attachmentMeta.size ?? '')

  return {
    id: item.id,
    side: item.side,
    text: item.body,
    time: item.sent_label ?? item.time_ago ?? '',
    attachmentName: item.attachment_name,
    attachmentMeta,
    attachmentLabel: [attachmentType, attachmentSize].filter(Boolean).join(' · '),
    avatarInitial: sender?.initial ?? '',
    avatarColor: sender?.color ?? '',
  }
}

async function loadResearchContent(): Promise<void> {
  try {
    const response = await api.get<ContentIndexResponse>('/api/v1/community/research-library', {
      cacheTTL: 0,
      params: {
        search: researchSearch.value || undefined,
        category: activeResearchCategory.value || undefined,
        tag: researchTagFilter.value || undefined,
        compound: researchCompoundFilter.value || undefined,
        sort: researchSort.value || undefined,
        published_from: researchPublishedFrom.value || undefined,
        published_to: researchPublishedTo.value || undefined,
        page: researchPage.value,
      },
    })
    apiResearchArticles.value = response.data.data.map(mapContent)
    researchPagination.value = extractPagination(response.data.meta)
    contentCategories.value.research = response.data.meta?.categories ?? []
    contentTopics.value.research = response.data.meta?.topics ?? []
    contentFilterOptions.value.research = {
      categories: response.data.meta?.filters?.categories ?? response.data.meta?.categories ?? [],
      tags: response.data.meta?.filters?.tags ?? [],
      compounds: response.data.meta?.filters?.compounds ?? [],
      sorts: response.data.meta?.filters?.sorts ?? [{ value: 'latest', label: 'Latest Added' }],
      date_bounds: response.data.meta?.filters?.date_bounds ?? {},
    }
    contentLoaded.value = true
  } catch {
    apiResearchArticles.value = []
    researchPagination.value = null
    contentFilterOptions.value.research = emptyContentFilterOptions()
    contentLoaded.value = true
    contentStatusMessage.value = 'Unable to load research content from the API.'
  }
}

function applyResearchFilters(): void {
  researchPage.value = 1
  void loadResearchContent()
}

function clearResearchFilters(): void {
  researchSearch.value = ''
  activeResearchCategory.value = ''
  researchTagFilter.value = ''
  researchCompoundFilter.value = ''
  researchSort.value = 'latest'
  researchPublishedFrom.value = ''
  researchPublishedTo.value = ''
  researchPage.value = 1
  void loadResearchContent()
}

function setResearchPage(pageNumber: number): void {
  researchPage.value = pageNumber
  void loadResearchContent()
}

function setGuidePage(pageNumber: number): void {
  guidePage.value = pageNumber
  void loadGuidesContent()
}

function setMemberPage(pageNumber: number): void {
  memberPage.value = pageNumber
  void loadMembers()
}

async function loadGuidesContent(): Promise<void> {
  try {
    const response = await api.get<ContentIndexResponse>('/api/v1/community/guides', {
      cacheTTL: 0,
      params: {
        search: guideSearch.value || undefined,
        category: activeGuideCategory.value || undefined,
        page: guidePage.value,
      },
    })
    apiGuides.value = response.data.data.map(mapContent)
    guidePagination.value = extractPagination(response.data.meta)
    contentCategories.value.guide = response.data.meta?.categories ?? []
    contentTopics.value.guide = response.data.meta?.topics ?? []
    contentLoaded.value = true
  } catch {
    apiGuides.value = []
    guidePagination.value = null
    contentLoaded.value = true
    contentStatusMessage.value = 'Unable to load guides from the API.'
  }
}

async function loadFaqContent(): Promise<void> {
  try {
    const response = await api.get<ContentIndexResponse>('/api/v1/community/faqs', {
      cacheTTL: 0,
    })
    apiFaqs.value = response.data.data.map(mapContent)
    contentCategories.value.faq = response.data.meta?.categories ?? []
    contentTopics.value.faq = response.data.meta?.topics ?? []
  } catch {
    apiFaqs.value = []
  }
}

async function loadContentDetails(): Promise<void> {
  if (page.value === 'researchArticle') {
    try {
      const response = await api.get<ContentDetailResponse>(`/api/v1/community/research-library/${currentContentSlug.value}`, {
        cacheTTL: 0,
        skipDeduplication: true,
      })
      apiDetailResearchArticle.value = mapContent(response.data.data)
    } catch {
      apiDetailResearchArticle.value = null
    }
  }

  if (page.value === 'guideDetail') {
    try {
      const response = await api.get<ContentDetailResponse>(`/api/v1/community/guides/${currentContentSlug.value}`, {
        cacheTTL: 0,
        skipDeduplication: true,
      })
      apiDetailGuide.value = mapContent(response.data.data)
    } catch {
      apiDetailGuide.value = null
    }
  }
}

async function loadMembers(): Promise<void> {
  try {
    const response = await api.get<MemberIndexResponse>('/api/v1/community/members', {
      cacheTTL: 0,
      params: {
        search: memberSearch.value || undefined,
        discussion: typeof route.query.discussion === 'string' ? route.query.discussion : undefined,
        page: memberPage.value,
      },
    })
    apiMembers.value = response.data.data.map(mapMember)
    apiTopContributorMembers.value = (response.data.meta?.top_contributors ?? []).map(mapMember)
    apiOnlineMemberSummaries.value = (response.data.meta?.online_members ?? []).map(mapMember)
    memberStats.value = {
      total: response.data.meta?.stats?.total ?? 0,
      online: response.data.meta?.stats?.online ?? 0,
    }
    memberPagination.value = extractPagination(response.data.meta)
    membersLoaded.value = true
  } catch {
    apiMembers.value = []
    apiTopContributorMembers.value = []
    apiOnlineMemberSummaries.value = []
    memberStats.value = { total: 0, online: 0 }
    memberPagination.value = null
    membersLoaded.value = true
  }
}

async function loadBlockedUsers(): Promise<void> {
  if (!authStore.isAuthenticated) {
    blockedUsers.value = []
    blockedUsersLoaded.value = true
    return
  }

  try {
    const response = await api.get<BlockedUsersResponse>('/api/v1/user/blocked-users', {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    blockedUsers.value = response.data.data.map(mapMember)
    blockedUsersLoaded.value = true
  } catch {
    blockedUsers.value = []
    blockedUsersLoaded.value = true
    settingsStatusMessage.value = 'Unable to load blocked users.'
  }
}

async function blockMember(member: UiMemberProfile): Promise<void> {
  if (!member.id || !authStore.isAuthenticated) {
    return
  }

  blockingUserId.value = member.id
  settingsStatusMessage.value = ''

  try {
    const response = await api.post<BlockedUsersResponse>('/api/v1/user/blocked-users', {
      user_id: member.id,
    }, {
      skipDeduplication: true,
    })
    blockedUsers.value = response.data.data.map(mapMember)
    settingsStatusMessage.value = `${member.name} has been blocked.`
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, `Unable to block ${member.name}.`)
  } finally {
    blockingUserId.value = null
  }
}

async function blockMemberByUsername(): Promise<void> {
  const username = blockUsername.value.trim().replace(/^@/, '')

  if (!username || !authStore.isAuthenticated) {
    settingsStatusMessage.value = username ? 'Please log in to block members.' : 'Enter a username to block.'
    return
  }

  blockingUsername.value = true
  settingsStatusMessage.value = ''

  try {
    const response = await api.post<BlockedUsersResponse>('/api/v1/user/blocked-users', {
      username,
    }, {
      skipDeduplication: true,
    })
    blockedUsers.value = response.data.data.map(mapMember)
    blockUsername.value = ''
    settingsStatusMessage.value = `@${username} has been blocked.`
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, `Unable to block @${username}.`)
  } finally {
    blockingUsername.value = false
  }
}

async function unblockMember(member: UiMemberProfile): Promise<void> {
  if (!member.id || !authStore.isAuthenticated) {
    return
  }

  blockingUserId.value = member.id
  settingsStatusMessage.value = ''

  try {
    const response = await api.delete<BlockedUsersResponse>(`/api/v1/user/blocked-users/${member.id}`, {
      skipDeduplication: true,
    })
    blockedUsers.value = response.data.data.map(mapMember)
    settingsStatusMessage.value = `${member.name} has been unblocked.`
  } catch (error) {
    settingsStatusMessage.value = settingsApiError(error, `Unable to unblock ${member.name}.`)
  } finally {
    blockingUserId.value = null
  }
}

async function loadMemberDetail(): Promise<void> {
  if (page.value !== 'memberDetail' || !currentMemberSlug.value) {
    apiDetailMember.value = null
    return
  }

  try {
    const response = await api.get<MemberDetailResponse>(`/api/v1/community/members/${currentMemberSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailMember.value = mapMember(response.data.data)
  } catch {
    apiDetailMember.value = null
  }
}

async function loadMessages(): Promise<void> {
  try {
    const response = await api.get<MessageIndexResponse>('/api/v1/community/messages', {
      cacheTTL: 0,
    })
    apiMessageThreads.value = response.data.data.map(mapMessageThread)
    messagesLoaded.value = true

    if (route.query.inbox !== undefined) {
      apiCurrentMessageThread.value = null
      return
    }

    const requestedThread = Number(route.query.thread ?? 0)
    const selectedThread = requestedThread
      ? apiMessageThreads.value.find(thread => thread.id === requestedThread)
      : null

    if (selectedThread) {
      await loadMessageThread(selectedThread.id)
    } else if (!requestedThread) {
      apiCurrentMessageThread.value = null
    } else if (apiCurrentMessageThread.value && !apiMessageThreads.value.some(thread => thread.id === apiCurrentMessageThread.value?.id)) {
      apiCurrentMessageThread.value = null
    }
  } catch {
    apiMessageThreads.value = []
    apiCurrentMessageThread.value = null
    messagesLoaded.value = true
    messagesStatusMessage.value = 'Unable to load messages from the API.'
  }
}

async function loadMessageThread(threadId: number): Promise<void> {
  try {
    const response = await api.get<MessageDetailResponse>(`/api/v1/community/messages/${threadId}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiCurrentMessageThread.value = mapMessageThread(response.data.data)
  } catch {
    apiCurrentMessageThread.value = null
  }
}

async function openMessagesInbox(): Promise<void> {
  apiCurrentMessageThread.value = null

  if (route.path === '/messages' && route.query.inbox !== undefined) {
    return
  }

  await router.push({ path: '/messages', query: { inbox: '1' } })
}

async function openMessageThread(threadId: number): Promise<void> {
  await router.push({ path: '/messages', query: { thread: threadId } })
}

async function startMessage(profile: UiMemberProfile): Promise<void> {
  if (!profile.id) {
    return
  }
  if (!ensureAuthenticated('send messages')) {
    return
  }
  if (authStore.user?.id === profile.id) {
    messagesStatusMessage.value = 'You cannot message yourself.'
    return
  }

  messagesStatusMessage.value = ''
  startingMessageUserId.value = profile.id

  try {
    const response = await api.post<MessageDetailResponse>('/api/v1/community/messages', {
      participant_user_id: profile.id,
    })
    const thread = mapMessageThread(response.data.data)
    apiCurrentMessageThread.value = thread
    apiMessageThreads.value = [thread, ...apiMessageThreads.value.filter(item => item.id !== thread.id)]
    messageRecipientSearch.value = ''
    await router.push({ path: '/messages', query: { thread: thread.id } })
  } catch {
    messagesStatusMessage.value = 'Unable to start a message thread with this member.'
  } finally {
    startingMessageUserId.value = null
  }
}

async function startMessageFromSearch(): Promise<void> {
  const [member] = messageRecipientOptions.value
  if (member) {
    await startMessage(member)
  }
}

async function sendMessage(): Promise<void> {
  const body = messageBody.value.trim()
  if (!body || !currentThread.value) {
    return
  }

  sendingMessage.value = true

  try {
    const response = await api.post<{ data: ApiMessage }>(`/api/v1/community/messages/${currentThread.value.id}/messages`, { body })
    const message = mapMessage(response.data.data)
    apiCurrentMessageThread.value = {
      ...currentThread.value,
      preview: message.text,
      time: message.time,
      messages: [...currentThread.value.messages, message],
    }
    messageBody.value = ''
  } finally {
    sendingMessage.value = false
  }
}

function mapNotification(item: ApiNotification): UiNotification {
  const body = item.body || item.excerpt || ''
  const author = item.author?.name ?? ''

  return {
    id: item.id,
    slug: item.slug,
    title: item.title,
    text: item.excerpt || body,
    body,
    bodyParagraphs: body.split(/\n+/).map(paragraph => paragraph.trim()).filter(Boolean),
    category: item.category,
    categorySlug: item.category_slug,
    time: item.time_ago ?? item.published_label ?? '',
    icon: item.icon,
    tone: item.tone,
    unread: Boolean(item.unread),
    href: item.href ?? item.source_url ?? `/notifications/${item.slug}`,
    detailHref: item.detail_href ?? `/notifications/${item.slug}`,
    author,
    views: formatCount(item.views),
  }
}

async function loadNotifications(): Promise<void> {
  try {
    const category = ['all', 'unread'].includes(activeNotificationFilter.value) ? undefined : activeNotificationFilter.value
    const status = activeNotificationFilter.value === 'unread' ? 'unread' : undefined
    const response = await api.get<NotificationIndexResponse>('/api/v1/community/notifications', {
      cacheTTL: 0,
      params: {
        category,
        status,
        page: notificationPage.value,
      },
    })
    apiNotifications.value = response.data.data.map(mapNotification)
    notificationStats.value = response.data.meta?.stats ?? notificationStats.value
    notificationCategories.value = response.data.meta?.categories ?? []
    notificationPagination.value = extractPagination(response.data.meta)
    notificationsLoaded.value = true
    notificationStatusMessage.value = ''
  } catch {
    apiNotifications.value = []
    notificationCategories.value = []
    notificationPagination.value = null
    notificationStats.value = {
      total: 0,
      unread: 0,
      announcements: 0,
      lab_results: 0,
      discussions: 0,
      vendors: 0,
    }
    notificationsLoaded.value = true
    notificationStatusMessage.value = 'Unable to load notifications from the API.'
  }
}

async function loadNotificationDetail(): Promise<void> {
  if (page.value !== 'notificationDetail') {
    return
  }

  try {
    const response = await api.get<NotificationDetailResponse>(`/api/v1/community/notifications/${currentNotificationSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailNotification.value = mapNotification(response.data.data)
  } catch {
    apiDetailNotification.value = null
  }
}

function scrollToHeading(heading: string): void {
  const el = document.querySelector('.pv-prose')
  if (!el) return
  const headings = el.querySelectorAll('h2')
  for (const h of headings) {
    if (h.textContent?.trim() === heading) {
      h.scrollIntoView({ behavior: 'smooth', block: 'start' })
      break
    }
  }
}

function setNotificationFilter(filter: string): void {
  activeNotificationFilter.value = filter
  notificationPage.value = 1
  void loadNotifications()
}

function setNotificationPage(pageNumber: number): void {
  notificationPage.value = pageNumber
  void loadNotifications()
}

async function markNotificationRead(slug: string): Promise<void> {
  if (!authStore.isAuthenticated) {
    return
  }

  markingNotificationsRead.value = true

  try {
    const response = await api.post<NotificationDetailResponse>(`/api/v1/community/notifications/${slug}/read`)
    const updated = mapNotification(response.data.data)
    apiNotifications.value = apiNotifications.value.map(item => item.slug === updated.slug ? updated : item)
    if (apiDetailNotification.value?.slug === updated.slug) {
      apiDetailNotification.value = updated
    }
    await loadNotifications()
  } finally {
    markingNotificationsRead.value = false
  }
}

async function markAllNotificationsRead(): Promise<void> {
  if (!authStore.isAuthenticated) {
    return
  }

  markingNotificationsRead.value = true

  try {
    await api.post('/api/v1/community/notifications/read-all')
    apiNotifications.value = apiNotifications.value.map(item => ({ ...item, unread: false }))
    if (apiDetailNotification.value) {
      apiDetailNotification.value = { ...apiDetailNotification.value, unread: false }
    }
    await loadNotifications()
  } finally {
    markingNotificationsRead.value = false
  }
}

const notifications = computed(() => apiNotifications.value)
const notificationCounts = computed(() => ({
  total: notificationStats.value.total,
  unread: notificationStats.value.unread,
  announcements: notificationStats.value.announcements,
  labResults: notificationStats.value.lab_results,
  discussions: notificationStats.value.discussions,
  vendors: notificationStats.value.vendors,
  repliesUnread: notificationStats.value.replies_unread ?? 0,
  mentionsUnread: notificationStats.value.mentions_unread ?? 0,
  messagesUnread: notificationStats.value.messages_unread ?? notificationStats.value.message_unread ?? 0,
  systemUnread: notificationStats.value.system_unread ?? 0,
}))
const notificationFilterTabs = computed(() => [
  { slug: 'all', label: 'All' },
  { slug: 'unread', label: 'Unread', count: notificationCounts.value.unread },
  { slug: 'replies', label: 'Replies', count: notificationCounts.value.repliesUnread },
  { slug: 'mentions', label: 'Mentions', count: notificationCounts.value.mentionsUnread },
  { slug: 'messages', label: 'Messages', count: notificationCounts.value.messagesUnread },
  { slug: 'announcements', label: 'Announcements', count: notificationCounts.value.announcements },
  { slug: 'lab-results', label: 'Lab Results', count: notificationCounts.value.labResults },
  { slug: 'vendor-reviews', label: 'Vendors', count: notificationCounts.value.vendors },
])
const notificationFilterItems = computed(() => [
  { slug: 'all', label: 'All Notifications', icon: 'bell', count: notificationCounts.value.total },
  { slug: 'unread', label: 'Unread', icon: 'clock', count: notificationCounts.value.unread },
  { slug: 'replies', label: 'Replies', icon: 'message', count: notificationCounts.value.repliesUnread },
  { slug: 'mentions', label: 'Mentions', icon: 'users', count: notificationCounts.value.mentionsUnread },
  { slug: 'messages', label: 'Messages', icon: 'mail', count: notificationCounts.value.messagesUnread },
  { slug: 'announcements', label: 'Announcements', icon: 'megaphone', count: notificationCounts.value.announcements },
  { slug: 'lab-results', label: 'Lab Results', icon: 'flask', count: notificationCounts.value.labResults },
  { slug: 'discussions', label: 'Discussions', icon: 'message', count: notificationCounts.value.discussions },
  { slug: 'vendor-reviews', label: 'Vendors', icon: 'star', count: notificationCounts.value.vendors },
])
const notificationSummary = computed(() => notificationCategories.value.map(category => ({
  label: category.name,
  icon: category.icon,
  count: category.count,
  latest: `${category.count} published`,
})))
const notificationPaginationLabel = computed(() => paginationText(notificationPagination.value, 'notifications'))
const currentNotificationSlug = computed(() => String(route.params.slug ?? ''))
const primaryNotification = computed(() => apiDetailNotification.value ?? notifications.value.find(item => item.slug === currentNotificationSlug.value) ?? null)
const currentNotificationIndex = computed(() => notifications.value.findIndex(item => item.slug === primaryNotification.value?.slug))
const previousNotification = computed(() => currentNotificationIndex.value > 0 ? notifications.value[currentNotificationIndex.value - 1] : null)
function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

function memberHref(value?: string | null): string {
  const slug = String(value || '').trim().replace(/^@/, '').toLowerCase().replace(/\s+/g, '-')
  return slug ? `/members/${encodeURIComponent(slug)}` : '/members'
}

function safeExternalUrl(value: string): string {
  const decoded = value.replace(/&amp;/g, '&')
  return /^https?:\/\//i.test(decoded) || /^\/(?!\/)/.test(decoded) ? decoded : '#'
}

function looksLikeRichHtml(value: string): boolean {
  return /<\/?(p|h[1-6]|ul|ol|li|blockquote|pre|code|strong|em|a|img|figure|table|thead|tbody|tr|th|td|iframe|div|hr)\b/i.test(value)
}

function normalizeRichText(value?: string | null): string {
  const raw = String(value || '').trim()
  if (!raw || /^<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>$/i.test(raw)) {
    return ''
  }

  return looksLikeRichHtml(raw) ? sanitizeRichHtml(raw) : raw
}

function plainTextFromRichText(value?: string | null): string {
  const raw = String(value || '')
  if (!raw) return ''

  if (looksLikeRichHtml(raw) && typeof DOMParser !== 'undefined') {
    const doc = new DOMParser().parseFromString(raw, 'text/html')
    return (doc.body.textContent || '').replace(/\u00a0/g, ' ').trim()
  }

  return raw
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/(p|li|blockquote|h[1-6])>/gi, '\n')
    .replace(/<[^>]+>/g, '')
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .trim()
}

function isRichTextEmpty(value?: string | null): boolean {
  const raw = String(value || '')
  return !plainTextFromRichText(raw).trim() && !/<(img|iframe|table)\b/i.test(raw)
}

function quoteHtml(value: string): string {
  const text = plainTextFromRichText(value).trim()
  return text ? `<blockquote><p>${escapeHtml(text).replace(/\n+/g, '<br>')}</p></blockquote><p></p>` : ''
}

function linkifyMentions(root: ParentNode): void {
  if (typeof document === 'undefined') return
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT)
  const nodes: Text[] = []
  let current = walker.nextNode()
  while (current) {
    if (current.parentElement?.closest('a, code, pre')) {
      current = walker.nextNode()
      continue
    }
    nodes.push(current as Text)
    current = walker.nextNode()
  }

  nodes.forEach(node => {
    const value = node.nodeValue || ''
    const regex = /(^|[^\w@])@([A-Za-z0-9_]{3,40})/g
    let match: RegExpExecArray | null
    let lastIndex = 0
    const fragment = document.createDocumentFragment()
    let changed = false

    while ((match = regex.exec(value))) {
      const full = match[0] || ''
      const prefix = match[1] || ''
      const username = match[2] || ''
      const atIndex = match.index + prefix.length
      fragment.append(value.slice(lastIndex, atIndex))
      const link = document.createElement('a')
      link.href = memberHref(username)
      link.className = 'pv-mention'
      link.textContent = `@${username}`
      fragment.append(link)
      lastIndex = match.index + full.length
      changed = true
    }

    if (!changed) return
    fragment.append(value.slice(lastIndex))
    node.parentNode?.replaceChild(fragment, node)
  })
}

function sanitizeRichHtml(value: string): string {
  if (typeof document === 'undefined') {
    return escapeHtml(value)
  }

  const template = document.createElement('template')
  template.innerHTML = value
  const allowedTags = new Set(['P', 'BR', 'STRONG', 'B', 'EM', 'I', 'U', 'S', 'BLOCKQUOTE', 'UL', 'OL', 'LI', 'PRE', 'CODE', 'H2', 'H3', 'A', 'IMG', 'FIGURE', 'DIV', 'IFRAME', 'TABLE', 'THEAD', 'TBODY', 'TR', 'TH', 'TD', 'HR'])

  const cleanNode = (node: Node): void => {
    if (node.nodeType !== Node.ELEMENT_NODE) return
    const element = node as HTMLElement
    const tag = element.tagName

    if (tag === 'SCRIPT' || tag === 'STYLE') {
      element.remove()
      return
    }

    if (!allowedTags.has(tag)) {
      element.replaceWith(...Array.from(element.childNodes))
      return
    }

    Array.from(element.attributes).forEach(attribute => {
      const name = attribute.name.toLowerCase()
      const keepClass = name === 'class' && /^(pv-|ProseMirror)/.test(attribute.value)
      const keepTableSpan = ['colspan', 'rowspan'].includes(name) && ['TD', 'TH'].includes(tag)
      const keepEmbedData = name.startsWith('data-') && tag === 'DIV'
      const keepFrameAttr = ['frameborder', 'allowfullscreen', 'allow', 'title', 'width', 'height'].includes(name) && tag === 'IFRAME'
      if (!keepClass && !keepTableSpan && !keepEmbedData && !keepFrameAttr && !['href', 'src', 'alt', 'target', 'rel'].includes(name)) {
        element.removeAttribute(attribute.name)
      }
    })

    if (tag === 'A') {
      const href = safeExternalUrl(element.getAttribute('href') || '')
      element.setAttribute('href', href)
      element.setAttribute('target', '_blank')
      element.setAttribute('rel', 'noreferrer')
      element.classList.add('pv-link')
    }

    if (tag === 'IMG') {
      const src = safeExternalUrl(element.getAttribute('src') || '')
      if (src === '#') {
        element.remove()
        return
      }
      element.setAttribute('src', src)
      element.setAttribute('alt', element.getAttribute('alt') || 'Attached image')
    }

    if (tag === 'IFRAME') {
      const src = safeExternalUrl(element.getAttribute('src') || '')
      const allowedEmbed = /^(https:\/\/(?:www\.)?youtube(?:-nocookie)?\.com\/embed\/[a-zA-Z0-9_-]+|https:\/\/player\.vimeo\.com\/video\/\d+)/i.test(src)
      if (!allowedEmbed) {
        element.remove()
        return
      }
      element.setAttribute('src', src)
      element.setAttribute('frameborder', '0')
      element.setAttribute('allowfullscreen', 'true')
    }

    Array.from(element.childNodes).forEach(cleanNode)
  }

  Array.from(template.content.childNodes).forEach(cleanNode)
  linkifyMentions(template.content)
  return template.innerHTML
}

function mediaEmbed(url: string): string | null {
  const decoded = safeExternalUrl(url)
  const youtube = decoded.match(/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/)
  if (youtube) {
    return `<div class="pv-embed"><iframe src="https://www.youtube.com/embed/${youtube[1]}" frameborder="0" allowfullscreen></iframe></div>`
  }

  const vimeo = decoded.match(/vimeo\.com\/(\d+)/)
  if (vimeo) {
    return `<div class="pv-embed"><iframe src="https://player.vimeo.com/video/${vimeo[1]}" frameborder="0" allowfullscreen></iframe></div>`
  }

  if (/\.(png|jpe?g|webp|gif)(\?.*)?$/i.test(decoded)) {
    return `<figure class="pv-inline-image"><img src="${escapeHtml(decoded)}" alt="Linked image"></figure>`
  }

  return null
}

function formatInlineText(value: string): string {
  const replacements: string[] = []
  const stash = (html: string) => {
    const token = `\uE000${replacements.length}\uE000`
    replacements.push(html)
    return token
  }

  let output = escapeHtml(value)
  output = output.replace(/`([^`]+)`/g, (_match, code: string) => stash(`<code>${code}</code>`))
  output = output.replace(/!\[([^\]]*)\]\((https?:\/\/[^\s)]+)\)/gi, (_match, alt: string, url: string) => {
    const safeUrl = safeExternalUrl(url)
    return stash(`<figure class="pv-inline-image"><img src="${escapeHtml(safeUrl)}" alt="${escapeHtml(alt || 'Linked image')}"></figure>`)
  })
  output = output.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/gi, (_match, label: string, url: string) => {
    const safeUrl = safeExternalUrl(url)
    return stash(`<a href="${escapeHtml(safeUrl)}" target="_blank" rel="noreferrer" class="pv-link">${label}</a>`)
  })
  output = output.replace(/https?:\/\/[^\s<]+/g, (url: string) => {
    const cleanUrl = url.replace(/[),.;!?]+$/, '')
    const trailing = url.slice(cleanUrl.length)
    const embed = mediaEmbed(cleanUrl)
    if (embed) {
      return stash(embed) + trailing
    }

    const safeUrl = safeExternalUrl(cleanUrl)
    return stash(`<a href="${escapeHtml(safeUrl)}" target="_blank" rel="noreferrer" class="pv-link">${cleanUrl}</a>`) + trailing
  })
  output = output.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
  output = output.replace(/__([^_]+)__/g, '<strong>$1</strong>')
  output = output.replace(/\*([^*\n]+)\*/g, '<em>$1</em>')
  output = output.replace(/_([^_\n]+)_/g, '<em>$1</em>')
  output = output.replace(
    /(^|[^\w@])@([A-Za-z0-9_]{3,40})/g,
    (_match, prefix: string, username: string) => `${prefix}<a href="${memberHref(username)}" class="pv-mention">@${username}</a>`
  )

  return replacements.reduce((html, replacement, index) => html.split(`\uE000${index}\uE000`).join(replacement), output)
}

function renderFormattedText(text?: string | null): string {
  const raw = String(text || '').trim()
  if (!raw) return ''
  if (looksLikeRichHtml(raw)) return sanitizeRichHtml(raw)

  const lines = raw.replace(/\r\n/g, '\n').split('\n')
  const html: string[] = []
  let index = 0

  while (index < lines.length) {
    const line = lines[index] ?? ''
    if (!line.trim()) {
      index += 1
      continue
    }

    if (line.trim().startsWith('```')) {
      const codeLines: string[] = []
      index += 1
      while (index < lines.length && !(lines[index] ?? '').trim().startsWith('```')) {
        codeLines.push(lines[index] ?? '')
        index += 1
      }
      index += 1
      html.push(`<pre><code>${escapeHtml(codeLines.join('\n'))}</code></pre>`)
      continue
    }

    const heading = line.match(/^(#{1,3})\s+(.+)$/)
    if (heading) {
      const marker = heading[1] ?? '##'
      const headingText = heading[2] ?? ''
      const level = marker.length + 2
      html.push(`<h${level}>${formatInlineText(headingText)}</h${level}>`)
      index += 1
      continue
    }

    if (/^>\s?/.test(line)) {
      const quoteLines: string[] = []
      while (index < lines.length && /^>\s?/.test(lines[index] ?? '')) {
        quoteLines.push((lines[index] ?? '').replace(/^>\s?/, ''))
        index += 1
      }
      html.push(`<blockquote>${quoteLines.map(formatInlineText).join('<br>')}</blockquote>`)
      continue
    }

    if (/^\s*[-*]\s+/.test(line)) {
      const items: string[] = []
      while (index < lines.length && /^\s*[-*]\s+/.test(lines[index] ?? '')) {
        items.push((lines[index] ?? '').replace(/^\s*[-*]\s+/, ''))
        index += 1
      }
      html.push(`<ul>${items.map(item => `<li>${formatInlineText(item)}</li>`).join('')}</ul>`)
      continue
    }

    if (/^\s*\d+\.\s+/.test(line)) {
      const items: string[] = []
      while (index < lines.length && /^\s*\d+\.\s+/.test(lines[index] ?? '')) {
        items.push((lines[index] ?? '').replace(/^\s*\d+\.\s+/, ''))
        index += 1
      }
      html.push(`<ol>${items.map(item => `<li>${formatInlineText(item)}</li>`).join('')}</ol>`)
      continue
    }

    const paragraph: string[] = []
    while (index < lines.length && (lines[index] ?? '').trim()) {
      if (/^(#{1,3})\s+|^>\s?|^\s*[-*]\s+|^\s*\d+\.\s+|^```/.test(lines[index] ?? '')) break
      paragraph.push(lines[index] ?? '')
      index += 1
    }
    html.push(`<p>${formatInlineText(paragraph.join(' '))}</p>`)
  }

  return html.join('')
}

const nextNotification = computed(() => currentNotificationIndex.value >= 0 && currentNotificationIndex.value < notifications.value.length - 1 ? notifications.value[currentNotificationIndex.value + 1] : null)
const legalPage = computed(() => {
  if (page.value === 'legalPrivacy') {
    return publicLegalPages.value.privacy
  }

  if (page.value === 'legalRules') {
    return publicLegalPages.value.rules
  }

  return publicLegalPages.value.terms
})

function thumbnailStyle(index: number, imageUrl?: string | null) {
  if (imageUrl) {
    return {
      backgroundImage: `url(${assetUrl(imageUrl)})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
    }
  }

  const positions = ['0% 0%', '50% 0%', '100% 0%', '0% 50%', '50% 50%', '100% 50%', '0% 100%', '50% 100%', '100% 100%']
  return {
    backgroundImage: `url(${researchImage})`,
    backgroundSize: '300% 300%',
    backgroundPosition: positions[index % positions.length] ?? '0% 0%',
  }
}

function contentThumbnailStyle(item: UiContentItem, fallbackIndex = 0) {
  return thumbnailStyle(item.imageIndex || fallbackIndex, item.imageUrl)
}

const TipTapComposer = defineComponent({
  props: {
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Write...' },
    maxLength: { type: Number, default: 10000 },
    compact: { type: Boolean, default: false },
  },
  emits: {
    'update:modelValue': (value: string) => typeof value === 'string',
  },
  setup(props, { emit }) {
    const preview = ref(false)
    const editor = useEditor({
      content: props.modelValue || '<p></p>',
      extensions: [
        StarterKit.configure({
          heading: { levels: [2, 3] },
          link: false,
        }),
        Link.configure({
          openOnClick: false,
          autolink: true,
          linkOnPaste: true,
          defaultProtocol: 'https',
        }),
        Image.configure({ allowBase64: false }),
        Placeholder.configure({ placeholder: props.placeholder }),
        CharacterCount.configure({ limit: props.maxLength }),
        Table.configure({ resizable: true }),
        TableRow,
        TableHeader,
        TableCell,
        Youtube.configure({
          controls: true,
          nocookie: true,
          width: 720,
          height: 405,
        }),
      ],
      editorProps: {
        attributes: {
          class: 'pv-tiptap-prose',
          spellcheck: 'true',
        },
      },
      onUpdate: ({ editor: activeEditor }) => {
        emit('update:modelValue', activeEditor.isEmpty ? '' : activeEditor.getHTML())
      },
    })

    watch(() => props.modelValue, value => {
      const activeEditor = editor.value
      if (!activeEditor) return
      const next = value || '<p></p>'
      if (activeEditor.getHTML() === next) return
      activeEditor.commands.setContent(next, { emitUpdate: false })
    })

    onUnmounted(() => {
      editor.value?.destroy()
    })

    const withEditor = (callback: (activeEditor: NonNullable<typeof editor.value>) => void) => {
      const activeEditor = editor.value
      if (!activeEditor) return
      callback(activeEditor)
    }

    const characterCount = () => {
      const storage = editor.value?.storage.characterCount as { characters?: () => number } | undefined
      return storage?.characters?.() ?? plainTextFromRichText(props.modelValue).length
    }

    const setLink = () => withEditor(activeEditor => {
      const previous = String(activeEditor.getAttributes('link').href || '')
      const url = window.prompt('Link URL', previous || 'https://')
      if (url === null) return
      if (!url.trim()) {
        activeEditor.chain().focus().extendMarkRange('link').unsetLink().run()
        return
      }
      activeEditor.chain().focus().extendMarkRange('link').setLink({ href: safeExternalUrl(url) }).run()
    })

    const setImage = () => withEditor(activeEditor => {
      const url = window.prompt('Image URL', 'https://')
      if (!url) return
      activeEditor.chain().focus().setImage({ src: safeExternalUrl(url), alt: 'Attached image' }).run()
    })

    const setVideo = () => withEditor(activeEditor => {
      const url = window.prompt('YouTube URL', 'https://')
      if (!url) return
      activeEditor.chain().focus().setYoutubeVideo({ src: safeExternalUrl(url) }).run()
    })

    const setEmoji = () => withEditor(activeEditor => {
      const emoji = window.prompt('Emoji', ':)')
      if (!emoji) return
      activeEditor.chain().focus().insertContent(emoji).run()
    })

    const actions = [
      { key: 'bold', label: 'B', title: 'Bold', active: () => editor.value?.isActive('bold'), run: () => withEditor(activeEditor => activeEditor.chain().focus().toggleBold().run()) },
      { key: 'italic', label: 'I', title: 'Italic', active: () => editor.value?.isActive('italic'), run: () => withEditor(activeEditor => activeEditor.chain().focus().toggleItalic().run()) },
      { key: 'heading', label: 'H', title: 'Heading', active: () => editor.value?.isActive('heading', { level: 2 }), run: () => withEditor(activeEditor => activeEditor.chain().focus().toggleHeading({ level: 2 }).run()) },
      { key: 'quote', label: '""', title: 'Quote', active: () => editor.value?.isActive('blockquote'), run: () => withEditor(activeEditor => activeEditor.chain().focus().toggleBlockquote().run()) },
      { key: 'bullet', label: 'List', title: 'Bullet list', active: () => editor.value?.isActive('bulletList'), run: () => withEditor(activeEditor => activeEditor.chain().focus().toggleBulletList().run()) },
      { key: 'numbered', label: '1.', title: 'Numbered list', active: () => editor.value?.isActive('orderedList'), run: () => withEditor(activeEditor => activeEditor.chain().focus().toggleOrderedList().run()) },
      { key: 'code', label: '</>', title: 'Code block', active: () => editor.value?.isActive('codeBlock'), run: () => withEditor(activeEditor => activeEditor.chain().focus().toggleCodeBlock().run()) },
      { key: 'link', label: 'Link', title: 'Link', run: setLink },
      { key: 'image', label: 'Image', title: 'Image', run: setImage },
      { key: 'table', label: 'Table', title: 'Table', active: () => editor.value?.isActive('table'), run: () => withEditor(activeEditor => activeEditor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()) },
      { key: 'emoji', label: 'Smile', title: 'Emoji', run: setEmoji },
      { key: 'video', label: 'Video', title: 'YouTube embed', run: setVideo },
    ]

    return () => h('div', { class: ['pv-tiptap', props.compact ? 'pv-tiptap--compact' : ''] }, [
      h('div', { class: 'pv-tiptap-modebar' }, [
        h('button', {
          type: 'button',
          class: { active: !preview.value },
          onClick: () => { preview.value = false },
        }, [h(PvIcon, { name: 'edit' }), 'Write']),
        h('button', {
          type: 'button',
          class: { active: preview.value },
          onClick: () => { preview.value = true },
        }, [h(PvIcon, { name: 'eye' }), 'Preview']),
      ]),
      preview.value
        ? h('div', {
          class: 'pv-tiptap-preview pv-rich-text',
          innerHTML: renderFormattedText(editor.value?.getHTML() || props.modelValue),
        })
        : h('div', { class: 'pv-tiptap-shell' }, [
          h('div', { class: 'pv-tiptap-toolbar', 'aria-label': 'Text formatting toolbar' }, [
            ...actions.map(action => h('button', {
              key: action.key,
              type: 'button',
              title: action.title,
              'aria-label': action.title,
              class: { active: Boolean(action.active?.()) },
              disabled: !editor.value,
              onClick: action.run,
            }, action.label)),
            h('span', { class: 'pv-tiptap-spacer' }),
            h('button', {
              type: 'button',
              title: 'Undo',
              'aria-label': 'Undo',
              disabled: !editor.value?.can().undo(),
              onClick: () => withEditor(activeEditor => activeEditor.chain().focus().undo().run()),
            }, 'Undo'),
            h('button', {
              type: 'button',
              title: 'Redo',
              'aria-label': 'Redo',
              disabled: !editor.value?.can().redo(),
              onClick: () => withEditor(activeEditor => activeEditor.chain().focus().redo().run()),
            }, 'Redo'),
          ]),
          h(EditorContent, { editor: editor.value, class: 'pv-tiptap-editor' }),
          h('div', { class: 'pv-tiptap-foot' }, [
            h('span', 'Use formatting, drag images in, or paste links.'),
            h('small', `${characterCount()} characters`),
          ]),
        ]),
    ])
  },
})

const PaginationBlock = defineComponent({
  props: {
    label: { type: String, default: '' },
    meta: { type: Object as PropType<PaginationMeta | null>, default: null },
  },
  emits: {
    page: (value: number) => Number.isFinite(value),
  },
  setup(props, { emit }) {
    return () => {
      const meta = props.meta
      const current = Number(meta?.current_page ?? 1)
      const last = Number(meta?.last_page ?? 1)
      const total = Number(meta?.total ?? 0)
      const label = props.label || paginationText(meta, 'items')

      if (!props.label && (!meta || total <= Number(meta.per_page ?? total))) {
        return null
      }

      const first = Math.max(1, Math.min(current - 2, Math.max(1, last - 4)))
      const pages = Array.from({ length: Math.min(5, last) }, (_, index) => first + index).filter(pageNumber => pageNumber <= last)

      return h('div', { class: 'pv-pagination' }, [
        h('button', { disabled: current <= 1, onClick: () => emit('page', current - 1) }, 'Previous'),
        h('span', label),
        ...pages.map(pageNumber => h('button', {
          class: pageNumber === current ? 'active' : '',
          disabled: pageNumber === current,
          onClick: () => emit('page', pageNumber),
        }, String(pageNumber))),
        h('button', { disabled: current >= last, onClick: () => emit('page', current + 1) }, 'Next'),
      ])
    }
  },
})

const MemberProfile = defineComponent({
  props: { profile: { type: Object as PropType<UiMemberProfile>, required: true } },
  setup(props) {
    const badgeIcons = ['shield', 'flask', 'star', 'trophy']
    const activeTab = ref<MemberTabKey>('overview')
    return () => {
      const profile = props.profile
      const stats = profile.stats
      const isSelf = Boolean(profile.id && authStore.user?.id === profile.id)
      const tabs: Array<{ key: MemberTabKey; label: string }> = [
        { key: 'overview', label: 'Overview' },
        { key: 'activity', label: 'Activity' },
        { key: 'posts', label: `Posts (${formatCount(stats.posts)})` },
        { key: 'reviews', label: `Reviews (${formatCount(stats.reviews)})` },
        { key: 'guides', label: `Guides (${formatCount(stats.guides)})` },
        { key: 'badges', label: 'Badges' },
      ]
      const statCards: Array<[string, string, string]> = [
        ['Posts', formatCount(stats.posts), 'discussions'],
        ['Likes Received', formatCount(stats.likes), 'thumbs'],
        ['Solutions', formatCount(stats.solutions), 'shield'],
        ['Lab Reports', formatCount(stats.lab_reports ?? stats.guides), 'flask'],
        ['Thanks Received', formatCount(stats.thanks ?? stats.reviews), 'star'],
      ]

      return h('div', { class: 'pv-content-grid' }, [
        h('main', { class: 'pv-stack' }, [
          h('nav', { class: 'pv-breadcrumbs' }, `Members › ${profile.name}`),
          h('article', { class: ['pv-member-hero', 'pv-member-hero--banner'] }, [
            profile.avatarUrl
              ? h('span', { class: ['pv-avatar', 'pv-avatar--xl', profile.color] }, [h('img', { src: assetUrl(profile.avatarUrl), alt: profile.name, class: 'pv-avatar-img' })])
              : h('span', { class: ['pv-avatar', 'pv-avatar--xl', profile.color] }, profile.initial),
            h('div', { class: 'pv-topic-main' }, [h('h1', [profile.name, ' ', h('span', { class: 'pv-tag trusted' }, profile.role)]), h('p', `${profile.badge ?? profile.group} · Joined ${profile.joined} · Last seen ${profile.lastActive}`), h('p', profile.bio), h('span', { class: 'pv-chip-row' }, profile.interests.map(interest => h('span', interest)))]),
            h('div', { class: 'pv-member-actions' }, isSelf
              ? [h(RouterLink, { to: '/settings', class: 'pv-primary-button' }, () => [h(PvIcon, { name: 'settings' }), ' Edit Profile'])]
              : [
                  h('button', { class: 'pv-primary-button', onClick: () => void startMessage(profile) }, [h(PvIcon, { name: 'message' }), ' Message']),
                  h('button', { class: 'pv-small-button', onClick: () => void toggleMemberFollow(profile) }, [h(PvIcon, { name: 'users' }), isFollowingMember(profile) ? ' Following' : ' Follow']),
                  h('button', { class: 'pv-small-button', disabled: blockingUserId.value === profile.id, onClick: () => void blockMember(profile) }, [h(PvIcon, { name: 'close' }), blockingUserId.value === profile.id ? ' Blocking...' : ' Block']),
                ]),
          ]),
          h('div', { class: 'pv-metrics pv-metrics--member' }, statCards.map(stat => h('span', [h(PvIcon, { name: stat[2] }), h('strong', stat[1]), h('small', stat[0])]))),
          h('div', { class: 'pv-tabs pv-tabs--line' }, tabs.map(tab => h('button', { class: activeTab.value === tab.key ? 'active' : '', onClick: () => { activeTab.value = tab.key } }, tab.label))),
          memberTabPanel(profile, activeTab.value, badgeIcons),
        ]),
        h('aside', { class: 'pv-stack' }, [
          h('article', { class: 'pv-panel' }, [h('h2', 'Reputation'), h('div', { class: 'pv-ring' }, [h('strong', formatCount(stats.reputation)), h('span', 'Reputation')])]),
          h('article', { class: 'pv-panel' }, [h('h2', 'Community Stats'), h('dl', { class: 'pv-data-list' }, [['Posts created', formatCount(stats.posts)], ['Likes received', formatCount(stats.likes)], ['Solutions provided', formatCount(stats.solutions)], ['Reports reviewed', formatCount(stats.lab_reports ?? stats.guides)]].map(row => h('div', [h('dt', row[0]), h('dd', row[1])])))]),
          h('article', { class: 'pv-panel' }, [h('h2', 'Following'), h('div', { class: 'pv-avatar-stack' }, onlineMembers.value.map(member => h('span', { class: ['pv-avatar', member.color] }, member.initial)).concat(h('span', { class: 'pv-more' }, `+${Math.max(0, memberStats.value.total - onlineMembers.value.length)}`)))]),
        ]),
      ])
    }
  },
})

function memberTabPanel(profile: UiMemberProfile, tab: MemberTabKey, badgeIcons: string[]) {
  if (tab === 'overview') {
    return h('div', { class: 'pv-profile-grid' }, [
      h('article', { class: 'pv-panel' }, [
        h('h2', `About ${profile.name}`),
        h('p', profile.bio || 'No bio has been added yet.'),
        h('dl', { class: 'pv-data-list' }, [
          h('div', [h('dt', 'Location'), h('dd', profile.location || 'Not shared')]),
          h('div', [h('dt', 'Website'), h('dd', profile.websiteUrl ? h('a', { href: profile.websiteUrl, target: '_blank', rel: 'noreferrer' }, profile.websiteUrl) : 'Not shared')]),
          h('div', [h('dt', 'Member Since'), h('dd', profile.joined)]),
          h('div', [h('dt', 'Last Active'), h('dd', profile.lastActive)]),
        ]),
      ]),
      memberItemPanel('Recent Contributions', profile.tabData.overview ?? [], 'No recent public contributions.'),
      memberBadgePanel(profile, badgeIcons),
    ])
  }

  if (tab === 'activity') {
    const activities = profile.tabData.activity ?? profile.activities

    return h('article', { class: 'pv-panel' }, [
      h('h2', 'Activity'),
      h('div', { class: 'pv-mini-list' }, activities.length > 0
        ? activities.map(activity => h('span', { class: 'pv-mini-row' }, [
          h(PvIcon, { name: activity.icon }),
          h('span', [h('strong', activity.title), h('small', activity.subtitle ? `${activity.subtitle} · ${activity.time}` : activity.time)]),
        ]))
        : [h('p', { class: 'pv-muted' }, 'No recent public activity.')]),
    ])
  }

  if (tab === 'badges') {
    return memberBadgePanel(profile, badgeIcons)
  }

  const title = tab === 'posts' ? 'Posts' : tab === 'reviews' ? 'Reviews' : 'Guides'
  return memberItemPanel(title, profile.tabData[tab] as UiMemberTabItem[] | undefined ?? [], `No ${title.toLowerCase()} yet.`)
}

function memberItemPanel(title: string, items: UiMemberTabItem[], emptyText: string) {
  return h('article', { class: 'pv-panel' }, [
    h('h2', title),
    h('div', { class: 'pv-mini-list' }, items.length > 0
      ? items.map(item => {
        const content = [
          h(PvIcon, { name: item.type === 'review' ? 'star' : item.type === 'lab' ? 'flask' : 'message' }),
          h('span', [h('strong', item.title), h('small', [item.text, item.time].filter(Boolean).join(' · '))]),
          h(PvIcon, { name: 'chevron' }),
        ]

        return item.href
          ? h(RouterLink, { to: item.href, class: 'pv-mini-row' }, () => content)
          : h('span', { class: 'pv-mini-row' }, content)
      })
      : [h('p', { class: 'pv-muted' }, emptyText)]),
  ])
}

function memberBadgePanel(profile: UiMemberProfile, badgeIcons: string[]) {
  const badges = profile.tabData.badges ?? profile.badges

  return h('article', { class: 'pv-panel' }, [
    h('header', { class: 'pv-panel-header' }, [
      h('h2', 'Badges'),
      h('span', { class: 'pv-purple-link' }, `${badges.length} earned`),
    ]),
    badges.length > 0
      ? h('div', { class: 'pv-badge-grid' }, badges.map((badge, index) => h('span', [h(PvIcon, { name: badgeIcons[index % badgeIcons.length] ?? 'shield' }), h('small', badge)])))
      : h('p', { class: 'pv-muted' }, 'No badges earned yet.'),
  ])
}

const MessageBubble = defineComponent({
  props: {
    side: { type: String, required: true },
    text: { type: String, required: true },
    time: { type: String, required: true },
    file: { type: Boolean, default: false },
    attachmentName: { type: String, default: '' },
    attachmentLabel: { type: String, default: '' },
    avatarInitial: { type: String, default: '' },
    avatarColor: { type: String, default: 'purple' },
  },
  setup(props) {
    return () => h('div', { class: ['pv-message-bubble', `pv-message-bubble--${props.side}`] }, [
      props.side === 'in' ? h('span', { class: ['pv-avatar', props.avatarColor] }, props.avatarInitial) : null,
      h('div', [
        props.file ? h('span', { class: 'pv-file-card' }, [
          h(PvIcon, { name: 'document' }),
          h('strong', props.attachmentName),
          props.attachmentLabel ? h('small', props.attachmentLabel) : null,
        ]) : null,
        h('p', props.text),
        h('small', props.time),
      ]),
    ])
  },
})

const SettingsScreens = defineComponent({
  props: { page: { type: String, required: true } },
  setup(props) {
     const tabs: Array<[string, string, string, string]> = [['settingsProfile', '/settings', 'Profile', 'user'], ['settingsAccount', '/settings/account', 'Account', 'users'], ['settingsSecurity', '/settings/security', 'Security', 'shield'], ['settingsPrivacy', '/settings/privacy', 'Privacy', 'lock'], ['settingsBilling', '/settings/billing', 'Billing', 'credit-card'], ['settingsNotifications', '/settings/notifications', 'Notifications', 'bell'], ['settingsPreferences', '/settings/preferences', 'Preferences', 'settings'], ['settingsBlocked', '/settings/blocked-users', 'Blocked Users', 'close'], ['settingsSessions', '/settings/sessions', 'Sessions', 'document'], ['settingsDanger', '/settings/danger-zone', 'Danger Zone', 'shield']]
     return () => h('div', { class: 'pv-settings-grid' }, [
      h('aside', { class: 'pv-settings-nav' }, [h('small', 'ACCOUNT SETTINGS'), ...tabs.map(tab => h(RouterLink, { to: tab[1], class: props.page === tab[0] ? 'active' : '' }, () => [h(PvIcon, { name: tab[3] }), tab[2]]))]),
      h('main', { class: 'pv-stack' }, [h('header', { class: 'pv-page-header' }, [h('div', [h('h1', settingsPageTitle(props.page)), h('p', settingsPageDescription(props.page))])]), settingsMain(props.page)]),
      h('aside', { class: 'pv-stack' }, [settingsSummary(), props.page === 'settingsSecurity' ? tipsPanel('Security Tips', ['Use a strong password', 'Enable 2FA', 'Keep your email secure']) : props.page === 'settingsPrivacy' ? tipsPanel('Privacy Tips', ['Review your privacy settings', 'Be careful with shared info', 'Keep your account secure']) : quickActions(), h('article', { class: 'pv-panel' }, [h('h2', 'Need Help?'), h('p', 'Visit our Guides & FAQ section or contact the support team if you need assistance.'), h(RouterLink, { to: '/guides', class: 'pv-primary-button' }, () => 'Visit Guides & FAQ')])]),
    ])
  },
})

function settingsPageTitle(pageName: string): string {
  const titles: Record<string, string> = {
    settingsProfile: 'Profile',
    settingsAccount: 'Account',
    settingsSecurity: 'Security',
    settingsPrivacy: 'Privacy',
    settingsBilling: 'Billing',
    settingsNotifications: 'Notifications',
    settingsPreferences: 'Preferences',
    settingsBlocked: 'Blocked Users',
    settingsSessions: 'Sessions',
    settingsDanger: 'Danger Zone',
  }

  return titles[pageName] ?? 'Account Settings'
}

function settingsPageDescription(pageName: string): string {
  const descriptions: Record<string, string> = {
    settingsProfile: 'Manage your public profile, bio, website and avatar.',
    settingsAccount: 'Manage your username, email status, timezone and language.',
    settingsSecurity: 'Manage your password, two-factor authentication and active sessions.',
    settingsPrivacy: 'Control profile visibility, direct messages and activity signals.',
    settingsBilling: 'Manage your membership plan, payment methods, and billing history.',
    settingsNotifications: 'Choose how community alerts reach you.',
    settingsPreferences: 'Tune display, language and browsing preferences.',
    settingsBlocked: 'Manage members you do not want to interact with.',
    settingsSessions: 'Review and revoke active browser sessions and auth tokens.',
    settingsDanger: 'Export your account data or sign out everywhere.',
  }

  return descriptions[pageName] ?? 'Manage your account preferences and security settings.'
}

function settingsMain(pageName: string) {
  if (!authStore.isAuthenticated) {
    return h('article', { class: 'pv-panel' }, [
      h('h2', 'Log in required'),
      h('p', { class: 'pv-muted' }, 'Account settings are available after you log in.'),
      h(RouterLink, { to: '/login', class: 'pv-primary-button' }, () => 'Log In'),
    ])
  }

  if (!settingsLoaded.value) {
    return h('article', { class: 'pv-panel' }, [
      h('h2', 'Loading settings'),
      h('p', { class: 'pv-muted' }, 'Fetching your account settings...'),
    ])
  }

  const status = settingsStatusMessage.value
    ? h('p', { class: 'pv-settings-status' }, settingsStatusMessage.value)
    : null

  if (pageName === 'settingsSecurity') {
    return h('div', { class: 'pv-stack' }, [
      status,
      h('article', { class: 'pv-panel pv-settings-card' }, [h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'lock' })]), h('div', [h('h2', 'Change Password'), h('div', { class: 'pv-input-group' }, [
        settingsInput('Current Password', passwordSettingsForm.value.current_password, value => { passwordSettingsForm.value.current_password = value }, 'password'),
        settingsInput('New Password', passwordSettingsForm.value.new_password, value => { passwordSettingsForm.value.new_password = value }, 'password'),
        settingsInput('Confirm New Password', passwordSettingsForm.value.new_password_confirmation, value => { passwordSettingsForm.value.new_password_confirmation = value }, 'password'),
      ]), h('button', { class: 'pv-primary-button', disabled: changingSettingsPassword.value, onClick: changeSettingsPassword }, changingSettingsPassword.value ? 'Updating...' : 'Update Password')])]),
      twoFactorSecurityPanel(),
      h('article', { class: 'pv-panel pv-settings-card' }, [h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'document' })]), h('div', [h('h2', 'Login Sessions'), h('p', 'Manage your active sessions across different devices.'), sessionList(4, false)]), h(RouterLink, { to: '/settings/sessions', class: 'pv-small-button' }, () => 'View Sessions')]),
    ])
  }
  if (pageName === 'settingsPrivacy') {
    return h('div', { class: 'pv-stack' }, [
      status,
      h('article', { class: 'pv-panel pv-settings-card' }, [h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'user' })]), h('div', [h('h2', 'Profile Visibility'), h('p', 'Choose who can view your profile.'), settingsRadioGroup('profile_visibility', [['everyone', 'Everyone'], ['members_only', 'Members Only'], ['nobody', 'Nobody']])])]),
      settingsSwitchCard('Public Profile', 'Allow your member profile to appear in community member lists.', 'eye', 'public_profile'),
      settingsSwitchCard('Show Online Status', 'Allow other members to see when you are online.', 'users', 'show_online'),
      h('article', { class: 'pv-panel pv-settings-card' }, [h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'message' })]), h('div', [h('h2', 'Direct Messages'), h('p', 'Control who can send you direct messages.'), settingsRadioGroup('direct_messages', [['everyone', 'Everyone'], ['members_only', 'Members Only'], ['nobody', 'Nobody']])])]),
      settingsSwitchCard('Activity Visibility', 'Display recent activity in community surfaces.', 'eye', 'show_recent_activity'),
      settingsSwitchCard('Read Topics', 'Allow read-topic signals to improve your community experience.', 'document', 'show_read_topics'),
      settingsSwitchCard('Typing Indicators', 'Show typing indicators when message features support them.', 'message', 'show_typing'),
      settingsSwitchCard('Data & Personalization', 'Personalize content and recommendations.', 'shield', 'personalize_experience'),
      settingsSwitchCard('Allow Analytics', 'Share anonymous usage data to improve the platform.', 'settings', 'allow_analytics'),
    ])
  }
  if (pageName === 'settingsAccount') {
    return h('article', { class: 'pv-panel pv-form-card' }, [
      h('h2', 'Account Information'),
      status,
      h('div', { class: 'pv-input-group' }, [
        settingsInput('Username', accountForm.value.username, value => { accountForm.value.username = value }),
        settingsInput('Email Address', accountForm.value.email, value => { accountForm.value.email = value }, 'email'),
      ]),
      h('div', { class: 'pv-settings-meta-grid' }, [
        h('span', [h('small', 'Email Status'), h('strong', authUserValue('email_verified_at') ? 'Verified' : 'Unverified')]),
        h('span', [h('small', 'Member Since'), h('strong', authUserDate('created_at') || 'Unknown')]),
        h('span', [h('small', 'Last Active'), h('strong', authUserDate('last_active') || 'Unknown')]),
      ]),
      h('div', { class: 'pv-two-col' }, [
        settingsInput('Timezone', accountForm.value.timezone, value => { accountForm.value.timezone = value }),
        settingsSelect('Language', userSettings.value.language, value => { userSettings.value.language = value }, [['en', 'English'], ['en-GB', 'English (UK)'], ['en-US', 'English (US)']]),
      ]),
      h('button', { class: 'pv-primary-button', disabled: savingSettings.value, onClick: async () => { await saveAccountProfile(); await saveUserSettings({ language: userSettings.value.language }) } }, savingSettings.value ? 'Saving...' : 'Save Account'),
    ])
  }
  if (pageName === 'settingsBilling') {
    return h('div', { class: 'pv-stack' }, [
      status,
      h('article', { class: 'pv-panel pv-settings-card' }, [
        h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'credit-card' })]),
        h('div', [
          h('h2', 'Current Plan'),
          h('div', { class: 'pv-settings-meta-grid' }, [
            h('span', [h('small', 'Tier'), h('strong', membershipTier.value === 'paid' ? 'Premium' : 'Free')]),
            h('span', [h('small', 'Status'), h('strong', membershipStatus.value?.status ?? (membershipTier.value === 'paid' ? 'Active' : 'N/A'))]),
            h('span', [h('small', 'Next Billing'), h('strong', membershipStatus.value?.current_period_end ? formatDate(String(membershipStatus.value.current_period_end)) : 'N/A')]),
          ]),
          membershipTier.value === 'paid'
            ? h('button', { class: 'pv-small-button pv-small-button--danger', onClick: cancelSubscription }, 'Cancel Subscription')
            : h(RouterLink, { to: '/pricing', class: 'pv-primary-button' }, () => 'Upgrade to Premium'),
        ]),
      ]),
      h('article', { class: 'pv-panel pv-settings-card' }, [
        h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'shield' })]),
        h('div', [
          h('h2', 'Payment Methods'),
          h('p', 'Manage your payment methods through your payment provider.'),
          h('div', { class: 'pv-two-col' }, [
            h('a', { href: 'https://dashboard.stripe.com', target: '_blank', rel: 'noreferrer', class: 'pv-small-button' }, 'Stripe Dashboard'),
            h('a', { href: 'https://www.paypal.com/myaccount/autopay/', target: '_blank', rel: 'noreferrer', class: 'pv-small-button' }, 'PayPal Pre-Approved'),
          ]),
        ]),
      ]),
      h('article', { class: 'pv-panel' }, [
        h('h2', 'Premium Features'),
        h('div', { class: 'pv-badge-grid' }, [
          h('span', [h(PvIcon, { name: 'shield' }), h('small', 'Vendor Reviews')]),
          h('span', [h(PvIcon, { name: 'flask' }), h('small', 'Lab Results')]),
          h('span', [h(PvIcon, { name: 'message' }), h('small', 'Messaging')]),
          h('span', [h(PvIcon, { name: 'users' }), h('small', 'Member Profiles')]),
          h('span', [h(PvIcon, { name: 'discussions' }), h('small', 'Unlimited Discussions')]),
          h('span', [h(PvIcon, { name: 'upload' }), h('small', 'File Uploads')]),
        ]),
      ]),
    ])
  }
  if (pageName === 'settingsNotifications') {
    return settingsOptionPanel('Notification Settings', [
      ['Email notifications', 'email_notifications'],
      ['Push notifications', 'push_notifications'],
      ['Sound effects', 'sound_enabled'],
    ])
  }
  if (pageName === 'settingsPreferences') {
    return h('div', { class: 'pv-stack' }, [
      status,
      h('article', { class: 'pv-panel pv-form-card' }, [
        h('h2', 'Display'),
        h('div', { class: 'pv-two-col' }, [
          settingsSelect('Theme', userSettings.value.theme, value => { void setUserSetting('theme', value as UserSettingsPayload['theme']) }, [['dark', 'Dark'], ['light', 'Light'], ['system', 'System']]),
          settingsSelect('Language', userSettings.value.language, value => { void setUserSetting('language', value) }, [['en', 'English'], ['en-GB', 'English (UK)'], ['en-US', 'English (US)']]),
        ]),
      ]),
      settingsOptionPanel('Browsing', [
        ['Compact discussion lists', 'compact_discussions'],
        ['Show online members', 'show_online_members'],
        ['Remember content filters', 'remember_content_filters'],
      ], false),
    ])
  }
  if (pageName === 'settingsBlocked') {
    return h('div', { class: 'pv-stack' }, [
      status,
      h('article', { class: 'pv-panel' }, [
        h('header', { class: 'pv-panel-header' }, [
          h('div', [h('h2', 'Blocked Users'), h('p', { class: 'pv-muted' }, 'Blocked members cannot start new message threads with you.')]),
          h('span', { class: 'pv-tag' }, `${blockedUsers.value.length} blocked`),
        ]),
        h('div', { class: 'pv-mini-list' }, blockedUsers.value.length > 0
          ? blockedUsers.value.map(member => h('span', { class: 'pv-mini-row' }, [
            h('span', { class: ['pv-avatar', member.color] }, member.initial),
            h('span', [h('strong', member.name), h('small', member.username)]),
            h('button', { class: 'pv-small-button', disabled: blockingUserId.value === member.id, onClick: () => void unblockMember(member) }, blockingUserId.value === member.id ? 'Unblocking...' : 'Unblock'),
          ]))
          : [h('p', { class: 'pv-muted' }, blockedUsersLoaded.value ? 'No blocked users.' : 'Loading blocked users...')]),
      ]),
      h('article', { class: 'pv-panel' }, [
        h('h2', 'Block by Username'),
        h('div', { class: 'pv-two-col pv-settings-inline-actions' }, [
          settingsInput('Username', blockUsername.value, value => { blockUsername.value = value }),
          h('button', { class: 'pv-primary-button', disabled: blockingUsername.value || !blockUsername.value.trim(), onClick: blockMemberByUsername }, blockingUsername.value ? 'Blocking...' : 'Block Username'),
        ]),
      ]),
      h('article', { class: 'pv-panel' }, [
        h('h2', 'Find a Member'),
        settingsInput('Search Members', blockUserSearch.value, value => { blockUserSearch.value = value }),
        h('div', { class: 'pv-mini-list' }, blockableMembers.value.length > 0
          ? blockableMembers.value.map(member => h('span', { class: 'pv-mini-row' }, [
            h('span', { class: ['pv-avatar', member.color] }, member.initial),
            h('span', [h('strong', member.name), h('small', member.role || member.group || 'Member')]),
            h('button', { class: 'pv-small-button', disabled: blockingUserId.value === member.id, onClick: () => void blockMember(member) }, blockingUserId.value === member.id ? 'Blocking...' : 'Block'),
          ]))
          : [h('p', { class: 'pv-muted' }, membersLoaded.value ? 'No matching members available to block.' : 'Loading member directory...')]),
      ]),
    ])
  }
  if (pageName === 'settingsSessions') {
    return h('article', { class: 'pv-panel' }, [h('h2', 'Sessions'), status, sessionList(20, true)])
  }
   if (pageName === 'settingsDanger') {
     return h('div', { class: 'pv-stack' }, [
       status,
       h('article', { class: 'pv-alert pv-alert--danger' }, [
        h(PvIcon, { name: 'shield' }),
        h('div', [
          h('h2', 'Sign Out Everywhere'),
          h('p', 'This revokes every active API/session token for your account, including this browser.'),
          h('button', { class: 'pv-small-button', disabled: signingOutEverywhere.value, onClick: signOutEverywhere }, signingOutEverywhere.value ? 'Signing out...' : 'Sign Out Everywhere'),
        ]),
      ]),
    ])
  }
  return h('article', { class: 'pv-panel pv-form-card' }, [
    h('h2', 'Profile Information'),
    status,
    h('div', { class: 'pv-profile-form-head' }, [accountAvatarNode('pv-avatar--xl'), h('div', { class: 'pv-input-group' }, [
      settingsInput('Username', accountForm.value.username, value => { accountForm.value.username = value }),
    ])]),
    h('label', ['Email Address', h('input', { value: accountForm.value.email, disabled: true })]),
    settingsTextarea('Bio', accountForm.value.bio, value => { accountForm.value.bio = value }),
    h('div', { class: 'pv-two-col' }, [
      settingsInput('Timezone', accountForm.value.timezone, value => { accountForm.value.timezone = value }),
      settingsInput('Website', accountForm.value.website_url, value => { accountForm.value.website_url = value }),
    ]),
    h('button', { class: 'pv-primary-button', disabled: savingSettings.value, onClick: saveAccountProfile }, savingSettings.value ? 'Saving...' : 'Save Changes'),
    h('hr'),
    h('h2', 'Profile Photo'),
    h('div', { class: 'pv-avatar-upload-row' }, [
      accountAvatarNode('pv-avatar--xl'),
      h('div', [
        h('strong', accountName()),
        h('p', { class: 'pv-muted' }, 'Upload a square image up to 2 MB.'),
      ]),
    ]),
    h('input', { ref: (el) => { avatarFileInput.value = el as HTMLInputElement | null }, type: 'file', accept: 'image/*', style: 'display:none', onChange: uploadProfileAvatar }),
    h('button', { class: 'pv-small-button', disabled: uploadingAvatar.value, onClick: () => avatarFileInput.value?.click() }, [h(PvIcon, { name: 'upload' }), uploadingAvatar.value ? ' Uploading...' : ' Change Photo']),
  ])
}

function settingsInput(label: string, value: string, onValue: (value: string) => void, type = 'text', disabled = false) {
  return h('label', [label, h('input', { type, placeholder: label, value, disabled, onInput: (event: Event) => onValue((event.target as HTMLInputElement).value) })])
}

function settingsSelect(label: string, value: string, onValue: (value: string) => void, options: Array<[string, string]>) {
  return h('label', { class: 'pv-settings-select' }, [
    label,
    h('select', { value, onChange: (event: Event) => onValue((event.target as HTMLSelectElement).value) }, options.map(option => h('option', { value: option[0] }, option[1]))),
  ])
}

function twoFactorSecurityPanel() {
  const enabled = Boolean(twoFactorStatus.value?.enabled || authUserValue('two_factor_enabled'))
  const qrSource = twoFactorSetup.value?.qr_code ? `data:image/svg+xml;base64,${twoFactorSetup.value.qr_code}` : ''
  const action = twoFactorSetup.value
    ? h('div', { class: 'pv-input-group' }, [
      qrSource ? h('img', { class: 'pv-qr-code', src: qrSource, alt: 'Two-factor setup QR code' }) : null,
      h('p', { class: 'pv-muted' }, `Secret: ${twoFactorSetup.value.secret}`),
      settingsInput('Authenticator Code', twoFactorCode.value, value => { twoFactorCode.value = value }, 'text'),
      h('div', { class: 'pv-action-row' }, [
        h('button', { class: 'pv-primary-button', disabled: savingTwoFactor.value, onClick: confirmTwoFactorSetup }, savingTwoFactor.value ? 'Confirming...' : 'Confirm 2FA'),
        h('button', { class: 'pv-small-button', disabled: savingTwoFactor.value, onClick: cancelTwoFactorSetup }, 'Cancel'),
      ]),
    ])
    : enabled
      ? h('div', { class: 'pv-input-group' }, [
        h('p', { class: 'pv-muted' }, 'Two-factor authentication is active for this account. Enter your current password to disable it.'),
        settingsInput('Current Password', twoFactorPassword.value, value => { twoFactorPassword.value = value }, 'password'),
        h('button', { class: 'pv-small-button', disabled: savingTwoFactor.value, onClick: disableTwoFactor }, savingTwoFactor.value ? 'Disabling...' : 'Disable 2FA'),
      ])
      : h('button', { class: 'pv-primary-button', disabled: savingTwoFactor.value || loadingTwoFactor.value, onClick: startTwoFactorSetup }, savingTwoFactor.value ? 'Starting...' : 'Set Up 2FA')

  return h('article', { class: 'pv-panel pv-settings-card' }, [
    h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'shield' })]),
    h('div', [
      h('h2', 'Two-Factor Authentication (2FA)'),
      h('p', 'Add an extra layer of security to your account.'),
      h('span', { class: enabled ? 'pv-success-pill' : 'pv-tag' }, enabled ? 'Enabled' : 'Disabled'),
      twoFactorRecoveryCodes.value.length > 0 ? h('div', { class: 'pv-recovery-code-list' }, [
        h('strong', 'Recovery Codes'),
        h('p', { class: 'pv-muted' }, 'Save these now. They will not be shown again.'),
        h('code', twoFactorRecoveryCodes.value.join('\n')),
      ]) : null,
      action,
    ]),
  ])
}

function settingsTextarea(label: string, value: string, onValue: (value: string) => void) {
  return h('label', [label, h('textarea', { value, onInput: (event: Event) => onValue((event.target as HTMLTextAreaElement).value) })])
}

function settingsSwitchCard(title: string, text: string, icon: string, key: BooleanUserSettingKey) {
  return h('article', { class: 'pv-panel pv-settings-card' }, [
    h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: icon })]),
    h('div', [h('h2', title), h('p', text)]),
    h('button', { class: 'pv-toggle-button', disabled: savingSettings.value, onClick: () => toggleUserSetting(key) }, [
      h('span', { class: userSettings.value[key] ? 'pv-switch active' : 'pv-switch' }),
    ]),
  ])
}

function settingsRadioGroup(key: 'profile_visibility' | 'direct_messages', options: Array<[UserSettingsPayload[typeof key], string]>) {
  return h('div', { class: 'pv-radio-list' }, options.map(([value, label]) => h('label', [
    h('input', { type: 'radio', checked: userSettings.value[key] === value, disabled: savingSettings.value, onChange: () => { void setUserSetting(key, value) } }),
    label,
  ])))
}

function sessionList(limit = 6, allowRevoke = false) {
  if (userSessions.value.length === 0) {
    return h('p', { class: 'pv-muted' }, 'No active sessions or tokens were found.')
  }

  return h('div', { class: 'pv-mini-list' }, userSessions.value.slice(0, limit).map(session => {
    const title = session.name ?? session.userAgent ?? (session.kind === 'browser' ? 'Browser session' : 'Auth token')
    const meta = [
      session.kind === 'browser' ? 'Browser' : 'Token',
      session.ipAddress || '',
      session.lastActivity ? `Last active ${formatDate(session.lastActivity)}` : '',
      session.expiresAt ? `Expires ${formatDate(session.expiresAt)}` : '',
    ].filter(Boolean).join(' · ')

    return h('span', { class: 'pv-mini-row' }, [
      h(PvIcon, { name: session.kind === 'browser' ? 'lock' : 'document' }),
      h('span', [h('strong', title), h('small', meta || 'Active')]),
      session.isCurrent ? h('em', { class: 'pv-tag trusted' }, 'Current') : null,
      allowRevoke ? h('button', {
        class: 'pv-small-button',
        disabled: revokingSessionId.value === session.id,
        onClick: () => void revokeUserSession(session),
      }, revokingSessionId.value === session.id ? 'Revoking...' : 'Revoke') : null,
    ])
  }))
}

function settingsSummary() {
  return h('article', { class: 'pv-panel' }, [h('h2', 'Account Summary'), h('div', { class: 'pv-summary-user' }, [accountAvatarNode('pv-avatar--xl'), h('div', [h('strong', accountName()), h('span', { class: 'pv-tag' }, accountRole())])]), h('dl', { class: 'pv-data-list' }, [['Joined', authUserDate('created_at') || 'Not signed in'], ['Last Active', authUserDate('last_active') || ''], ['Email', accountEmail()], ['Two-Factor Authentication', authUserValue('two_factor_enabled') ? 'Enabled' : 'Disabled']].map(row => h('div', [h('dt', row[0]), h('dd', row[1])])))])
}

function quickActions() {
  return h('article', { class: 'pv-panel' }, [
    h('h2', 'Quick Actions'),
    h('div', { class: 'pv-filter-list' }, [
      h(RouterLink, { to: '/settings/security' }, () => [h(PvIcon, { name: 'lock' }), ' Change Password', h(PvIcon, { name: 'chevron' })]),
      h(RouterLink, { to: '/settings/security' }, () => [h(PvIcon, { name: 'shield' }), ' Two-Factor Authentication', h(PvIcon, { name: 'chevron' })]),
      h('button', { disabled: exportingAccountData.value, onClick: exportAccountData }, [h(PvIcon, { name: 'download' }), exportingAccountData.value ? ' Exporting...' : ' Download My Data', h(PvIcon, { name: 'chevron' })]),
      h(RouterLink, { to: '/settings/danger-zone' }, () => [h(PvIcon, { name: 'settings' }), ' Sign Out Everywhere', h(PvIcon, { name: 'chevron' })]),
    ]),
  ])
}

function settingsOptionPanel(title: string, options: Array<[string, BooleanUserSettingKey]>, showStatus = true) {
  return h('article', { class: 'pv-panel' }, [
    h('h2', title),
    showStatus && settingsStatusMessage.value ? h('p', { class: 'pv-settings-status' }, settingsStatusMessage.value) : null,
    h('div', { class: 'pv-stack' }, options.map(option => h('div', { class: 'pv-toggle-row' }, [
      h('span', [h('strong', option[0]), h('small', userSettings.value[option[1]] ? 'Enabled' : 'Disabled')]),
      h('button', { class: 'pv-toggle-button', disabled: savingSettings.value, onClick: () => toggleUserSetting(option[1]) }, [
        h('span', { class: userSettings.value[option[1]] ? 'pv-switch active' : 'pv-switch' }),
      ]),
    ]))),
  ])
}

function tipsPanel(title: string, tips: string[]) {
  return h('article', { class: 'pv-panel' }, [
    h('h2', title),
    h('div', { class: 'pv-mini-list' }, tips.map(tip => h('span', { class: 'pv-mini-row' }, [
      h(PvIcon, { name: 'shield' }),
      h('span', [
        h('strong', tip),
        h('small', 'Simple steps to keep your account safe.'),
      ]),
    ]))),
  ])
}

function authUserValue(key: string): any {
  const user = authStore.user as Record<string, any> | null
  return user?.[key] ?? null
}

function accountName(): string {
  return String(authUserValue('username') || authUserValue('name') || 'Not signed in')
}

function accountEmail(): string {
  return String(authUserValue('email') || 'Not signed in')
}

function accountInitial(): string {
  return accountName().charAt(0).toUpperCase()
}

function accountAvatar(): string {
  return assetUrl(String(authUserValue('avatar') || authUserValue('profile_photo_path') || authUserValue('profile_picture') || ''))
}

function accountAvatarNode(sizeClass = '') {
  const avatar = accountAvatar()

  return h('span', { class: ['pv-avatar', sizeClass, 'purple'] }, avatar
    ? [h('img', { src: avatar, alt: accountName(), class: 'pv-avatar-img' })]
    : accountInitial())
}

function accountRole(): string {
  const roles = authUserValue('roles')
  return Array.isArray(roles) && roles[0] ? String(roles[0]) : ''
}

function authUserDate(key: string): string {
  const value = authUserValue(key)
  if (!value) {
    return ''
  }

  return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
}
</script>
