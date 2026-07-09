<template>
<section class="pv-page pv-notification-detail-page">
    <div class="pv-content-grid">
      <main v-if="primaryNotification" class="pv-stack pv-notification-detail-main">
        <router-link to="/notifications" class="pv-purple-link">Back to notifications</router-link>
        <header class="pv-page-header pv-notification-detail-header">
          <div>
            <h1>Notification</h1>
            <p>Read the update and open the related content when needed.</p>
          </div>
          <div class="pv-action-row pv-notification-detail-actions">
            <button v-if="authStore.isAuthenticated && primaryNotification.unread" class="pv-small-button" :disabled="markingNotificationsRead" @click="markNotificationRead(primaryNotification.slug)"><PvIcon name="mail" /> Mark as read</button>
            <router-link v-if="previousNotification" class="pv-icon-button pv-icon-button--static" :to="previousNotification.detailHref" aria-label="Previous notification"><PvIcon name="chevron" /></router-link>
            <router-link v-if="nextNotification" class="pv-icon-button pv-icon-button--static" :to="nextNotification.detailHref" aria-label="Next notification"><PvIcon name="chevron" /></router-link>
          </div>
        </header>
        <article class="pv-notification-hero pv-notification-detail-hero">
          <span class="pv-large-icon" :class="primaryNotification.tone"><PvIcon :name="primaryNotification.icon" /></span>
          <div class="pv-notification-hero-copy">
            <div class="pv-notification-meta">
              <span class="pv-tag" :class="primaryNotification.tone">{{ primaryNotification.category }}</span>
              <strong>{{ primaryNotification.author }}</strong>
              <time>{{ primaryNotification.time }}</time>
            </div>
            <h2 class="pv-notification-title">{{ primaryNotification.title }}</h2>
            <p class="pv-notification-text">{{ primaryNotification.text }}</p>
            <router-link :to="primaryNotification.href" class="pv-primary-button">Open {{ primaryNotification.category }} <PvIcon name="share" /></router-link>
          </div>
        </article>
        <article class="pv-panel pv-prose pv-notification-body-card">
          <h2>Full Message</h2>
          <div class="pv-notification-body-copy">
            <p v-for="paragraph in primaryNotification.bodyParagraphs" :key="paragraph">{{ paragraph }}</p>
          </div>
          <hr>
          <h2>What you can do</h2>
          <router-link :to="primaryNotification.href" class="pv-action-card pv-notification-action-card"><PvIcon :name="primaryNotification.icon" /><span><strong>Open related content</strong><small>View the full source item for this notification.</small></span><PvIcon name="chevron" /></router-link>
          <router-link to="/discussions" class="pv-action-card pv-notification-action-card"><PvIcon name="message" /><span><strong>Join the discussion</strong><small>Discuss updates with the community.</small></span><PvIcon name="chevron" /></router-link>
        </article>
      </main>
      <main v-else class="pv-stack"><router-link to="/notifications" class="pv-purple-link">Back</router-link><article class="pv-panel"><h1>Notification not found</h1><p class="pv-muted">There are no live notifications to show yet.</p></article></main>
      <aside class="pv-stack"><article class="pv-panel"><h2>Notification Details</h2><dl class="pv-data-list"><div><dt>Type</dt><dd>{{ primaryNotification?.category ?? '' }}</dd></div><div><dt>From</dt><dd>{{ primaryNotification?.author ?? '' }}</dd></div><div><dt>Received</dt><dd>{{ primaryNotification?.time ?? '' }}</dd></div><div><dt>Status</dt><dd><span class="pv-dot purple"></span> {{ primaryNotification?.unread ? 'Unread' : 'Read' }}</dd></div></dl><hr><h2>Related Links</h2><div class="pv-filter-list"><router-link to="/lab-results"><PvIcon name="flask" /> Lab Results <PvIcon name="chevron" /></router-link><router-link to="/announcements"><PvIcon name="megaphone" /> Announcements <PvIcon name="chevron" /></router-link><router-link to="/discussions"><PvIcon name="message" /> Community Discussions <PvIcon name="chevron" /></router-link></div></article></aside>
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
