<template>
  <main class="pv-page pv-legal-page">
    <section class="pv-legal-hero">
      <span class="pv-icon-tile"><PvIcon name="document" /></span>
      <div>
        <p class="pv-kicker">Copyright</p>
        <h1>DMCA Takedown Policy</h1>
        <p>How copyright owners can report allegedly infringing content hosted in the community.</p>
      </div>
    </section>

    <div class="pv-legal-layout">
      <article class="pv-panel pv-prose pv-legal-doc">
        <p>Peptide Vendors respects the intellectual property rights of others and expects its users to do the same. In accordance with the Digital Millennium Copyright Act (DMCA), we will respond expeditiously to notices of alleged copyright infringement.</p>

        <h2>Submitting a Notice</h2>
        <p>If you believe your copyrighted work has been reproduced on our site without authorization, provide the following to our designated agent:</p>
        <ul>
          <li>Your physical or electronic signature</li>
          <li>Identification of the copyrighted work claimed to have been infringed</li>
          <li>Identification of the material that is claimed to be infringing, including its location on the site</li>
          <li>Your contact information (address, phone, email)</li>
          <li>A statement that you have a good faith belief the use is not authorized</li>
          <li>A statement, made under penalty of perjury, that the information is accurate and you are the copyright owner or authorized to act on their behalf</li>
        </ul>

        <h2>Designated Agent</h2>
        <p>Send notices to: <a :href="'mailto:' + dmcaEmail">{{ dmcaEmail }}</a></p>

        <h2>Counter-Notice</h2>
        <p>If you believe material was removed by mistake, you may submit a counter-notice with the same contact and signature requirements above, including a statement of consent to jurisdiction in your federal district court.</p>
      </article>
      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Related Policies</h2>
          <div class="pv-filter-list">
            <router-link to="/terms"><PvIcon name="document" /> Terms of Service <PvIcon name="chevron" /></router-link>
            <router-link to="/privacy"><PvIcon name="lock" /> Privacy Policy <PvIcon name="chevron" /></router-link>
            <router-link to="/community-rules"><PvIcon name="shield" /> Community Rules <PvIcon name="chevron" /></router-link>
            <router-link to="/data-deletion"><PvIcon name="trash" /> Data Deletion <PvIcon name="chevron" /></router-link>
          </div>
        </article>

        <article class="pv-panel pv-policy-note">
          <h2>Designated Agent</h2>
          <p class="pv-muted">Send complete notices to:</p>
          <a :href="'mailto:' + dmcaEmail" class="pv-primary-button pv-full"><PvIcon name="mail" /> {{ dmcaEmail }}</a>
        </article>
      </aside>
    </div>
  </main>
</template>

<script setup lang="ts">
import PvIcon from '@/components/peptide/PvIcon.vue'

const dmcaEmail = 'dmca@pepvguides.com'
</script>
