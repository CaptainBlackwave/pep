<template>
  <main class="pv-page pv-legal-page">
    <section class="pv-legal-hero">
      <span class="pv-icon-tile"><PvIcon name="shield" /></span>
      <div>
        <p class="pv-kicker">Privacy Controls</p>
        <h1>Cookie Settings</h1>
        <p>Choose which browser preferences this community can remember on this device.</p>
      </div>
    </section>

    <div class="pv-legal-layout">
      <article class="pv-panel pv-cookie-settings-card">
        <header class="pv-panel-header">
          <div>
            <h2>Preferences</h2>
            <p class="pv-muted">Essential cookies are always on because they keep sign-in, security, and account protection working.</p>
          </div>
          <span class="pv-tag trusted">{{ savedStatus }}</span>
        </header>

        <div class="pv-cookie-option-list">
          <label class="pv-cookie-option locked">
            <span class="pv-icon-tile"><PvIcon name="lock" /></span>
            <span>
              <strong>Essential</strong>
              <small>Required for login sessions, CSRF protection, security, and saved consent.</small>
            </span>
            <span class="pv-switch active" aria-hidden="true"></span>
          </label>

          <label class="pv-cookie-option">
            <span class="pv-icon-tile"><PvIcon name="settings" /></span>
            <span>
              <strong>Preferences</strong>
              <small>Remember display choices such as theme, filters, and browsing preferences.</small>
            </span>
            <input v-model="preferences.preferences" type="checkbox" class="pv-sr-only">
            <span class="pv-switch" :class="{ active: preferences.preferences }" aria-hidden="true"></span>
          </label>

          <label class="pv-cookie-option">
            <span class="pv-icon-tile"><PvIcon name="chart" /></span>
            <span>
              <strong>Analytics</strong>
              <small>Allow anonymous usage signals that help improve layout, reliability, and navigation.</small>
            </span>
            <input v-model="preferences.analytics" type="checkbox" class="pv-sr-only">
            <span class="pv-switch" :class="{ active: preferences.analytics }" aria-hidden="true"></span>
          </label>
        </div>

        <footer class="pv-cookie-settings-actions">
          <button type="button" class="pv-small-button" @click="essentialOnly">Use Essential Only</button>
          <button type="button" class="pv-primary-button" @click="savePreferences"><PvIcon name="check" /> Save Preferences</button>
        </footer>

        <p v-if="statusMessage" class="pv-alert pv-alert--compact">{{ statusMessage }}</p>
      </article>

      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Related Policies</h2>
          <div class="pv-filter-list">
            <router-link to="/privacy"><PvIcon name="lock" /> Privacy Policy <PvIcon name="chevron" /></router-link>
            <router-link to="/terms"><PvIcon name="document" /> Terms of Service <PvIcon name="chevron" /></router-link>
            <router-link to="/community-rules"><PvIcon name="shield" /> Community Rules <PvIcon name="chevron" /></router-link>
            <router-link to="/data-deletion"><PvIcon name="trash" /> Data Deletion <PvIcon name="chevron" /></router-link>
          </div>
        </article>

        <article class="pv-panel pv-policy-note">
          <h2>Current Device</h2>
          <dl class="pv-data-list">
            <div><dt>Consent saved</dt><dd>{{ hasConsent ? 'Yes' : 'No' }}</dd></div>
            <div><dt>Preferences</dt><dd>{{ preferences.preferences ? 'On' : 'Off' }}</dd></div>
            <div><dt>Analytics</dt><dd>{{ preferences.analytics ? 'On' : 'Off' }}</dd></div>
          </dl>
        </article>
      </aside>
    </div>
  </main>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import PvIcon from '@/components/peptide/PvIcon.vue'

interface CookiePreferences {
  essential: true
  preferences: boolean
  analytics: boolean
}

const defaultPreferences = (): CookiePreferences => ({
  essential: true,
  preferences: true,
  analytics: false,
})

const preferences = ref<CookiePreferences>(defaultPreferences())
const hasConsent = ref(false)
const statusMessage = ref('')

const savedStatus = computed(() => hasConsent.value ? 'Saved' : 'Not saved')

onMounted(() => {
  hasConsent.value = Boolean(localStorage.getItem('cookie_consent'))
  const raw = localStorage.getItem('cookie_preferences')
  if (!raw) return

  try {
    const parsed = JSON.parse(raw) as Partial<CookiePreferences>
    preferences.value = {
      essential: true,
      preferences: Boolean(parsed.preferences),
      analytics: Boolean(parsed.analytics),
    }
  } catch {
    preferences.value = defaultPreferences()
  }
})

function savePreferences(): void {
  localStorage.setItem('cookie_consent', '1')
  localStorage.setItem('cookie_preferences', JSON.stringify(preferences.value))
  hasConsent.value = true
  statusMessage.value = 'Cookie preferences saved.'
  window.dispatchEvent(new CustomEvent('pv-cookie-preferences-saved'))
}

function essentialOnly(): void {
  preferences.value = { essential: true, preferences: false, analytics: false }
  savePreferences()
}
</script>
