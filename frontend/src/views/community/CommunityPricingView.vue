<template>
<section class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div>
            <h1>Choose Your Plan</h1>
            <p>Upgrade to Premium for full access to vendor reviews, lab results, and member messaging.</p>
          </div>
        </header>

        <div class="pv-pricing-grid">
          <article class="pv-pricing-card pv-pricing-card--free">
            <header>
              <h2>Free</h2>
              <p class="pv-price"><sup>£</sup>0</p>
              <small>Forever free</small>
            </header>
            <ul class="pv-check-list">
              <li>Community Discussions</li>
              <li>Guides &amp; FAQ</li>
              <li>Announcements</li>
              <li>Research Library</li>
              <li>5 discussions per day</li>
            </ul>
            <div class="pv-pricing-footer">
              <span v-if="authStore.isAuthenticated && membershipTier === 'free'" class="pv-tag trusted">Current Plan</span>
              <span v-else-if="!authStore.isAuthenticated" class="pv-tag">Always Free</span>
            </div>
          </article>

          <article class="pv-pricing-card pv-pricing-card--premium">
            <header>
              <span class="pv-badge">Popular</span>
              <h2>Premium</h2>
              <p class="pv-price"><sup>£</sup>{{ billingInterval === 'year' ? '10' : 'N/A' }}</p>
              <small>per year</small>
            </header>
            <ul class="pv-check-list">
              <li>Community Discussions</li>
              <li>Guides &amp; FAQ</li>
              <li>Announcements</li>
              <li>Research Library</li>
              <li>Vendor Reviews &amp; Profiles</li>
              <li>Access to Multiple Bulk Vendors</li>
              <li>Lab Results &amp; COAs</li>
              <li>Member Messaging</li>
              <li>Unlimited Discussions</li>
            </ul>
            <div class="pv-pricing-footer">
              <template v-if="authStore.isAuthenticated">
                <span v-if="membershipTier === 'paid'" class="pv-tag trusted">Active</span>
                <div v-else class="pv-pricing-buttons">
                  <button class="pv-primary-button pv-full" @click="subscribeWith('stripe')">
                    <PvIcon name="lock" /> Subscribe with Card
                  </button>
                  <button class="pv-paypal-button pv-full" @click="subscribeWith('paypal')">
                    Subscribe with PayPal
                  </button>
                </div>
              </template>
              <router-link v-else to="/login?redirect=/pricing" class="pv-primary-button pv-full">
                Log In to Subscribe
              </router-link>
            </div>
          </article>
        </div>

        <article class="pv-panel">
          <h2>All Premium Features</h2>
          <div class="pv-feature-grid">
            <div class="pv-feature-item">
              <PvIcon name="shield" />
              <strong>Vendor Reviews</strong>
              <small>Read and write verified vendor reviews with ratings, photos, and detailed feedback.</small>
            </div>
            <div class="pv-feature-item">
              <PvIcon name="flask" />
              <strong>Lab Results</strong>
              <small>Access independent lab testing reports including COAs, purity analysis, and batch data.</small>
            </div>
            <div class="pv-feature-item">
              <PvIcon name="message" />
              <strong>Messaging</strong>
              <small>Direct message other premium members for private discussions and collaboration.</small>
            </div>
            <div class="pv-feature-item">
              <PvIcon name="users" />
              <strong>Member Profiles</strong>
              <small>Browse detailed member profiles with activity history and reputation scores.</small>
            </div>
            <div class="pv-feature-item">
              <PvIcon name="discussions" />
              <strong>Unlimited Discussions</strong>
              <small>Create and participate in unlimited community discussions with no daily limits.</small>
            </div>
            <div class="pv-feature-item">
              <PvIcon name="upload" />
              <strong>File Uploads</strong>
              <small>Attach files, images, and documents to your discussions and messages.</small>
            </div>
          </div>
        </article>

        <p v-if="paymentStatusMessage" class="pv-alert pv-alert--compact">{{ paymentStatusMessage }}</p>
      </main>
    </div>
  </section>
</template>

<script lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { inject } from 'vue';
export default {
  setup() {
    const state = inject('communityState');
    if (!state) throw new Error('Missing community state');
    return state as any;
  }
}
</script>
