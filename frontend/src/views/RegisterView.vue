<template>
  <main class="pv-login" :style="{ backgroundImage: `linear-gradient(180deg, rgba(3,4,10,.66), rgba(3,4,10,.96)), url(${loginBackdrop})` }">
    <section class="pv-login-brand">
      <router-link to="/login" class="pv-brand pv-brand--login">
        <span class="pv-brand-mark">PV</span>
        <span class="pv-brand-text">
          <strong>Peptide</strong>
          <span>Vendors</span>
        </span>
      </router-link>
      <p>Private access for approved community members.</p>
    </section>

    <section class="pv-login-card">
      <header>
        <h1>Request Access</h1>
        <p>Use your one-time code to create an account</p>
      </header>

      <div v-if="authStore.error" class="error-message">
        {{ authStore.error }}
      </div>

      <form class="pv-login-form" @submit.prevent="handleRegister">
        <label for="username">
          Username
          <span class="pv-input-shell">
            <PvIcon name="user" />
            <input
              id="username"
              v-model="form.username"
              type="text"
              required
              autofocus
              autocomplete="username"
              placeholder="Choose a username"
            >
          </span>
        </label>

        <label for="email">
          Email Address
          <span class="pv-input-shell">
            <PvIcon name="mail" />
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              placeholder="Enter your email"
            >
          </span>
        </label>

        <label for="access-code">
          Access Code
          <span class="pv-input-shell">
            <PvIcon name="lock" />
            <input
              id="access-code"
              v-model="form.access_code"
              type="text"
              required
              autocomplete="one-time-code"
              placeholder="Enter your private access code"
            >
          </span>
        </label>

        <label for="password">
          Password
          <span class="pv-input-shell">
            <PvIcon name="lock" />
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              minlength="8"
              autocomplete="new-password"
              placeholder="Create a password"
            >
          </span>
        </label>

        <label for="password-confirmation">
          Confirm Password
          <span class="pv-input-shell">
            <PvIcon name="shield" />
            <input
              id="password-confirmation"
              v-model="form.password_confirmation"
              type="password"
              required
              minlength="8"
              autocomplete="new-password"
              placeholder="Confirm your password"
            >
          </span>
        </label>

        <button type="submit" :disabled="authStore.loading" class="pv-primary-button pv-login-submit">
          {{ authStore.loading ? 'Creating account...' : 'Create Account' }}
        </button>

        <p class="pv-login-register">
          Already have an account?
          <router-link to="/login">Log In</router-link>
        </p>
      </form>
    </section>

    <footer class="pv-login-footer">
      <p>&copy; {{ currentYear }} Peptide Vendors. All rights reserved.</p>
      <nav>
        <router-link to="/terms">Terms of Service</router-link>
        <router-link to="/privacy">Privacy Policy</router-link>
        <router-link to="/community-rules">Community Rules</router-link>
      </nav>
    </footer>
  </main>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUserStore } from '@/stores/user'
import PvIcon from '@/components/peptide/PvIcon.vue'
import loginBackdrop from '@/assets/peptide/login-backdrop.png'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const userStore = useUserStore()
const currentYear = new Date().getFullYear()

const form = ref({
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  access_code: '',
})

async function handleRegister() {
  const success = await authStore.register(form.value)

  if (success) {
    await userStore.fetchProfile()
    router.push(typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard')
  }
}
</script>
