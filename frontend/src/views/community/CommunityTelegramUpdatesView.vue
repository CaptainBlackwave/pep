<template>
<section class="pv-page">
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
