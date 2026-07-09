<template>
<section class="pv-page">
    <div v-if="detailArticle" class="pv-content-grid">
      <main class="pv-stack">
        <nav class="pv-breadcrumbs">Research Library <PvIcon name="chevron" /> {{ detailArticle.category }} <PvIcon name="chevron" /> {{ detailArticle.title }}</nav>
        <article class="pv-article-hero">
          <div><span class="pv-tag">{{ detailArticle.tag }}</span><h1>{{ detailArticle.title }}</h1><p>{{ detailArticle.excerpt }}</p><div class="pv-author-line"><span class="pv-avatar purple">{{ detailArticle.authorInitial }}</span><strong>{{ detailArticle.author }}</strong><span v-if="detailArticle.authorBadge" class="pv-tag trusted">{{ detailArticle.authorBadge }}</span><span>{{ detailArticle.date }}</span><span><PvIcon name="eye" /> {{ detailArticle.timeLabel }} read</span></div></div>
          <span class="pv-article-image" :style="contentThumbnailStyle(detailArticle)"></span>
        </article>
        <div class="pv-tabs pv-tabs--line"><button :class="{ active: researchDetailTab === 'article' }" @click="researchDetailTab = 'article'">Article</button><button :class="{ active: researchDetailTab === 'data' }" @click="researchDetailTab = 'data'">Figures & Data</button><button :class="{ active: researchDetailTab === 'references' }" @click="researchDetailTab = 'references'">References</button><button :class="{ active: researchDetailTab === 'comments' }" @click="researchDetailTab = 'comments'">Comments ({{ detailArticle.comments }})</button></div>
        <article v-if="researchDetailTab === 'article'" class="pv-prose">
          <template v-for="block in articleBodyBlocks" :key="block.text">
            <h2 v-if="block.kind === 'heading'">{{ block.text }}</h2>
            <ul v-else-if="block.kind === 'list'"><li v-for="item in block.items" :key="item">{{ item }}</li></ul>
            <p v-else>{{ block.text }}</p>
          </template>
        </article>
        <article v-else-if="researchDetailTab === 'data'" class="pv-panel"><h2>Figures & Data</h2><dl v-if="Object.keys(articleDataMetadata).length" class="pv-data-list"><div v-for="(value, key) in articleDataMetadata" :key="key"><dt>{{ formatMetadataKey(key) }}</dt><dd>{{ formatMetadataValue(value) }}</dd></div></dl><p v-else class="pv-muted">No figures or data were attached to this article.</p></article>
        <article v-else-if="researchDetailTab === 'references'" class="pv-panel"><h2>References</h2><p class="pv-muted">{{ articleReferences || 'No references were attached to this article.' }}</p></article>
        <article v-else class="pv-panel"><h2>Comments</h2><p class="pv-muted">Discussion comments are not attached to this article yet. Start a related topic in the community discussions.</p><router-link to="/discussions" class="pv-primary-button">Open Discussions</router-link></article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Quick Info</h2><dl class="pv-data-list"><div><dt>Category</dt><dd>{{ detailArticle.category }}</dd></div><div><dt>Compound</dt><dd>{{ detailArticle.metadata.compound ?? detailArticle.title }}</dd></div><div v-if="detailArticle.metadata.fda_approved !== undefined"><dt>FDA Status</dt><dd>{{ detailArticle.metadata.fda_approved ? 'FDA Approved' : 'Research Only' }}</dd></div><div><dt>Views</dt><dd>{{ detailArticle.views }}</dd></div><div><dt>Read Time</dt><dd>{{ detailArticle.timeLabel }}</dd></div></dl></article>
        <article class="pv-panel"><h2>Table of Contents</h2><ol class="pv-toc"><li v-for="heading in articleHeadings" :key="heading" :class="{ active: heading === articleHeadings[0] }" @click="scrollToHeading(heading)">{{ heading }}</li></ol></article>
        <article class="pv-panel"><h2>Related Articles</h2><div class="pv-mini-list"><router-link v-for="(article, index) in relatedArticles" :key="article.title" :to="article.href" class="pv-mini-row"><span class="pv-mini-thumb" :style="contentThumbnailStyle(article, Number(index) + 1)"></span><span><strong>{{ article.title }}</strong><small>{{ article.date }}</small></span></router-link></div><router-link to="/research-library" class="pv-primary-button pv-full">View all related articles</router-link></article>
      </aside>
    </div>
    <div v-else class="pv-empty-route"><h1>Research article not found</h1><p>This article has not been published or does not exist.</p></div>
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
