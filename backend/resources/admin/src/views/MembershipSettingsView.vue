<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Membership Settings</h1>
        <p class="text-slate-400 mt-1">Manage membership status and pricing plans</p>
      </div>
      <div class="flex items-center gap-3">
        <span
          :class="[
            'px-3 py-1.5 rounded-full text-sm font-medium flex items-center gap-2',
            membershipEnabled
              ? 'bg-emerald-500/20 text-emerald-400'
              : 'bg-red-500/20 text-red-400'
          ]"
        >
          <span class="w-2 h-2 rounded-full" :class="membershipEnabled ? 'bg-emerald-400' : 'bg-red-400'"></span>
          {{ membershipEnabled ? 'Enabled' : 'Disabled' }}
        </span>
        <button
          @click="toggleMembership"
          :disabled="toggling"
          :class="[
            'px-4 py-2 rounded-xl font-medium transition-all flex items-center gap-2',
            membershipEnabled
              ? 'bg-red-500/20 text-red-400 hover:bg-red-500/30'
              : 'bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30'
          ]"
        >
          <ArrowPathIcon v-if="toggling" class="w-5 h-5 animate-spin" />
          <template v-else>
            {{ membershipEnabled ? 'Disable' : 'Enable' }}
          </template>
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-8">
      <div class="flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-500"></div>
        <span class="ml-3 text-slate-400">Loading membership settings...</span>
      </div>
    </div>

    <!-- Plans List -->
    <template v-else>
      <div
        v-for="plan in plans"
        :key="plan.id"
        class="rounded-2xl bg-slate-800/50 backdrop-blur border border-slate-700/50 p-6"
      >
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-amber-500/20">
              <CreditCardIcon class="w-5 h-5 text-amber-400" />
            </div>
            <div>
              <h3 class="text-lg font-semibold text-white">{{ plan.name }}</h3>
              <p class="text-sm text-slate-400">{{ plan.slug }}</p>
            </div>
          </div>
          <span
            :class="[
              'px-2 py-1 text-xs rounded-full',
              plan.active
                ? 'bg-emerald-500/20 text-emerald-400'
                : 'bg-slate-600/20 text-slate-400'
            ]"
          >
            {{ plan.active ? 'Active' : 'Inactive' }}
          </span>
        </div>

        <form @submit.prevent="updatePlan(plan)" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-300">Plan Name</label>
            <input
              v-model="plan.name"
              type="text"
              required
              class="w-full px-4 py-2 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/50"
            >
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-300">Description</label>
            <input
              v-model="plan.description"
              type="text"
              class="w-full px-4 py-2 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/50"
            >
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-300">Monthly Price ($)</label>
            <input
              v-model="plan.price_monthly"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full px-4 py-2 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/50"
            >
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-300">Yearly Price ($)</label>
            <input
              v-model="plan.price_yearly"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full px-4 py-2 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/50"
            >
          </div>
          <div class="lg:col-span-2 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span v-if="plan.saving" class="text-sm text-amber-400 flex items-center gap-1">
                <ArrowPathIcon class="w-4 h-4 animate-spin" />
                Saving...
              </span>
              <span v-else-if="plan.saved" class="text-sm text-emerald-400">Saved</span>
            </div>
            <button
              type="submit"
              :disabled="plan.saving"
              class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl font-medium hover:opacity-90 transition-opacity disabled:opacity-50"
            >
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import {
  CreditCardIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const loading = ref(true)
const toggling = ref(false)
const membershipEnabled = ref(false)
const plans = ref([])

async function fetchSettings() {
  try {
    const { data } = await api.get('/admin/membership/settings')
    membershipEnabled.value = data.membership_enabled
    plans.value = data.plans.map(p => ({ ...p, saving: false, saved: false }))
  } catch (e) {
    console.error('Failed to load membership settings:', e)
  } finally {
    loading.value = false
  }
}

async function toggleMembership() {
  toggling.value = true
  try {
    const { data } = await api.post('/admin/membership/settings/toggle')
    membershipEnabled.value = data.membership_enabled
  } catch (e) {
    console.error('Failed to toggle membership:', e)
  } finally {
    toggling.value = false
  }
}

async function updatePlan(plan) {
  plan.saving = true
  plan.saved = false
  try {
    const { data } = await api.patch(`/admin/membership/plans/${plan.id}`, {
      name: plan.name,
      price_monthly: plan.price_monthly,
      price_yearly: plan.price_yearly,
      description: plan.description,
      features: plan.features,
    })
    Object.assign(plan, data.plan)
    plan.saved = true
    setTimeout(() => { plan.saved = false }, 2000)
  } catch (e) {
    console.error('Failed to update plan:', e)
  } finally {
    plan.saving = false
  }
}

onMounted(fetchSettings)
</script>
