<template>
  <main class="pv-page">
    <div class="pv-content-grid">
      <article class="pv-panel pv-prose">
        <router-link to="/" class="pv-purple-link">Back to home</router-link>
        <h1>Data Deletion Request</h1>
        <p>Under GDPR and applicable privacy laws, you have the right to request deletion of your personal data.</p>

        <form v-if="!submitted" class="pv-form" @submit.prevent="submitRequest">
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
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import api from '@/services/api'

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
