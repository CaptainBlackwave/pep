<template>
<section class="pv-page">
    <div v-if="detailGuide" class="pv-content-grid">
      <main class="pv-stack">
        <nav class="pv-breadcrumbs">Guides & FAQ <PvIcon name="chevron" /> {{ detailGuide.category }} <PvIcon name="chevron" /></nav>
        <header class="pv-page-header"><div><h1>{{ detailGuide.title }}</h1><p>{{ detailGuide.excerpt }}</p><span class="pv-muted"><PvIcon name="calendar" /> {{ detailGuide.date }} · <PvIcon name="clock" /> {{ detailGuide.timeLabel }} read · <PvIcon name="eye" /> {{ detailGuide.views }} views</span></div><div class="pv-action-row"><button class="pv-small-button" @click="toggleContentBookmark(detailGuide.slug)"><PvIcon name="bookmark" /> {{ isBookmarkedContent(detailGuide.slug) ? 'Bookmarked' : 'Bookmark' }}</button><button class="pv-primary-button" @click="printCurrentPage"><PvIcon name="download" /> Print / PDF</button></div></header>
        <span class="pv-guide-hero" :style="contentThumbnailStyle(detailGuide)"></span>
        <article class="pv-alert"><PvIcon name="shield" /><strong>Disclaimer</strong><p>This guide is for educational purposes only and does not constitute medical advice. Always follow lab safety best practices and consult a healthcare professional for personal guidance.</p></article>
        <article class="pv-prose">
          <template v-for="block in guideBodyBlocks" :key="block.text">
            <h2 v-if="block.kind === 'heading'">{{ block.text }}</h2>
            <ul v-else-if="block.kind === 'list'"><li v-for="item in block.items" :key="item">{{ item }}</li></ul>
            <p v-else>{{ block.text }}</p>
          </template>
          <h2 v-if="detailGuideSteps.length">Step-by-Step Instructions</h2>
          <div v-if="detailGuideSteps.length" class="pv-step-list"><div v-for="(step, index) in detailGuideSteps" :key="step.title" class="pv-step"><span>{{ Number(index) + 1 }}</span><div><strong>{{ step.title }}</strong><p>{{ step.text }}</p></div><PvIcon :name="step.icon" /></div></div>
          <div class="pv-pass-box"><PvIcon name="shield" /> <span><strong>Safety First</strong><br>When in doubt, do not use the product. Your health and safety come first.</span></div>
        </article>
      </main>
      <aside class="pv-stack"><article class="pv-panel"><h2>On This Page</h2><ol class="pv-toc"><li v-for="heading in guideHeadings" :key="heading" :class="{ active: heading === guideHeadings[0] }">{{ heading }}</li></ol></article><article class="pv-panel"><h2>Quick Info</h2><dl class="pv-data-list"><div><dt>Difficulty</dt><dd><span class="pv-green-dot"></span> {{ detailGuide.metadata.difficulty ?? 'Beginner' }}</dd></div><div><dt>Time to Complete</dt><dd>{{ detailGuide.timeLabel }}</dd></div><div><dt>Category</dt><dd>{{ detailGuide.category }}</dd></div><div><dt>Last Updated</dt><dd>{{ detailGuide.date }}</dd></div></dl></article><article class="pv-panel"><h2>Related Guides</h2><div class="pv-mini-list"><router-link v-for="(guide, index) in relatedGuides" :key="guide.title" :to="guide.href" class="pv-mini-row"><span class="pv-mini-thumb" :style="contentThumbnailStyle(guide, Number(index) + 1)"></span><span><strong>{{ guide.title }}</strong><small>{{ guide.timeLabel }} read</small></span></router-link></div></article><article class="pv-panel pv-help-card"><h2>Still Need Help?</h2><p>Ask the community or submit a question.</p><router-link to="/discussions" class="pv-primary-button pv-full">Ask a Question</router-link></article></aside>
    </div>
    <div v-else class="pv-empty-route"><h1>Guide not found</h1><p>This guide has not been published or does not exist.</p></div>
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
