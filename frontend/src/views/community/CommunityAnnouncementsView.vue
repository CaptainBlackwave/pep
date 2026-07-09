<template>
<section class="pv-page">
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
