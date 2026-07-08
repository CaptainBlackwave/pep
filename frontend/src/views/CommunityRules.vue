<template>
  <main class="pv-page pv-legal-page">
    <section class="pv-legal-hero">
      <span class="pv-icon-tile"><PvIcon name="shield" /></span>
      <div>
        <p class="pv-kicker">Community Standards</p>
        <h1>{{ page.title }}</h1>
        <p>Rules for respectful, evidence-led discussions, reviews, lab-result submissions, and member interactions.</p>
      </div>
    </section>

    <div class="pv-legal-layout">
      <article class="pv-panel pv-prose pv-legal-doc">
        <section v-for="section in policySections" :key="section.title">
          <h2>{{ section.title }}</h2>
          <p v-for="paragraph in section.paragraphs" :key="paragraph">{{ paragraph }}</p>
        </section>
      </article>

      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Community Documents</h2>
          <div class="pv-filter-list">
            <router-link to="/terms"><PvIcon name="document" /> Terms of Service <PvIcon name="chevron" /></router-link>
            <router-link to="/privacy"><PvIcon name="lock" /> Privacy Policy <PvIcon name="chevron" /></router-link>
            <router-link to="/community-rules" class="active"><PvIcon name="shield" /> Community Rules <PvIcon name="chevron" /></router-link>
            <router-link to="/dmca"><PvIcon name="document" /> DMCA Policy <PvIcon name="chevron" /></router-link>
          </div>
        </article>

        <article class="pv-panel pv-policy-note">
          <h2>Quick Standards</h2>
          <ul class="pv-check-list">
            <li>No transaction coordination or source solicitation</li>
            <li>No harassment, spam, scams, or evasion</li>
            <li>No medical claims presented as personal advice</li>
            <li>Reviews and lab reports should be honest and specific</li>
          </ul>
        </article>
      </aside>
    </div>
  </main>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import PvIcon from '@/components/peptide/PvIcon.vue'

const page = ref({ title: 'Community Rules', paragraphs: [] as string[] })
const defaultSections = [
  {
    title: 'Be Useful And Respectful',
    paragraphs: [
      'Keep discussion educational, evidence-led, and respectful. Personal attacks, harassment, intimidation, and pile-ons are not allowed.',
      'Search before posting, use clear titles, and keep replies on topic so threads stay useful for other members.',
    ],
  },
  {
    title: 'No Marketplace Activity',
    paragraphs: [
      'Do not coordinate transactions, solicit sources, advertise private deals, or use messages and discussions to bypass community safety rules.',
      'Vendor reviews and product discussions must stay informational and may be moderated when they become promotional or unsafe.',
    ],
  },
  {
    title: 'Evidence And Safety',
    paragraphs: [
      'Lab-result submissions should be batch-aware and accurate. Reviews should be honest, specific, and based on direct experience.',
      'This community is for information sharing and harm-reduction context only. It is not medical advice, legal advice, or a marketplace.',
    ],
  },
]

const policySections = computed(() => {
  if (page.value.paragraphs.length > 0) {
    return [{ title: 'Overview', paragraphs: page.value.paragraphs }]
  }

  return defaultSections
})

onMounted(async () => {
  try {
    const res = await api.get<{ legal_pages?: { rules?: { title: string; paragraphs: string[] } } }>('/api/v1/settings/public')
    if (res.data?.legal_pages?.rules) {
      page.value = res.data.legal_pages.rules
    }
  } catch {
    // use defaults
  }
})
</script>
