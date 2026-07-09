<template>
<section class="pv-page">
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
