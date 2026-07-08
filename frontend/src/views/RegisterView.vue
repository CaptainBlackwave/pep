<template>
  <main class="pv-login" :style="{ backgroundImage: `linear-gradient(180deg, rgba(3,4,10,.66), rgba(3,4,10,.96)), url(${loginBackdrop})` }">
    <div class="pv-login-shell pv-login-shell--register">
      <section class="pv-login-brand">
        <router-link to="/login" class="pv-brand pv-brand--login">
          <span class="pv-brand-mark">PV</span>
          <span class="pv-brand-text">
            <strong>Peptide</strong>
            <span>Vendors</span>
          </span>
        </router-link>
        <p>Private access for approved community members.</p>
        <div class="pv-auth-proof" aria-label="Account requirements">
          <span><PvIcon name="lock" /> Invite access</span>
          <span><PvIcon name="shield" /> Age-gated</span>
          <span><PvIcon name="check" /> Moderated space</span>
        </div>
      </section>

      <section class="pv-login-card">
        <header>
          <span class="pv-auth-kicker"><PvIcon name="shield" /> Member registration</span>
          <h1>Create Account</h1>
          <p>Use your one-time code to join the community.</p>
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
                placeholder="Username"
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

          <label v-if="showAccessCode" for="access-code">
            Access Code
            <span class="pv-input-shell">
              <PvIcon name="lock" />
              <input
                id="access-code"
                v-model="form.access_code"
                type="text"
                :required="showAccessCode"
                placeholder="Enter your access code"
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
                :type="showPassword ? 'text' : 'password'"
                required
                minlength="8"
                autocomplete="new-password"
                placeholder="Create a password"
              >
              <button
                type="button"
                class="pv-password-button"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                @click="showPassword = !showPassword"
              >
                <PvIcon :name="showPassword ? 'eye-off' : 'eye'" />
              </button>
            </span>
            <span v-if="form.password" class="pv-password-strength" :data-score="passwordScore">
              <i></i>
              <small>{{ passwordStrengthLabel }}</small>
            </span>
          </label>

          <label for="password-confirmation">
            Confirm Password
            <span class="pv-input-shell">
              <PvIcon name="shield" />
              <input
                id="password-confirmation"
                v-model="form.password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                required
                minlength="8"
                autocomplete="new-password"
                placeholder="Confirm your password"
              >
              <button
                type="button"
                class="pv-password-button"
                :aria-label="showConfirmPassword ? 'Hide confirmation password' : 'Show confirmation password'"
                @click="showConfirmPassword = !showConfirmPassword"
              >
                <PvIcon :name="showConfirmPassword ? 'eye-off' : 'eye'" />
              </button>
            </span>
            <small v-if="passwordMismatch" class="pv-field-note pv-field-note--danger">Passwords do not match yet.</small>
          </label>

          <label class="pv-checkbox pv-age-check">
            <input v-model="agree18" type="checkbox" required>
            <span>I am 18 years or older and understand this site discusses research compounds for educational purposes only.</span>
          </label>

          <p v-if="ageError" class="error-message">{{ ageError }}</p>

          <button type="submit" :disabled="authStore.loading" class="pv-primary-button pv-login-submit">
            {{ authStore.loading ? 'Creating account...' : 'Create Account' }}
          </button>

          <p class="pv-login-register">
            Already have an account?
            <router-link to="/login">Log In</router-link>
          </p>
        </form>
      </section>
    </div>

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
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUserStore } from '@/stores/user'
import api from '@/services/api'
import PvIcon from '@/components/peptide/PvIcon.vue'
import loginBackdrop from '@/assets/peptide/login-backdrop.png'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const userStore = useUserStore()
const currentYear = new Date().getFullYear()
const showAccessCode = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)

const form = ref({
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  access_code: '',
})
const agree18 = ref(false)
const ageError = ref('')
const passwordMismatch = computed(() => Boolean(
  form.value.password_confirmation &&
  form.value.password &&
  form.value.password !== form.value.password_confirmation,
))
const passwordScore = computed(() => {
  const password = form.value.password
  return [
    password.length >= 8,
    /[A-Z]/.test(password),
    /[a-z]/.test(password),
    /\d/.test(password),
    /[^A-Za-z0-9]/.test(password),
  ].filter(Boolean).length
})
const passwordStrengthLabel = computed(() => {
  if (passwordScore.value >= 5) return 'Strong password'
  if (passwordScore.value >= 3) return 'Good password'
  return 'Keep strengthening it'
})

onMounted(async () => {
  try {
    const res = await api.get<{ access_code_required?: boolean }>('/api/v1/settings/public')
    showAccessCode.value = res.data.access_code_required === true
  } catch {
    // ignore
  }
})

async function handleRegister() {
  ageError.value = ''
  if (!agree18.value) {
    ageError.value = 'You must be 18 or older to register.'
    return
  }
  const success = await authStore.register(form.value)

  if (success) {
    await userStore.fetchProfile()
    router.push(typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard')
  }
}
</script>
