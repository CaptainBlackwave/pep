<template>
  <main class="pv-page pv-legal-page">
    <section class="pv-legal-hero">
      <span class="pv-icon-tile"><PvIcon name="document" /></span>
      <div>
        <p class="pv-kicker">Community Document</p>
        <h1>{{ page.title }}</h1>
        <p>The terms that govern account access, acceptable use, community content, and moderation.</p>
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
            <router-link to="/terms" class="active"><PvIcon name="document" /> Terms of Service <PvIcon name="chevron" /></router-link>
            <router-link to="/privacy"><PvIcon name="lock" /> Privacy Policy <PvIcon name="chevron" /></router-link>
            <router-link to="/community-rules"><PvIcon name="shield" /> Community Rules <PvIcon name="chevron" /></router-link>
            <router-link to="/cookie-settings"><PvIcon name="settings" /> Cookie Settings <PvIcon name="chevron" /></router-link>
          </div>
        </article>

        <article class="pv-panel pv-policy-note">
          <h2>Applies To</h2>
          <ul class="pv-check-list">
            <li>Public and private community areas</li>
            <li>Discussion posts, replies, reviews, and uploaded files</li>
            <li>Account settings, memberships, and moderation decisions</li>
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

const page = ref({ title: 'Terms of Service', paragraphs: [] as string[] })
const defaultSections = [
  {
    title: 'Acceptable Use',
    paragraphs: [
      'Use the community for lawful, educational discussion. Do not use the site for marketplace activity, spam, harassment, evasion, or unsafe conduct.',
      'You are responsible for anything submitted through your account, including discussions, reviews, profile details, and uploaded media.',
    ],
  },
  {
    title: 'Community Content',
    paragraphs: [
      'Member posts and submissions are informational and may be moderated, edited, hidden, or removed when they break site rules or create risk for the community.',
      'You should verify information independently before relying on it. Nothing on the site is medical, legal, or professional advice.',
    ],
  },
  {
    title: 'Account Access',
    paragraphs: [
      'We may restrict or remove access for abuse, fraud, unsafe behavior, chargeback abuse, account sharing, or attempts to bypass access controls.',
      'Membership features can change as the platform improves, but core account and privacy controls remain available from settings.',
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
    const res = await api.get<{ legal_pages?: { terms?: { title: string; paragraphs: string[] } } }>('/api/v1/settings/public')
    if (res.data?.legal_pages?.terms) {
      page.value = res.data.legal_pages.terms
    }
  } catch {
    // use defaults
  }
})
</script>
