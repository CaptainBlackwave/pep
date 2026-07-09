<template>
<section class="pv-page pv-dashboard">
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
          <div v-if="!authStore.isAuthenticated" style="margin-top:16px">
            <router-link to="/register" class="pv-primary-button" style="margin-right:8px">Join the Community</router-link>
            <router-link to="/login" class="pv-small-button">Sign In</router-link>
          </div>
        </article>

        <article class="pv-panel pv-home-links">
          <header class="pv-panel-header">
            <h2><PvIcon name="home" /> Explore</h2>
          </header>
          <div class="pv-home-link-grid">
            <router-link to="/discussions" class="pv-home-link">
              <span class="pv-icon-tile"><PvIcon name="discussions" /></span>
              <span><strong>Discussions</strong><small>Open conversations</small></span>
              <PvIcon name="chevron" />
            </router-link>
            <router-link to="/research-library" class="pv-home-link">
              <span class="pv-icon-tile"><PvIcon name="library" /></span>
              <span><strong>Research Library</strong><small>Articles and references</small></span>
              <PvIcon name="chevron" />
            </router-link>
            <router-link to="/guides" class="pv-home-link">
              <span class="pv-icon-tile"><PvIcon name="question" /></span>
              <span><strong>Guides &amp; FAQ</strong><small>Safety-first reading</small></span>
              <PvIcon name="chevron" />
            </router-link>
            <router-link :to="authStore.isAuthenticated ? '/members' : '/pricing'" class="pv-home-link">
              <span class="pv-icon-tile"><PvIcon name="users" /></span>
              <span><strong>{{ authStore.isAuthenticated ? 'Members' : 'Premium' }}</strong><small>{{ authStore.isAuthenticated ? 'Community directory' : 'Unlock protected areas' }}</small></span>
              <PvIcon name="chevron" />
            </router-link>
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
        <article class="pv-panel pv-online-panel">
          <header class="pv-panel-header"><h2><PvIcon name="users" /> Online Now</h2><span class="pv-count">{{ onlineActivityTotal }}</span></header>
          <div class="pv-online-summary">
            <span><PvIcon name="users" /><strong>{{ memberStats.online }}</strong><small>Members</small></span>
            <span><PvIcon name="eye" /><strong>{{ memberStats.guests }}</strong><small>Guests</small></span>
            <span><PvIcon name="clock" /><strong>{{ memberStats.visits_today }}</strong><small>Today</small></span>
          </div>
          <div class="pv-avatar-stack pv-avatar-stack--wrap" aria-label="Online members">
            <router-link v-for="member in onlineMembers" :key="member.slug" :to="member.href" class="pv-avatar pv-avatar--online" :class="member.color" :title="member.name">
              <img v-if="member.avatarUrl" :src="assetUrl(member.avatarUrl)" :alt="member.name">
              <span v-else>{{ member.initial }}</span>
            </router-link>
            <span v-if="onlineMemberOverflow > 0" class="pv-more">+{{ onlineMemberOverflow }}</span>
            <span v-if="memberStats.online === 0" class="pv-muted pv-online-empty">No members online.</span>
          </div>
          <div v-if="onlineGuestRows.length" class="pv-viewing-list">
            <div v-for="activity in onlineGuestRows" :key="`${activity.path}-${activity.label}`" class="pv-viewing-row">
              <PvIcon name="eye" />
              <span><strong>{{ activity.label }}</strong><small>{{ guestVisitorLabel(activity.visitors) }}</small></span>
            </div>
          </div>
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
