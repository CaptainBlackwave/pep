<template>
<section class="pv-page">
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
        <div class="pv-discussion-toolbar">
          <form class="pv-inline-search" @submit.prevent="applyDiscussionSearch">
            <label class="pv-input-search">
              <input v-model="discussionSearch" type="search" placeholder="Search discussions, authors, replies...">
              <PvIcon name="search" />
            </label>
            <button class="pv-small-button" type="submit">Search</button>
            <button v-if="discussionHasActiveFilters" class="pv-small-button" type="button" @click="clearDiscussionFilters">Clear</button>
          </form>
          <button class="pv-small-button" type="button" @click="cycleDiscussionSort">{{ discussionSortLabel }} <PvIcon name="chevron" /></button>
        </div>
        <article class="pv-panel">
          <p v-if="discussionStatusMessage" class="pv-alert pv-alert--compact">{{ discussionStatusMessage }}</p>
          <div class="pv-topic-list">
            <div v-if="discussionsLoaded && discussions.length === 0" class="pv-empty-inline">
              <PvIcon name="message" />
              <strong>No discussions found</strong>
              <p>{{ activeCategory || discussionSearch ? 'Try clearing the current filters.' : 'Start the first discussion in this space.' }}</p>
              <button v-if="activeCategory || discussionSearch" class="pv-small-button" type="button" @click="clearDiscussionFilters">Clear Filters</button>
            </div>
            <div v-if="!discussionsLoaded" class="pv-skeleton-stack">
              <div v-for="i in 5" :key="i" class="pv-skeleton-card"><div class="pv-skeleton pv-skeleton--avatar"></div><div class="pv-skeleton-body"><div class="pv-skeleton pv-skeleton--line w-75"></div><div class="pv-skeleton pv-skeleton--line w-50"></div><div class="pv-skeleton pv-skeleton--line w-25"></div></div></div>
            </div>
            <article
              v-for="(topic, index) in discussions"
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
                 <button class="topic-icon-action" :class="{ active: isSavedDiscussion(topic) }" :aria-label="isSavedDiscussion(topic) ? 'Remove saved discussion' : 'Save discussion'" :title="isSavedDiscussion(topic) ? 'Saved' : 'Save'" @click.stop="toggleDiscussionSave(topic)"><PvIcon name="bookmark" /></button>
                 <button class="topic-icon-action" :class="{ active: isFollowingDiscussion(topic) }" :aria-label="isFollowingDiscussion(topic) ? 'Unfollow discussion' : 'Follow discussion'" :title="isFollowingDiscussion(topic) ? 'Following' : 'Follow'" @click.stop="toggleDiscussionFollow(topic)"><PvIcon name="bell" /></button>
                  <button class="dots" @click.stop="toggleTopicMenu(topic.id ?? index)">⋮</button>
                  <div v-if="activeTopicMenu === (topic.id ?? index)" class="dots-dropdown" @click.stop>
                   <button @click="toggleDiscussionSave(topic); activeTopicMenu = null">{{ isSavedDiscussion(topic) ? 'Unsave' : 'Save' }}</button>
                   <button @click="toggleDiscussionFollow(topic); activeTopicMenu = null">{{ isFollowingDiscussion(topic) ? 'Unfollow' : 'Follow' }}</button>
                   <button @click="shareDiscussion(topic); activeTopicMenu = null">Share</button>
                   <button @click="goToMemberProfile(topic.authorUsername); activeTopicMenu = null">View Profile</button>
                   <button @click="reportDiscussion(topic); activeTopicMenu = null">Report</button>
                   <button v-if="authStore.user?.id === topic.authorId" @click="startEditDiscussionFromList(topic); activeTopicMenu = null">Edit</button>
                   <button v-if="authStore.user?.id === topic.authorId" @click="deleteDiscussionFromList(topic); activeTopicMenu = null">Delete</button>
                    <template v-if="hasAnyRole(['admin', 'moderator'])">
                      <button @click="moderateDiscussion(topic, 'hide'); activeTopicMenu = null">Hide</button>
                      <button @click="moderateDiscussion(topic, 'pin'); activeTopicMenu = null">{{ topic.isPinned ? 'Unpin' : 'Pin' }}</button>
                      <button @click="moderateDiscussion(topic, 'lock'); activeTopicMenu = null">{{ topic.isLocked ? 'Unlock' : 'Lock' }}</button>
                     </template>
                  </div>
                </div>
               <aside class="author-panel">
                <router-link class="avatar-wrap pv-author-link" :to="memberHref(topic.authorUsername)" :aria-label="`View ${topic.author}'s profile`" @click.stop>
                  <span v-if="topic.avatarUrl" class="avatar topic-avatar" :class="topic.color"><img :src="assetUrl(topic.avatarUrl)" :alt="topic.author"></span>
                  <span v-else class="avatar topic-avatar" :class="topic.color">{{ topic.initial }}</span>
                  <span v-if="topic.authorOnline" class="online-indicator" aria-label="Online"></span>
                </router-link>
                <div class="author-meta">
                  <router-link class="pv-author-name-link" :to="memberHref(topic.authorUsername)" @click.stop><h4>{{ topic.authorUsername }}</h4></router-link>
                  <span v-if="topic.authorBadge" class="author-badge">{{ topic.authorBadge }}</span>
                  <span class="author-presence" :class="{ online: topic.authorOnline }"><span></span>{{ topic.authorOnline ? 'Online' : 'Away' }}</span>
                </div>
                <div class="author-posts"><PvIcon name="message" /> {{ topic.authorPostCount }} posts</div>
              </aside>
              <main class="topic-body">
                <span v-if="topic.tag" class="topic-type"><PvIcon name="tag" /> {{ topic.tag }}</span>
                <span v-if="topic.isPinned" class="topic-type topic-type--pinned"><PvIcon name="pin" /> Pinned</span>
                <span v-if="topic.isLocked" class="topic-type topic-type--locked"><PvIcon name="lock" /> Locked</span>
                <h2>{{ topic.title }}</h2>
                <p class="topic-excerpt">{{ topic.excerpt }}</p>
                <div class="divider"></div>
                <div class="topic-bottom">
                  <div class="stats">
                    <div class="stat">
                      <PvIcon name="message" />
                      <strong>{{ topic.replies }}</strong>
                      <small>Replies</small>
                    </div>
                    <div class="stat">
                      <PvIcon name="eye" />
                      <strong>{{ topic.views }}</strong>
                      <small>Views</small>
                    </div>
                    <div class="stat">
                      <PvIcon name="vote-up" />
                      <strong>{{ topic.voteScore }}</strong>
                      <small>Likes</small>
                    </div>
                    <div class="stat stat--clickable" @click.stop="shareDiscussion(topic)">
                      <PvIcon name="share" />
                      <small>Share</small>
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
