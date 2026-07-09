<template>
  <div class="pv-page pv-calculator-page">
    <div class="pv-page-header">
      <div class="pv-page-header-title">
        <h1>Peptide Calculator</h1>
        <p>Calculate your exact dosage and syringe draw volume quickly and accurately.</p>
      </div>
    </div>

    <div class="pv-content-grid">
      <div class="pv-calculator-card">
        <form @submit.prevent class="pv-form">
          <div class="pv-form-group">
            <label>Syringe Type</label>
            <div class="pv-syringe-options pv-syringe-type-options">
              <label class="pv-radio-card" :class="{ active: syringeType === 100 }">
                <input type="radio" v-model="syringeType" :value="100" name="syringeType" />
                <span class="pv-radio-content">
                  <strong>U-100</strong>
                  <small>Standard</small>
                </span>
              </label>
              <label class="pv-radio-card" :class="{ active: syringeType === 40 }">
                <input type="radio" v-model="syringeType" :value="40" name="syringeType" />
                <span class="pv-radio-content">
                  <strong>U-40</strong>
                  <small>Veterinary</small>
                </span>
              </label>
            </div>
          </div>

          <div class="pv-form-group">
            <label>Syringe Size</label>
            <div class="pv-syringe-options">
              <label class="pv-radio-card" :class="{ active: syringePreset === 1 }">
                <input type="radio" v-model="syringePreset" :value="1" name="syringeSize" />
                <span class="pv-radio-content">
                  <strong>1 mL</strong>
                  <small>{{ syringeType }} Units</small>
                </span>
              </label>
              <label class="pv-radio-card" :class="{ active: syringePreset === 0.5 }">
                <input type="radio" v-model="syringePreset" :value="0.5" name="syringeSize" />
                <span class="pv-radio-content">
                  <strong>0.5 mL</strong>
                  <small>{{ Math.round(syringeType * 0.5) }} Units</small>
                </span>
              </label>
              <label class="pv-radio-card" :class="{ active: syringePreset === 0.3 }">
                <input type="radio" v-model="syringePreset" :value="0.3" name="syringeSize" />
                <span class="pv-radio-content">
                  <strong>0.3 mL</strong>
                  <small>{{ Math.round(syringeType * 0.3) }} Units</small>
                </span>
              </label>
              <label class="pv-radio-card" :class="{ active: syringePreset === 0 }">
                <input type="radio" v-model="syringePreset" :value="0" name="syringeSize" />
                <span class="pv-radio-content">
                  <strong>Custom</strong>
                  <small>Input size</small>
                </span>
              </label>
            </div>
            
            <div v-if="syringePreset === 0" class="pv-input-with-suffix" style="margin-top: 1rem;">
              <input type="number" v-model.number="customSyringeSize" min="0.1" step="0.1" placeholder="e.g. 2" />
              <span class="pv-suffix">mL</span>
            </div>
          </div>

          <div class="pv-form-group">
            <label>Peptide Amount (Vial Size)</label>
            <div class="pv-input-with-suffix">
              <input type="number" v-model.number="peptideAmount" min="0.1" step="0.1" placeholder="e.g. 10" />
              <span class="pv-suffix">mg</span>
            </div>
          </div>

          <div class="pv-form-group">
            <label>Bacteriostatic Water Added</label>
            <div class="pv-input-with-suffix">
              <input type="number" v-model.number="waterAmount" min="0.1" step="0.1" placeholder="e.g. 2" />
              <span class="pv-suffix">mL</span>
            </div>
          </div>

          <div class="pv-form-group">
            <label>Desired Dose</label>
            <div class="pv-dose-input-group">
              <div class="pv-input-with-suffix" style="flex: 1;">
                <input type="number" v-model.number="desiredDose" min="1" step="1" placeholder="e.g. 500" />
                <span class="pv-suffix">{{ doseUnit }}</span>
              </div>
              <select v-model="doseUnit" class="pv-select-inline">
                <option value="mcg">mcg</option>
                <option value="mg">mg</option>
              </select>
            </div>
          </div>
        </form>
      </div>

      <div class="pv-results-card">
        <h2>Calculated Results</h2>
        
        <div class="pv-results-content" v-if="isValidInput">
          
          <div v-if="exceedsVialCapacity" class="pv-alert pv-alert-error">
            <PvIcon name="alert-triangle" />
            <span><strong>Critical Error:</strong> The desired dose ({{ desiredDose }}{{ doseUnit }}) is larger than the total amount of peptide in the vial ({{ peptideAmount }}mg). Please double-check your mg vs mcg selection.</span>
          </div>

          <div v-else-if="exceedsSyringeCapacity" class="pv-alert pv-alert-error">
            <PvIcon name="alert-triangle" />
            <span><strong>Critical Error:</strong> The required draw volume ({{ drawVolumeMl.toFixed(2) }} mL) exceeds your selected syringe capacity ({{ activeSyringeSize }} mL). Please increase your syringe size or concentration.</span>
          </div>

          <template v-else>
            <div class="pv-result-box primary-result">
              <span class="pv-result-label">Draw Volume (Units)</span>
              <span class="pv-result-value">{{ drawVolumeUnits.toFixed(1) }} <small>U</small></span>
            </div>

            <div class="pv-result-row">
              <div class="pv-result-box">
                <span class="pv-result-label">Volume (mL)</span>
                <span class="pv-result-value">{{ drawVolumeMl.toFixed(3) }} <small>mL</small></span>
              </div>
              
              <div class="pv-result-box">
                <span class="pv-result-label">Concentration</span>
                <span class="pv-result-value">{{ concentrationMgPerMl.toFixed(2) }} <small>mg/mL</small></span>
              </div>
            </div>
          </template>

        </div>
        <div class="pv-results-empty" v-else>
          <PvIcon name="calculator" class="pv-empty-icon" />
          <p>Please fill out all fields with valid numbers to see your results.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import PvIcon from '@/components/peptide/PvIcon.vue'

const syringeType = ref(100) // 100 for U-100, 40 for U-40
const syringePreset = ref(1) // 1, 0.5, 0.3, or 0 (custom)
const customSyringeSize = ref(2)
const peptideAmount = ref<number | null>(10)
const waterAmount = ref<number | null>(2)
const desiredDose = ref<number | null>(500)
const doseUnit = ref<'mcg' | 'mg'>('mcg')

const activeSyringeSize = computed(() => {
  return syringePreset.value === 0 ? customSyringeSize.value : syringePreset.value
})

const isValidInput = computed(() => {
  return (
    activeSyringeSize.value > 0 &&
    peptideAmount.value && peptideAmount.value > 0 &&
    waterAmount.value && waterAmount.value > 0 &&
    desiredDose.value && desiredDose.value > 0
  )
})

const desiredDoseMcg = computed(() => {
  if (!desiredDose.value) return 0
  return doseUnit.value === 'mg' ? desiredDose.value * 1000 : desiredDose.value
})

const totalPeptideMcg = computed(() => {
  return (peptideAmount.value || 0) * 1000
})

const exceedsVialCapacity = computed(() => {
  return desiredDoseMcg.value > totalPeptideMcg.value
})

const concentrationMcgPerMl = computed(() => {
  if (!waterAmount.value) return 0
  return totalPeptideMcg.value / waterAmount.value
})

const concentrationMgPerMl = computed(() => {
  return concentrationMcgPerMl.value / 1000
})

const drawVolumeMl = computed(() => {
  if (concentrationMcgPerMl.value === 0) return 0
  return desiredDoseMcg.value / concentrationMcgPerMl.value
})

// Calculate units based on the selected syringe type (U-100 or U-40)
const drawVolumeUnits = computed(() => {
  return drawVolumeMl.value * syringeType.value
})

const exceedsSyringeCapacity = computed(() => {
  return drawVolumeMl.value > activeSyringeSize.value
})
</script>

<style scoped>
.pv-calculator-page {
  padding: 1rem;
  max-width: 1200px;
  margin: 0 auto;
}

@media (min-width: 768px) {
  .pv-calculator-page {
    padding: 2rem;
  }
}

.pv-page-header {
  margin-bottom: 2rem;
}

.pv-page-header h1 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #fff 0%, #a3a7b6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.pv-page-header p {
  color: var(--pv-muted);
  font-size: 1.1rem;
}

.pv-content-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
}

@media (min-width: 768px) {
  .pv-content-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.pv-calculator-card, .pv-results-card {
  background: var(--pv-panel);
  border: 1px solid var(--pv-border);
  border-radius: var(--pv-radius);
  padding: 1.25rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

@media (min-width: 768px) {
  .pv-calculator-card, .pv-results-card {
    padding: 2rem;
  }
}

.pv-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.pv-form-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.pv-form-group label {
  font-weight: 600;
  color: var(--pv-text);
}

.pv-syringe-options {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

@media (min-width: 480px) {
  .pv-syringe-options {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .pv-syringe-options {
    grid-template-columns: repeat(4, 1fr);
  }
}

.pv-syringe-type-options {
  grid-template-columns: repeat(2, 1fr);
}

@media (max-width: 350px) {
  .pv-syringe-type-options {
    grid-template-columns: 1fr;
  }
}

.pv-radio-card {
  display: flex;
  position: relative;
  cursor: pointer;
}

.pv-radio-card input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.pv-radio-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(3, 7, 14, 0.7);
  border: 1px solid var(--pv-border);
  border-radius: 8px;
  transition: all 0.2s ease;
  text-align: center;
}

.pv-radio-card:hover .pv-radio-content {
  border-color: var(--pv-border-strong);
}

.pv-radio-card.active .pv-radio-content {
  border-color: var(--pv-purple);
  background: var(--pv-purple-soft);
}

.pv-radio-content strong {
  font-size: 1.1rem;
  color: var(--pv-text);
  margin-bottom: 0.25rem;
}

.pv-radio-content small {
  color: var(--pv-muted);
  font-size: 0.85rem;
}

.pv-input-with-suffix {
  position: relative;
  display: flex;
  align-items: center;
}

.pv-input-with-suffix input {
  padding: 0.75rem 1rem;
  padding-right: 3.5rem;
  font-size: 1.1rem;
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

.pv-suffix {
  position: absolute;
  right: 1rem;
  color: var(--pv-muted);
  font-weight: 500;
  pointer-events: none;
}

.pv-dose-input-group {
  display: flex;
  gap: 1rem;
}

.pv-select-inline {
  background: rgba(3, 7, 14, 0.7);
  border: 1px solid var(--pv-border);
  border-radius: 6px;
  color: var(--pv-text);
  padding: 0 1rem;
  font-size: 1rem;
  outline: 0;
  cursor: pointer;
}

.pv-select-inline:focus {
  border-color: var(--pv-purple);
}

.pv-results-card h2 {
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
  margin-top: 0;
  color: var(--pv-text);
}

.pv-results-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 3rem 1rem;
  color: var(--pv-muted);
}

.pv-empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.pv-results-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.pv-result-box {
  background: rgba(3, 7, 14, 0.5);
  border: 1px solid var(--pv-border);
  border-radius: 8px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.pv-result-box.primary-result {
  background: linear-gradient(135deg, rgba(124, 58, 237, 0.1) 0%, rgba(14, 165, 233, 0.1) 100%);
  border-color: var(--pv-border-strong);
  padding: 2.5rem 1.5rem;
}

.pv-result-label {
  color: var(--pv-muted);
  font-size: 0.95rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.primary-result .pv-result-value {
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--pv-purple);
  line-height: 1;
  text-shadow: 0 0 20px rgba(124, 58, 237, 0.4);
}

@media (min-width: 768px) {
  .primary-result .pv-result-value {
    font-size: 3.5rem;
  }
}

.primary-result .pv-result-value small {
  font-size: 1.5rem;
  color: var(--pv-text);
  font-weight: 600;
}

.pv-result-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

@media (min-width: 480px) {
  .pv-result-row {
    grid-template-columns: 1fr 1fr;
  }
}

.pv-result-row .pv-result-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--pv-blue);
}

.pv-result-row .pv-result-value small {
  font-size: 1rem;
  color: var(--pv-muted);
  font-weight: 500;
}

.pv-alert {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 8px;
}

.pv-alert-warning {
  background: rgba(245, 184, 46, 0.1);
  border: 1px solid rgba(245, 184, 46, 0.3);
  color: var(--pv-amber);
}

.pv-alert-error {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #ef4444;
}

.pv-alert :deep(svg) {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
}
</style>
