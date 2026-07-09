<template>
<section class="pv-page pv-notifications-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header pv-notifications-header">
          <div><h1>Notifications</h1><p>Review replies, mentions, messages, vendor updates, and system notices.</p></div>
          <div class="pv-action-row pv-notifications-actions">
            <button v-if="authStore.isAuthenticated" class="pv-small-button pv-notification-read-button" :disabled="markingNotificationsRead" @click="markAllNotificationsRead"><PvIcon name="check" /> Mark all read</button>
            <router-link to="/settings/notifications" class="pv-small-button pv-notification-settings-button" aria-label="Notification settings"><PvIcon name="settings" /> Settings</router-link>
          </div>
        </header>
        <div class="pv-notification-quick-filter" role="group" aria-label="Notification filter">
          <button type="button" :class="{ active: activeNotificationFilter === 'all' }" @click="setNotificationFilter('all')">
            <PvIcon name="bell" /> All
          </button>
          <button type="button" :class="{ active: activeNotificationFilter === 'unread' }" @click="setNotificationFilter('unread')">
            <PvIcon name="clock" /> Unread <span v-if="notificationCounts.unread">{{ notificationCounts.unread }}</span>
          </button>
        </div>
        <article class="pv-panel pv-notification-list">
          <p v-if="notificationStatusMessage" class="pv-muted">{{ notificationStatusMessage }}</p>
          <div v-if="notificationsLoaded && notifications.length === 0" class="pv-empty-inline"><PvIcon name="bell" /><strong>No notifications yet</strong><p>Replies, mentions, messages, and important account updates will appear here.</p></div>
          <router-link v-for="item in notifications" :key="item.slug" :to="item.detailHref" class="pv-notification-row" :class="{ unread: item.unread }" @click="markNotificationRead(item.slug)">
            <span class="pv-large-icon" :class="item.tone"><PvIcon :name="item.icon" /></span>
            <span class="pv-notification-copy">
              <span class="pv-notification-meta">
                <em class="pv-tag" :class="item.tone">{{ item.category }}</em>
                <time class="pv-notification-time pv-notification-time--inline">{{ item.time }}</time>
              </span>
              <strong class="pv-notification-title">{{ item.title }}</strong>
              <small class="pv-notification-text">{{ item.text }}</small>
            </span>
            <time class="pv-notification-time pv-notification-time--rail">{{ item.time }}</time>
            <b v-if="item.unread" class="pv-notification-unread-dot"></b>
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
