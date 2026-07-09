<template>
<section class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div>
            <h1>Search Results</h1>
            <p v-if="searchQuery">Showing results for "<strong>{{ searchQuery }}</strong>"</p>
          </div>
        </header>
        <article class="pv-panel">
          <div v-if="searchLoading" class="pv-loading">Searching...</div>
          <div v-else-if="searchError" class="pv-error">{{ searchError }}</div>
          <div v-else-if="searchResults.length === 0" class="pv-muted">No results found.</div>
          <router-link v-for="result in searchResults" :key="result.type + '-' + result.id" :to="result.url" class="pv-search-row">
            <span class="pv-tag">{{ result.type_label }}<template v-if="result.premium"> 🔒</template></span>
            <div>
              <strong>{{ result.title }}</strong>
              <p>{{ result.text }}</p>
            </div>
            <PvIcon name="chevron" />
          </router-link>
        </article>
      </main>
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
