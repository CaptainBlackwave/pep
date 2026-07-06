<template>
  <RouterView />
  <div v-if="showCookieConsent" class="pv-cookie-banner">
    <div class="pv-cookie-banner-inner">
      <p>This site uses essential cookies for authentication and security. By continuing, you accept our <router-link to="/privacy">Privacy Policy</router-link>.</p>
      <button class="pv-primary-button" @click="acceptCookies">Accept</button>
    </div>
  </div>
  <ToastContainer />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterView } from 'vue-router'
import ToastContainer from './components/ToastContainer.vue'

const showCookieConsent = ref(false)

onMounted(() => {
  if (!localStorage.getItem('cookie_consent')) {
    showCookieConsent.value = true
  }
})

function acceptCookies(): void {
  localStorage.setItem('cookie_consent', '1')
  showCookieConsent.value = false
}
</script>

<style>
/* Reset and global styles are in assets/main.css */
</style>
