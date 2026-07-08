<template>
  <main class="pv-page pv-legal-page">
    <section class="pv-legal-hero">
      <span class="pv-icon-tile"><PvIcon name="trash" /></span>
      <div>
        <p class="pv-kicker">Account Privacy</p>
        <h1>Data Deletion Request</h1>
        <p>Request deletion of personal data associated with your community account.</p>
      </div>
    </section>

    <div class="pv-legal-layout">
      <article class="pv-panel pv-prose pv-legal-doc">
        <h2>Submit A Request</h2>
        <p>Under GDPR and applicable privacy laws, you can request deletion of personal data tied to your account. We may retain limited records when required for security, fraud prevention, legal compliance, or moderation integrity.</p>

        <form v-if="!submitted" class="pv-form pv-data-deletion-form" @submit.prevent="submitRequest">
          <p v-if="error" class="pv-alert pv-alert--error">{{ error }}</p>
          <label>
            <span>Email address associated with your account</span>
            <input v-model="email" type="email" required placeholder="your@email.com">
          </label>
          <label>
            <span>Reason (optional)</span>
            <textarea v-model="reason" rows="3" placeholder="Why would you like your data deleted?"></textarea>
          </label>
          <button type="submit" :disabled="sending" class="pv-primary-button">{{ sending ? 'Sending...' : 'Submit Request' }}</button>
        </form>
        <div v-else>
          <p><strong>Request submitted.</strong> We will process your deletion request within 30 days and confirm via email.</p>
        </div>
      </article>

      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Before You Submit</h2>
          <ul class="pv-check-list">
            <li>Use the email linked to your account</li>
            <li>Include helpful context if you have multiple accounts</li>
            <li>Export account data from settings first if you need a copy</li>
          </ul>
        </article>

        <article class="pv-panel">
          <h2>Related Policies</h2>
          <div class="pv-filter-list">
            <router-link to="/privacy"><PvIcon name="lock" /> Privacy Policy <PvIcon name="chevron" /></router-link>
            <router-link to="/cookie-settings"><PvIcon name="settings" /> Cookie Settings <PvIcon name="chevron" /></router-link>
            <router-link to="/terms"><PvIcon name="document" /> Terms of Service <PvIcon name="chevron" /></router-link>
          </div>
        </article>
      </aside>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import api from '@/services/api'
import PvIcon from '@/components/peptide/PvIcon.vue'

const email = ref('')
const reason = ref('')
const sending = ref(false)
const submitted = ref(false)
const error = ref('')

async function submitRequest() {
  sending.value = true
  error.value = ''
  try {
    await api.post('/api/v1/data-deletion-request', { email: email.value, reason: reason.value })
    submitted.value = true
  } catch {
    error.value = 'Failed to submit request. Please contact support.'
  } finally {
    sending.value = false
  }
}
</script>
