<template>
<section class="pv-page">
    <div v-if="showUpgradePrompt" class="pv-upgrade-overlay">
      <div class="pv-upgrade-card">
        <span class="pv-upgrade-icon"><PvIcon name="users" /></span>
        <h2>Premium Feature</h2>
        <p>Upgrade to Premium to browse detailed member profiles with activity history and reputation scores.</p>
        <router-link to="/pricing" class="pv-primary-button">View Pricing</router-link>
      </div>
    </div>
    <div v-else class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header"><div><h1>Members</h1><p>Find contributors, moderators, reviewers, and active community voices.</p></div><router-link to="/settings" class="pv-small-button"><PvIcon name="user" /> Edit Profile</router-link></header>
        <div class="pv-metrics pv-metrics--member">
          <span><PvIcon name="users" /><strong>{{ memberStats.total }}</strong><small>Total Members</small></span>
          <span><PvIcon name="clock" /><strong>{{ memberStats.online }}</strong><small>Online Now</small></span>
          <span><PvIcon name="eye" /><strong>{{ memberStats.guests }}</strong><small>Guests</small></span>
          <span><PvIcon name="clock" /><strong>{{ memberStats.visits_today }}</strong><small>Visits Today</small></span>
          <span><PvIcon name="shield" /><strong>{{ apiMembers.filter((member: any) => member.isModerator).length }}</strong><small>Moderators</small></span>
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
          <div v-if="membersLoaded && members.length === 0" class="pv-empty-inline"><PvIcon name="users" /><strong>No members found</strong><p>Try a different search term or clear the current directory filter.</p></div>
          <div class="pv-member-grid">
            <router-link v-for="member in members" :key="member.slug" :to="member.href" class="pv-member-card">
              <header class="pv-member-card-banner">
                <span v-if="member.avatarUrl" class="pv-avatar pv-avatar--large pv-avatar--presence" :class="member.color"><img :src="assetUrl(member.avatarUrl)" :alt="member.name" class="pv-avatar-img"><span v-if="member.isOnline" class="online-indicator"></span></span>
                <span v-else class="pv-avatar pv-avatar--large pv-avatar--presence" :class="member.color">{{ member.initial }}<span v-if="member.isOnline" class="online-indicator"></span></span>
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
                <div><dt>Posts</dt><dd>{{ formatCount(member.stats.posts) }}</dd></div>
                <div><dt>Reviews</dt><dd>{{ formatCount(member.stats.reviews) }}</dd></div>
                <div><dt>Labs</dt><dd>{{ formatCount(member.stats.lab_reports) }}</dd></div>
              </dl>
              <footer><small>{{ member.lastActive || 'No recent activity' }}</small><PvIcon name="chevron" /></footer>
            </router-link>
          </div>
          <PaginationBlock :meta="memberPagination" @page="setMemberPage" />
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Top Contributors</h2><div class="pv-mini-list"><router-link v-for="member in topContributors" :key="member.slug" :to="member.href" class="pv-mini-row"><span class="pv-avatar" :class="member.color">{{ member.initial }}</span><span><strong>{{ member.name }}</strong><small>{{ formatCount(memberEngagementScore(member)) }} contributions</small></span><PvIcon name="chevron" /></router-link></div></article>
        <article class="pv-panel pv-online-panel"><header class="pv-panel-header"><h2>Online Now</h2><span class="pv-count">{{ onlineActivityTotal }}</span></header><div class="pv-online-summary"><span><PvIcon name="users" /><strong>{{ memberStats.online }}</strong><small>Members</small></span><span><PvIcon name="eye" /><strong>{{ memberStats.guests }}</strong><small>Guests</small></span></div><div class="pv-avatar-stack pv-avatar-stack--wrap"><router-link v-for="member in onlineMembers" :key="member.slug" :to="member.href" class="pv-avatar pv-avatar--online" :class="member.color" :title="member.name"><img v-if="member.avatarUrl" :src="assetUrl(member.avatarUrl)" :alt="member.name"><span v-else>{{ member.initial }}</span></router-link><span v-if="onlineMemberOverflow > 0" class="pv-more">+{{ onlineMemberOverflow }}</span><span v-if="memberStats.online === 0" class="pv-muted pv-online-empty">Nobody online right now.</span></div><div v-if="onlineGuestRows.length" class="pv-viewing-list"><div v-for="activity in onlineGuestRows" :key="`${activity.path}-${activity.label}`" class="pv-viewing-row"><PvIcon name="eye" /><span><strong>{{ activity.label }}</strong><small>{{ guestVisitorLabel(activity.visitors) }}</small></span></div></div></article>
        <article class="pv-panel"><h2>Member Tips</h2><ul class="pv-check-list"><li>Keep your profile bio current</li><li>Use reports for moderation issues</li><li>Message members from their profile</li><li>Reputation grows from useful posts</li></ul></article>
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
