<template>
  <main class="pv-page pv-legal-page">
    <section class="pv-legal-hero">
      <span class="pv-icon-tile"><PvIcon name="lock" /></span>
      <div>
        <p class="pv-kicker">Privacy</p>
        <h1>{{ page.title }}</h1>
        <p>How account data, moderation records, preferences, and community activity are used to run the site.</p>
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
          <h2>Privacy Controls</h2>
          <div class="pv-filter-list">
            <router-link to="/settings/privacy"><PvIcon name="lock" /> Account Privacy <PvIcon name="chevron" /></router-link>
            <router-link to="/settings/notifications"><PvIcon name="bell" /> Notifications <PvIcon name="chevron" /></router-link>
            <router-link to="/cookie-settings"><PvIcon name="settings" /> Cookie Settings <PvIcon name="chevron" /></router-link>
            <router-link to="/data-deletion"><PvIcon name="trash" /> Data Deletion <PvIcon name="chevron" /></router-link>
          </div>
        </article>

        <article class="pv-panel">
          <h2>Community Documents</h2>
          <div class="pv-filter-list">
            <router-link to="/terms"><PvIcon name="document" /> Terms of Service <PvIcon name="chevron" /></router-link>
            <router-link to="/privacy" class="active"><PvIcon name="lock" /> Privacy Policy <PvIcon name="chevron" /></router-link>
            <router-link to="/community-rules"><PvIcon name="shield" /> Community Rules <PvIcon name="chevron" /></router-link>
          </div>
        </article>
      </aside>
    </div>
  </main>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import PvIcon from '@/components/peptide/PvIcon.vue'

const page = ref({ title: 'Privacy Policy', paragraphs: [] as string[] })
const defaultSections = [
  {
    title: 'What We Store',
    paragraphs: [
      'We store account details, authentication records, profile preferences, messages, submissions, moderation records, and saved community actions needed to operate the private community.',
      'Some technical information, such as session state and device-level preferences, is kept in your browser so the application can stay usable between visits.',
    ],
  },
  {
    title: 'How It Is Used',
    paragraphs: [
      'Data is used to authenticate access, display community content, process settings, prevent abuse, support moderation, and improve reliability.',
      'We do not ask members to publish private medical, payment, address, or identifying information in public community areas.',
    ],
  },
  {
    title: 'Your Choices',
    paragraphs: [
      'Use account settings to manage profile visibility, notification preferences, active sessions, API tokens, blocked users, and direct-message controls.',
      'You can submit a data deletion request from the dedicated data deletion page.',
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
    const res = await api.get<{ legal_pages?: { privacy?: { title: string; paragraphs: string[] } } }>('/api/v1/settings/public')
    if (res.data?.legal_pages?.privacy) {
      page.value = res.data.legal_pages.privacy
    }
  } catch {
    // use defaults
  }
})
</script>
