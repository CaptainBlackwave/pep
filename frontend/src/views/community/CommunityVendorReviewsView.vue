<template>
<section class="pv-page">
    <div class="pv-content-grid pv-content-grid--vendor-index">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>Vendors</h1><p>Browse vendor profiles, compare community feedback, and write a review from the vendor row.</p></div>
        </header>
        <article class="pv-panel">
          <div class="pv-toolbar"><div class="pv-tabs"><button :class="{ active: vendorStatusFilter === '' }" @click="setVendorStatusFilter('')">All Vendors</button><button v-for="status in vendorFilterOptions.statuses" :key="status.slug" :class="{ active: vendorStatusFilter === status.slug }" @click="setVendorStatusFilter(status.slug)">{{ status.name }}</button></div><button class="pv-small-button" type="button" @click="cycleVendorSort">{{ vendorSortLabel }} <PvIcon name="chevron" /></button><button class="pv-icon-button" @click="loadVendors"><PvIcon name="filter" /></button></div>
          <form class="pv-inline-search" @submit.prevent="loadVendors">
            <label class="pv-input-search">
              <input v-model="vendorSearch" placeholder="Search vendors..." type="search">
              <PvIcon name="search" />
            </label>
            <button class="pv-small-button" type="submit">Search</button>
            <button v-if="vendorHasActiveFilters" class="pv-small-button" type="button" @click="clearVendorFilters">Clear</button>
          </form>
          <p v-if="vendorStatusMessage" class="pv-alert pv-alert--compact">{{ vendorStatusMessage }}</p>
          <div v-if="vendorsLoaded && vendors.length === 0" class="pv-empty-inline"><PvIcon name="star" /><strong>{{ authStore.isAuthenticated ? 'No vendors found' : 'Sign in to browse vendors' }}</strong><p>{{ authStore.isAuthenticated ? 'Check back later for new vendors.' : 'Create an account or sign in to browse community-reviewed vendors, compare ratings, and read reviews.' }}</p><router-link v-if="!authStore.isAuthenticated" to="/register" class="pv-primary-button">Create Account</router-link></div>
          <article v-for="vendor in vendors" :key="vendor.slug" class="vendor-card">
            <router-link :to="vendor.href" class="vendor-arrow" :aria-label="`Open ${vendor.name}`"><PvIcon name="chevron" /></router-link>
            <div class="vendor-top">
              <span v-if="vendor.imageUrl" class="vendor-logo"><img :src="vendor.imageUrl" :alt="vendor.name"></span>
              <span v-else class="vendor-logo vendor-logo--letter">{{ vendor.logoText }}</span>
              <div class="vendor-info">
                <h3>{{ vendor.name }} <span v-if="vendor.tier === 'premium'" class="pv-tier-badge pv-tier-badge--premium">Premium</span></h3>
                <span class="trusted"><PvIcon name="check" /> {{ vendor.status }}</span>
                <span v-if="vendor.country" class="country-badge">{{ countryFlag(vendor.country) }} {{ vendor.country }}</span>
                <p class="vendor-meta"><PvIcon name="star" /> {{ vendor.reviews }} {{ vendor.reviews === 1 ? 'review' : 'reviews' }} <span>Member since {{ vendor.since || 'recently' }}</span></p>
              </div>
            </div>
            <div class="vendor-tags">
              <span v-for="chip in vendor.chips" :key="chip">{{ chip }}</span>
            </div>
            <div class="vendor-stats">
              <div>
                <strong><PvIcon name="star" /> {{ vendor.rating || '0.0' }} <small>/ 5</small></strong>
                <span>Overall Rating</span>
              </div>
              <div>
                <strong><PvIcon name="cart" /> {{ vendor.buyAgain || '0%' }}</strong>
                <span>Would buy again</span>
              </div>
            </div>
            <div class="vendor-actions">
              <router-link :to="vendor.href" class="secondary"><PvIcon name="eye" /> View Vendor</router-link>
              <router-link :to="`${vendor.href}/review`" class="primary"><PvIcon name="edit" /> Write Review</router-link>
            </div>
          </article>
          <PaginationBlock :meta="vendorPagination" @page="setVendorPage" />
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Overall Rating</h2><div class="pv-big-rating">{{ vendorStats.average_rating }} <small>/5</small> <span class="pv-stars">★★★★★</span></div><p class="pv-muted">Based on {{ formatCount(vendorStats.total_reviews) }} reviews</p></article>
        <article class="pv-panel pv-filter-panel">
          <header class="pv-panel-header"><h2>Filter Vendors</h2><button class="pv-text-button" type="button" @click="clearVendorFilters">Clear all</button></header>
          <label class="pv-input-search">
            <input v-model="vendorSearch" placeholder="Search vendors..." type="search" @keyup.enter="applyVendorFilters">
            <PvIcon name="search" />
          </label>
          <label>Rating<select v-model="vendorRatingFilter"><option value="">All ratings</option><option v-for="rating in vendorFilterOptions.ratings" :key="rating" :value="String(rating)">{{ rating }}+ stars</option></select></label>
          <label>Status<select v-model="vendorStatusFilter"><option value="">All statuses</option><option v-for="status in vendorFilterOptions.statuses" :key="status.slug" :value="status.slug">{{ status.name }}</option></select></label>
          <label>Tag<select v-model="vendorTagFilter"><option value="">All tags</option><option v-for="tag in vendorFilterOptions.tags" :key="tag" :value="tag">{{ tag }}</option></select></label>
          <button class="pv-primary-button pv-full" type="button" @click="applyVendorFilters">Apply Filters</button>
        </article>
        <article class="pv-panel"><header class="pv-panel-header"><h2>Top Vendors</h2><router-link to="/vendor-reviews" class="pv-purple-link">View all</router-link></header><div class="pv-ranked-list"><router-link v-for="(vendor, index) in topVendors" :key="vendor.name" :to="vendor.href" class="pv-ranked-row"><span class="pv-rank">{{ Number(index) + 1 }}</span><span class="pv-vendor-logo"><img v-if="vendor.imageUrl" :src="vendor.imageUrl" :alt="vendor.name"><template v-else>{{ vendor.logo }}</template></span><strong>{{ vendor.name }}</strong><span class="pv-green-text"><PvIcon name="star" /> {{ vendor.rating }}</span></router-link></div></article>
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
