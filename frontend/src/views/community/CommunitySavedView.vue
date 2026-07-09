<template>
<section class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>Saved</h1><p>Bookmarked discussions, products, and reading material.</p></div>
          <router-link to="/discussions" class="pv-small-button"><PvIcon name="plus" /> Explore Discussions</router-link>
        </header>

        <div class="pv-saved-summary">
          <span><PvIcon name="discussions" /><strong>{{ savedDiscussionSlugs.length }}</strong><small>Discussions</small></span>
          <span><PvIcon name="cart" /><strong>{{ bookmarkedProductKeys.length }}</strong><small>Products</small></span>
          <span><PvIcon name="library" /><strong>{{ bookmarkedContentSlugs.length }}</strong><small>Content</small></span>
        </div>

        <div class="pv-saved-grid">
          <article class="pv-panel">
            <header class="pv-panel-header"><h2>Saved Discussions</h2><span class="pv-count">{{ savedDiscussionSlugs.length }}</span></header>
            <div v-if="savedDiscussionSlugs.length === 0" class="pv-empty-inline"><PvIcon name="bookmark" /><strong>No saved discussions yet</strong><p>Save threads from the discussion list or thread page to collect them here.</p></div>
            <router-link v-for="slug in savedDiscussionSlugs" :key="slug" :to="savedDiscussionLink(slug)" class="pv-mini-row">
              <PvIcon name="bookmark" />
              <span><strong>{{ savedDiscussionLabel(slug) }}</strong><small>Discussion thread</small></span>
              <PvIcon name="chevron" />
            </router-link>
          </article>

          <article class="pv-panel">
            <header class="pv-panel-header"><h2>Bookmarked Products</h2><span class="pv-count">{{ bookmarkedProductKeys.length }}</span></header>
            <div v-if="bookmarkedProductKeys.length === 0" class="pv-empty-inline"><PvIcon name="cart" /><strong>No bookmarked products</strong><p>Browse vendors and bookmark products to see them here.</p></div>
            <router-link v-for="key in bookmarkedProductKeys" :key="key" :to="savedProductLink(key)" class="pv-mini-row">
              <PvIcon name="cart" />
              <span><strong>{{ savedProductLabel(key) }}</strong><small>Vendor product</small></span>
              <PvIcon name="chevron" />
            </router-link>
          </article>

          <article class="pv-panel">
            <header class="pv-panel-header"><h2>Bookmarked Content</h2><span class="pv-count">{{ bookmarkedContentSlugs.length }}</span></header>
            <div v-if="bookmarkedContentSlugs.length === 0" class="pv-empty-inline"><PvIcon name="library" /><strong>No bookmarked content</strong><p>Browse research, guides, and FAQ to bookmark reading material.</p></div>
            <router-link v-for="slug in bookmarkedContentSlugs" :key="slug" :to="savedContentLink(slug)" class="pv-mini-row">
              <PvIcon name="library" />
              <span><strong>{{ slug.replace(/[-_]/g, ' ') }}</strong><small>Saved article or guide</small></span>
              <PvIcon name="chevron" />
            </router-link>
          </article>
        </div>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Quick Links</h2>
          <div class="pv-filter-list">
            <router-link to="/discussions"><PvIcon name="discussions" /> Discussions <PvIcon name="chevron" /></router-link>
            <router-link to="/vendor-reviews"><PvIcon name="star" /> Vendor Reviews <PvIcon name="chevron" /></router-link>
            <router-link to="/research-library"><PvIcon name="library" /> Research Library <PvIcon name="chevron" /></router-link>
            <router-link to="/guides"><PvIcon name="question" /> Guides &amp; FAQ <PvIcon name="chevron" /></router-link>
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
