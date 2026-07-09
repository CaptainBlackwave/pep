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
    <MemberProfile v-else-if="detailMember" :profile="detailMember" />
    <div v-else class="pv-empty-route"><h1>Member not found</h1><p>This member profile has not been published or does not exist.</p></div>
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
