<template>
<section class="pv-page">
    <div v-if="showUpgradePrompt" class="pv-upgrade-overlay">
      <div class="pv-upgrade-card">
        <span class="pv-upgrade-icon"><PvIcon name="flask" /></span>
        <h2>Premium Feature</h2>
        <p>Upgrade to Premium to access independent lab testing reports, COAs, purity analysis, and batch data.</p>
        <router-link to="/pricing" class="pv-primary-button">View Pricing</router-link>
      </div>
    </div>
    <div v-else class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>Lab Results</h1><p>Independent lab testing and analysis from community members.</p></div>
          <button class="pv-primary-button" @click="openSubmitLabResult"><PvIcon name="flask" /> Submit Lab Result</button>
        </header>
        <article class="pv-panel">
          <div class="pv-toolbar">
            <div class="pv-tabs">
              <button :class="{ active: labTypeFilter === '' }" @click="setLabTypeFilter('')">All Results</button>
              <button v-for="type in labFilterOptions.compound_types" :key="type" :class="{ active: labTypeFilter === type }" @click="setLabTypeFilter(type)">{{ type }}</button>
            </div>
            <button class="pv-small-button" type="button" @click="cycleLabSort">{{ labSortLabel }} <PvIcon name="chevron" /></button>
            <button class="pv-icon-button" @click="applyLabFilters"><PvIcon name="filter" /></button>
          </div>
          <form class="pv-inline-search" @submit.prevent="applyLabFilters">
            <label class="pv-input-search">
              <input v-model="labSearch" placeholder="Search compounds, vendors, batches, labs..." type="search">
              <PvIcon name="search" />
            </label>
            <button class="pv-small-button" type="submit">Search</button>
            <button v-if="labHasActiveFilters" class="pv-small-button" type="button" @click="clearLabFilters">Clear</button>
          </form>
          <p v-if="labStatusMessage" class="pv-alert pv-alert--compact">{{ labStatusMessage }}</p>
          <p v-if="labResultsLoaded && labResults.length === 0" class="pv-muted">No lab results found.</p>
          <router-link v-for="result in labResults" :key="result.slug" :to="result.href" class="pv-result-row">
            <span class="pv-coa-thumb pv-coa-thumb--large"></span>
            <span class="pv-vial-icon" :class="result.color"><PvIcon name="flask" /></span>
            <span class="pv-topic-main">
              <strong>{{ result.name }}</strong>
              <small>Vendor: {{ result.vendor }}</small>
              <small>Batch: {{ result.batch }}</small>
              <small>Lab: <em>{{ result.lab }}</em></small>
              <span class="pv-chip-row"><span>{{ result.type }}</span><span>{{ result.use }}</span><span>{{ result.sampleType }}</span></span>
            </span>
            <span class="pv-purity pv-purity--green">{{ result.purity }}<small>Purity</small></span>
            <span class="pv-identity"><PvIcon name="shield" /> {{ result.identityResult }}<br>Identity</span>
            <span class="pv-date">{{ result.date }}<small>Tested Date</small></span>
            <span class="pv-small-button">View Report</span>
            <span class="pv-row-meta"><PvIcon name="eye" /> {{ result.views }} <PvIcon name="message" /> {{ result.comments }}</span>
          </router-link>
          <PaginationBlock :meta="labPagination" @page="setLabPage" />
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel pv-filter-panel">
          <header class="pv-panel-header"><h2>Filter Results</h2><button class="pv-purple-link" @click="clearLabFilters">Clear all</button></header>
          <label class="pv-input-search"><input v-model="labSearch" placeholder="Search compounds, vendors..." @keydown.enter="applyLabFilters"><PvIcon name="search" /></label>
          <label>Compound Type<select v-model="labTypeFilter"><option value="">All compound types</option><option v-for="type in labFilterOptions.compound_types" :key="type" :value="type">{{ type }}</option></select></label>
          <label>Compound<select v-model="labCompoundFilter"><option value="">All compounds</option><option v-for="compound in labFilterOptions.compounds" :key="compound" :value="compound">{{ compound }}</option></select></label>
          <label>Vendor<select v-model="labVendorFilter"><option value="">All vendors</option><option v-for="vendor in labFilterOptions.vendors" :key="vendor" :value="vendor">{{ vendor }}</option></select></label>
          <label>Lab<select v-model="labLabFilter"><option value="">All labs</option><option v-for="lab in labFilterOptions.labs" :key="lab" :value="lab">{{ lab }}</option></select></label>
          <button class="pv-primary-button pv-full" @click="applyLabFilters">Apply Filters</button>
        </article>
        <article class="pv-panel">
          <h2>About Lab Results</h2>
          <p class="pv-muted">All lab results are shared by community members for educational and harm reduction purposes only.</p>
          <ul class="pv-check-list"><li>Independent testing</li><li>Community verified</li><li>COA and analysis reports</li><li>Batch specific results</li></ul>
          <button class="pv-purple-link" type="button" @click="openSubmitLabResult">How to submit a lab result -></button>
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
