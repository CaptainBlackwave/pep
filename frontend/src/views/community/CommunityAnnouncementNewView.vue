<template>
<section class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack"><nav class="pv-breadcrumbs">Announcements <PvIcon name="chevron" /> Admin</nav><header class="pv-page-header"><div><h1>Create New Announcement</h1><p>Announcements are created and published through the existing admin panel.</p></div></header><article class="pv-form-card"><h2>Use the admin panel</h2><p>The public website reads announcements from the database. Staff can create drafts, publish updates, pin important notices, and hide old posts in the admin area.</p><a href="/admin/community-announcements" class="pv-primary-button"><PvIcon name="send" /> Open Announcement Manager</a></article></main>
      <aside class="pv-stack"><article class="pv-panel"><h2>Announcement Flow</h2><div class="pv-mini-list"><span class="pv-mini-row"><PvIcon name="document" /><span><strong>Create or edit</strong><small>Use the admin form to write the content.</small></span></span><span class="pv-mini-row"><PvIcon name="shield" /><span><strong>Publish safely</strong><small>Only published rows appear on the public site.</small></span></span><span class="pv-mini-row"><PvIcon name="star" /><span><strong>Pin key updates</strong><small>Pinned announcements stay at the top.</small></span></span></div></article></aside>
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
