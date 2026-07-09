<template>
<section class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>Research Library</h1><p>Explore research, studies, and educational resources on peptides and performance compounds.</p></div>
          <router-link v-if="canUseContentStudio" to="/research-library/new" class="pv-small-button"><PvIcon name="plus" /> Add Research</router-link>
        </header>
        <div class="pv-tabs"><button :class="{ active: activeResearchCategory === '' }" @click="activeResearchCategory = ''; loadResearchContent()">All</button><button v-for="category in contentCategories.research" :key="category.slug" :class="{ active: activeResearchCategory === category.slug }" @click="activeResearchCategory = category.slug; loadResearchContent()">{{ category.name }}</button></div>
        <div class="pv-toolbar"><label class="pv-input-search"><input v-model="researchSearch" placeholder="Search articles, compounds, topics..." @input="loadResearchContent"><PvIcon name="search" /></label><button class="pv-small-button" @click="cycleResearchSort">{{ researchSortLabel }} <PvIcon name="chevron" /></button><span class="pv-icon-button active pv-mode-indicator" aria-label="Library view"><PvIcon name="library" /></span><button class="pv-icon-button pv-icon-button--static" @click="loadResearchContent"><PvIcon name="filter" /></button></div>
        <p v-if="contentStatusMessage" class="pv-form-error">{{ contentStatusMessage }}</p>
        <p v-if="contentLoaded && articles.length === 0" class="pv-muted">No research articles published yet.</p>
        <div class="pv-article-grid">
           <router-link v-for="(article, index) in articles" :key="article.title" :to="article.href" class="pv-article-card">
              <span class="pv-thumb" :style="contentThumbnailStyle(article, index)"></span>
              <span class="pv-tag">{{ article.category }}</span>
              <span class="pv-tag" :class="{ trusted: article.authorBadge === 'FDA Approved' }">{{ article.authorBadge }}</span>
             <h2>{{ article.title }}</h2>
             <p>{{ article.excerpt }}</p>
            <footer><span><PvIcon name="calendar" /> {{ article.date }}</span><span><PvIcon name="eye" /> {{ article.views }}</span></footer>
            <PvIcon name="bookmark" class="pv-bookmark" :class="{ active: isBookmarkedContent(article.slug) }" />
          </router-link>
        </div>
        <PaginationBlock :meta="researchPagination" @page="setResearchPage" />
      </main>
      <aside class="pv-stack">
        <article class="pv-panel pv-filter-panel">
          <header class="pv-panel-header"><h2>Filters</h2><button class="pv-text-button" type="button" @click="clearResearchFilters">Clear all</button></header>
          <label class="pv-input-search">
            <input v-model="researchSearch" placeholder="Search research library..." type="search" @keyup.enter="applyResearchFilters">
            <PvIcon name="search" />
          </label>
          <label>Category<select v-model="activeResearchCategory"><option value="">All categories</option><option v-for="category in contentFilterOptions.research.categories" :key="category.slug" :value="category.slug">{{ category.name }}</option></select></label>
          <label>Compound<select v-model="researchCompoundFilter"><option value="">All compounds</option><option v-for="compound in contentFilterOptions.research.compounds" :key="compound" :value="compound">{{ compound }}</option></select></label>
          <label>Tag<select v-model="researchTagFilter"><option value="">All tags</option><option v-for="tag in contentFilterOptions.research.tags" :key="tag" :value="tag">{{ tag }}</option></select></label>
          <label>Sort By<select v-model="researchSort"><option v-for="sort in contentFilterOptions.research.sorts" :key="sort.value" :value="sort.value">{{ sort.label }}</option></select></label>
          <label>Published From<input v-model="researchPublishedFrom" :min="contentFilterOptions.research.date_bounds?.from ?? undefined" :max="contentFilterOptions.research.date_bounds?.to ?? undefined" type="date"></label>
          <label>Published To<input v-model="researchPublishedTo" :min="contentFilterOptions.research.date_bounds?.from ?? undefined" :max="contentFilterOptions.research.date_bounds?.to ?? undefined" type="date"></label>
          <button class="pv-primary-button pv-full" type="button" @click="applyResearchFilters">Apply Filters</button>
        </article>
        <article class="pv-panel"><h2>Popular Topics</h2><dl class="pv-data-list"><div v-for="topic in popularTopics" :key="topic.name"><dt>{{ topic.name }}</dt><dd>{{ topic.count }}</dd></div></dl><button class="pv-purple-link" type="button" @click="clearResearchFilters">View all topics -></button></article>
        <article class="pv-panel"><h2>About the Library</h2><p class="pv-muted">Our research library is curated from credible sources and community contributions. Always do your own research and consult a professional.</p><router-link class="pv-purple-link" to="/guides">Submission Guidelines -></router-link></article>
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
