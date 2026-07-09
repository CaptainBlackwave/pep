<template>
<section class="pv-page">
    <SettingsScreens :page="page" />
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
