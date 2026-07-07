<template>
  <RouterView />
  <div v-if="showCookieConsent" class="pv-cookie-banner" role="dialog" aria-live="polite" aria-label="Cookie notice">
    <div class="pv-cookie-banner-inner">
      <span class="pv-cookie-icon"><PvIcon name="shield" /></span>
      <div class="pv-cookie-copy">
        <strong>Essential cookies</strong>
        <p>We use cookies to keep you signed in, remember preferences, and protect the community.</p>
      </div>
      <div class="pv-cookie-actions">
        <router-link to="/privacy" class="pv-small-button">Privacy</router-link>
        <button class="pv-primary-button" @click="acceptCookies">Accept</button>
      </div>
    </div>
  </div>
  <ToastContainer />
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterView } from 'vue-router'
import PvIcon from '@/components/peptide/PvIcon.vue'
import ToastContainer from './components/ToastContainer.vue'

const showCookieConsent = ref(false)

onMounted(() => {
  setCookieConsentVisible(!localStorage.getItem('cookie_consent'))
})

onUnmounted(() => {
  document.body.classList.remove('pv-cookie-visible')
})

function setCookieConsentVisible(visible: boolean): void {
  showCookieConsent.value = visible
  document.body.classList.toggle('pv-cookie-visible', visible)
}

function acceptCookies(): void {
  localStorage.setItem('cookie_consent', '1')
  setCookieConsentVisible(false)
}
</script>

<style>
/* Reset and global styles are in assets/main.css */
</style>
