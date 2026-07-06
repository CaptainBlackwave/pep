<template>
  <main class="pv-page">
    <div class="pv-content-grid">
      <article class="pv-panel pv-prose">
        <router-link to="/login" class="pv-purple-link">Back to sign in</router-link>
        <h1>{{ page.title }}</h1>
        <p v-for="(paragraph, i) in page.paragraphs" :key="i">{{ paragraph }}</p>
      </article>
      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Community Documents</h2>
          <div class="pv-filter-list">
            <router-link to="/terms"><PvIcon name="document" /> Terms of Service <PvIcon name="chevron" /></router-link>
            <router-link to="/privacy"><PvIcon name="lock" /> Privacy Policy <PvIcon name="chevron" /></router-link>
            <router-link to="/community-rules"><PvIcon name="shield" /> Community Rules <PvIcon name="chevron" /></router-link>
          </div>
        </article>
      </aside>
    </div>
  </main>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '@/services/api'
import PvIcon from '@/components/peptide/PvIcon.vue'

const page = ref({ title: 'Terms of Service', paragraphs: [] as string[] })

onMounted(async () => {
  try {
    const res = await api.get<{ legal_pages?: { terms?: { title: string; paragraphs: string[] } } }>('/api/v1/settings/public')
    if (res.data?.legal_pages?.terms) {
      page.value = res.data.legal_pages.terms
    }
  } catch {
    // use defaults
  }
})
</script>
