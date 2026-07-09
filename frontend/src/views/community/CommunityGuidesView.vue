<template>
<section class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>Guides & FAQ</h1><p>Helpful guides, tutorials and answers to common questions from the community.</p></div>
          <div v-if="canUseContentStudio" class="pv-action-row pv-content-create-actions">
            <router-link to="/guides/new" class="pv-small-button"><PvIcon name="plus" /> Add Guide</router-link>
            <router-link to="/guides/faqs/new" class="pv-small-button"><PvIcon name="question" /> Add FAQ</router-link>
          </div>
        </header>
        <label class="pv-input-search pv-input-search--wide"><input v-model="guideSearch" placeholder="Search guides & FAQ..." @keydown.enter="loadGuidesContent"><PvIcon name="search" /></label>
        <div class="pv-guide-cats"><button :class="{ active: activeGuideCategory === '' }" @click="activeGuideCategory = ''; loadGuidesContent()"><PvIcon name="library" /><strong>All Topics</strong><small>View all</small></button><button v-for="category in contentCategories.guide" :key="category.slug" :class="{ active: activeGuideCategory === category.slug }" @click="activeGuideCategory = category.slug; loadGuidesContent()"><PvIcon name="document" /><strong>{{ category.name }}</strong><small>{{ category.count }} guides</small></button></div>
        <article class="pv-panel">
          <header class="pv-panel-header"><h2>Popular Guides</h2><router-link to="/guides" class="pv-purple-link">View all guides →</router-link></header>
          <p v-if="contentLoaded && guides.length === 0" class="pv-muted">No guides published yet.</p>
          <router-link v-for="(guide, index) in guides" :key="guide.title" :to="guide.href" class="pv-guide-row"><span class="pv-mini-thumb pv-guide-thumb" :style="contentThumbnailStyle(guide, index)"></span><span class="pv-topic-main"><strong>{{ guide.title }}</strong><small>{{ guide.excerpt }}</small><em>{{ guide.category }}</em></span><span><PvIcon name="document" /> {{ guide.metadata.guide_type ?? guide.type }}<br><PvIcon name="clock" /> {{ guide.timeLabel }} read<br><PvIcon name="eye" /> {{ guide.views }} views</span></router-link>
          <PaginationBlock :meta="guidePagination" @page="setGuidePage" />
        </article>
        <article id="faq-list" class="pv-panel"><header class="pv-panel-header"><h2>Frequently Asked Questions</h2><a class="pv-purple-link" href="#faq-list">View all FAQ →</a></header><details v-for="faq in faqs" :key="faq.slug"><summary><PvIcon name="question" /> {{ faq.title }}</summary><p class="pv-muted">{{ faq.body || faq.excerpt }}</p></details></article>
      </main>
      <aside class="pv-stack"><article class="pv-panel pv-help-card"><PvIcon name="question" /><h2>Need Help?</h2><p>Can&apos;t find what you&apos;re looking for?</p><router-link to="/discussions" class="pv-primary-button pv-full">Ask a Question</router-link></article><article class="pv-panel"><h2>Top FAQ Topics</h2><dl class="pv-data-list"><div v-for="category in contentCategories.faq" :key="category.slug"><dt>{{ category.name }}</dt><dd>{{ category.count }}</dd></div></dl></article><article class="pv-panel"><h2>Community Help</h2><div class="pv-mini-list"><router-link to="/discussions" class="pv-mini-row"><PvIcon name="question" /><span><strong>Ask the Community</strong><small>Get answers from experienced members</small></span><PvIcon name="chevron" /></router-link><router-link v-if="canUseContentStudio" to="/guides/new" class="pv-mini-row"><PvIcon name="document" /><span><strong>Submit a Guide</strong><small>Share your knowledge with others</small></span><PvIcon name="chevron" /></router-link><router-link :to="{ path: '/discussions', query: { q: 'issue' } }" class="pv-mini-row"><PvIcon name="bell" /><span><strong>Report an Issue</strong><small>Report outdated or incorrect info</small></span><PvIcon name="chevron" /></router-link></div></article><article class="pv-panel"><h2>Popular Tags</h2><span class="pv-chip-row"><span v-for="topic in contentTopics.guide" :key="topic.name">{{ topic.name }}</span></span></article></aside>
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
