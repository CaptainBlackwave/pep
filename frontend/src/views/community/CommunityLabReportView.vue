<template>
<section class="pv-page">
    <div v-if="showUpgradePrompt" class="pv-upgrade-overlay">
      <div class="pv-upgrade-card">
        <span class="pv-upgrade-icon"><PvIcon name="flask" /></span>
        <h2>Premium Feature</h2>
        <p>Upgrade to Premium to access independent lab testing reports, COAs, purity analysis, and batch data.</p>
        <router-link to="/pricing" class="pv-primary-button">View Pricing</router-link>
      </div>
    </div>
    <div v-else-if="detailLabResult" class="pv-content-grid">
      <main class="pv-stack">
        <nav class="pv-breadcrumbs">Lab Results <PvIcon name="chevron" /> {{ detailLabResult.name }} <PvIcon name="chevron" /> Report</nav>
        <header class="pv-page-header">
          <div><h1>{{ detailLabResult.name }} <span class="pv-success-pill">{{ detailLabResult.purity }} Purity</span></h1><p>Batch: {{ detailLabResult.batch }} · Vendor: <router-link :to="{ path: '/lab-results', query: { q: detailLabResult.vendor } }">{{ detailLabResult.vendor }}</router-link> · Lab: <router-link :to="{ path: '/lab-results', query: { q: detailLabResult.lab } }">{{ detailLabResult.lab }}</router-link></p></div>
          <div class="pv-action-row"><button class="pv-small-button" @click="downloadLabReport"><PvIcon name="download" /> Download Report</button><button class="pv-primary-button" @click="shareCurrentPage(detailLabResult.name)"><PvIcon name="share" /> Share</button></div>
        </header>
        <div class="pv-tabs pv-tabs--line"><button :class="{ active: labDetailTab === 'overview' }" @click="labDetailTab = 'overview'">Report Overview</button><button :class="{ active: labDetailTab === 'certificate' }" @click="labDetailTab = 'certificate'">Full Certificate</button><button :class="{ active: labDetailTab === 'raw' }" @click="labDetailTab = 'raw'">Raw Data</button><button :class="{ active: labDetailTab === 'batch' }" @click="labDetailTab = 'batch'">Batch History</button></div>
        <p v-if="labStatusMessage" class="pv-alert pv-alert--compact">{{ labStatusMessage }}</p>
        <div v-if="labDetailTab === 'overview'" class="pv-report-meta">
          <span><PvIcon name="calendar" /><small>Tested Date</small><strong>{{ detailLabResult.date }}</strong></span>
          <span><PvIcon name="box" /><small>Received Date</small><strong>{{ detailLabResult.receivedDate }}</strong></span>
          <span><PvIcon name="document" /><small>Report ID</small><strong>{{ detailLabResult.reportId }}</strong></span>
          <span><PvIcon name="flask" /><small>Sample Type</small><strong>{{ detailLabResult.sampleType }}</strong></span>
          <span><PvIcon name="shield" /><small>Sample Condition</small><strong>{{ detailLabResult.sampleCondition }}</strong></span>
        </div>
        <article v-if="labDetailTab === 'certificate' || labDetailTab === 'overview'" class="pv-certificate">
          <header><strong>{{ detailLabResult.lab }}</strong><span>Certificate of Analysis</span></header>
          <div class="pv-cert-grid">
            <span>Client <strong>{{ detailLabResult.vendor }}</strong></span><span>Analysis Date <strong>{{ detailLabResult.date }}</strong></span>
            <span>Sample <strong>{{ detailLabResult.name }}</strong></span><span>Report ID <strong>{{ detailLabResult.reportId }}</strong></span>
            <span>Batch <strong>{{ detailLabResult.batch }}</strong></span><span>Receipt Date <strong>{{ detailLabResult.receivedDate }}</strong></span>
          </div>
          <table>
            <thead><tr><th>Test</th><th>Result</th></tr></thead>
            <tbody>
              <tr v-if="detailLabResult.identityResult"><td>Identity</td><td>{{ detailLabResult.identityResult }}</td></tr>
              <tr v-if="detailLabResult.purity"><td>Purity</td><td class="pv-green-text">{{ detailLabResult.purity }}</td></tr>
              <tr v-if="detailLabResult.waterContent"><td>Water Content</td><td>{{ detailLabResult.waterContent }}</td></tr>
              <tr v-if="detailLabResult.peptideContent"><td>Peptide Content</td><td>{{ detailLabResult.peptideContent }}</td></tr>
            </tbody>
          </table>
        </article>
        <article v-if="labDetailTab === 'raw'" class="pv-panel"><h2>Raw Data</h2><dl class="pv-data-list"><div><dt>Identity</dt><dd>{{ detailLabResult.identityResult || 'Not provided' }}</dd></div><div><dt>Purity</dt><dd>{{ detailLabResult.purity || 'Not provided' }}</dd></div><div><dt>Water Content</dt><dd>{{ detailLabResult.waterContent || 'Not provided' }}</dd></div><div><dt>Peptide Content</dt><dd>{{ detailLabResult.peptideContent || 'Not provided' }}</dd></div><div><dt>COA Filename</dt><dd>{{ detailLabResult.coaFilename || 'Not attached' }}</dd></div></dl></article>
        <article v-if="labDetailTab === 'batch'" class="pv-panel"><h2>Batch History</h2><p class="pv-muted">Showing the submitted report for batch {{ detailLabResult.batch }}. Use the lab results search to find other records for this vendor, compound, or lab.</p><div class="pv-action-row"><button class="pv-small-button" @click="findCurrentBatchResults">Find this batch</button><button class="pv-small-button" @click="findCurrentVendorLabResults">Find vendor results</button></div></article>
        <article v-if="labDetailTab === 'overview'" class="pv-panel"><h2>Additional Notes</h2><p>{{ detailLabResult.notes }}</p></article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Summary</h2>
          <div class="pv-ring" :style="detailLabRingStyle"><strong>{{ detailLabResult.purity }}</strong><span>Purity</span></div>
          <dl class="pv-data-list"><div><dt>Identity</dt><dd>{{ detailLabResult.identityResult }}</dd></div><div><dt>Purity</dt><dd>{{ detailLabResult.purity }}</dd></div><div><dt>Water Content</dt><dd>{{ detailLabResult.waterContent }}</dd></div><div><dt>Peptide Content</dt><dd>{{ detailLabResult.peptideContent }}</dd></div></dl>
          <div class="pv-pass-box"><PvIcon name="shield" /> Overall Result <strong>{{ detailLabResult.overallResult }}</strong></div>
        </article>
        <article class="pv-panel"><h2>Compound Details</h2><dl class="pv-data-list"><div><dt>Compound Name</dt><dd>{{ detailLabResult.name }}</dd></div><div><dt>Type</dt><dd>{{ detailLabResult.type }}</dd></div><div><dt>Use Case</dt><dd>{{ detailLabResult.use }}</dd></div></dl></article>
        <article class="pv-panel"><h2>About This Report</h2><p class="pv-muted">This report is provided by {{ detailLabResult.lab }} for independent testing and analysis. All testing is performed using validated analytical methods.</p><ul class="pv-check-list"><li>ISO 17025 Accredited Lab</li><li>Community Funded</li><li>Independent & Unbiased</li><li>Batch Specific Results</li></ul></article>
      </aside>
    </div>
    <div v-else class="pv-empty-route">
      <h1>Lab result not found</h1>
      <p>This report has not been published or does not exist.</p>
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
