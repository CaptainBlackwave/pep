<template>
  <main class="pv-login" :style="{ backgroundImage: `linear-gradient(180deg, rgba(3,4,10,.66), rgba(3,4,10,.96)), url(${loginBackdrop})` }">
    <section class="pv-login-brand">
      <router-link to="/dashboard" class="pv-brand pv-brand--login">
        <span class="pv-brand-mark">PV</span>
        <span class="pv-brand-text">
          <strong>Peptide</strong>
          <span>Vendors</span>
        </span>
      </router-link>
      <p>Real reviews. Real lab results. Real people.</p>
    </section>

    <section class="pv-login-card">
      <header>
        <h1>Welcome Back</h1>
        <p>Log in to access the community</p>
      </header>

      <div v-if="authStore.error" class="error-message">
        {{ authStore.error }}
      </div>

      <form class="pv-login-form" @submit.prevent="handleLogin">
        <label for="email">
          Email or Username
          <span class="pv-input-shell">
            <PvIcon name="user" />
            <input
              id="email"
              v-model="form.email"
              type="text"
              required
              autofocus
              autocomplete="username"
              placeholder="Enter your email or username"
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
              autocomplete="current-password"
              placeholder="Enter your password"
            >
            <PvIcon name="eye" />
          </span>
        </label>

        <div class="pv-form-options">
          <label class="pv-checkbox" for="remember">
            <input id="remember" v-model="form.remember" type="checkbox">
            <span>Remember me</span>
          </label>
          <router-link to="/forgot-password">Forgot password?</router-link>
        </div>

        <button type="submit" :disabled="authStore.loading" class="pv-primary-button pv-login-submit">
          <span>{{ authStore.loading ? 'Signing in...' : 'Log In' }}</span>
          <span class="pv-sr-only">Sign in</span>
        </button>

        <p class="pv-login-register">
          Don&apos;t have an account?
          <router-link to="/register">Request Access</router-link>
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
  email: '',
  password: '',
  remember: true,
})

async function handleLogin() {
  const success = await authStore.login({
    login: form.value.email,
    password: form.value.password,
  })

  if (success) {
    await userStore.fetchProfile()
    router.push(typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard')
  }
}
</script>
