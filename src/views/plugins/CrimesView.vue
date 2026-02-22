<template>
  <div class="crimes-view">
    <h2 class="view-title">SELECT A LOCATION TO CONTINUE</h2>

    <div class="locations-grid">
      <div class="location-card" @click="selectLocation(1)">
        <div class="location-bg street"></div>
        <div class="location-info">
          <h3 class="location-name">STREET CORNER</h3>
          <div class="location-badges">
            <span class="energy-badge">☢️ 1</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation(2)">
        <div class="location-bg alley"></div>
        <div class="location-info">
          <h3 class="location-name">DARK ALLEY</h3>
          <div class="location-badges">
            <span class="energy-badge">☢️ 1</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation(3)">
        <div class="location-bg store"></div>
        <div class="location-info">
          <h3 class="location-name">CONVENIENCE STORE</h3>
          <div class="location-badges">
            <span class="energy-badge">☢️ 2</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation(4)">
        <div class="location-bg residential"></div>
        <div class="location-info">
          <h3 class="location-name">RESIDENTIAL AREA</h3>
          <div class="location-badges">
            <span class="energy-badge">☢️ 2</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation(5)">
        <div class="location-bg warehouse"></div>
        <div class="location-info">
          <h3 class="location-name">WAREHOUSE DISTRICT</h3>
          <div class="location-badges">
            <span class="energy-badge">☢️ 3</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation(6)">
        <div class="location-bg downtown"></div>
        <div class="location-info">
          <h3 class="location-name">DOWNTOWN BANK</h3>
          <div class="location-badges">
            <span class="energy-badge">☢️ 3</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation(7)">
        <div class="location-bg casino"></div>
        <div class="location-info">
          <h3 class="location-name">CASINO</h3>
          <div class="location-badges">
            <span class="min-level">MIN LEVEL 25</span>
            <span class="energy-badge">☢️ 5</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation(8)">
        <div class="location-bg mansion"></div>
        <div class="location-info">
          <h3 class="location-name">MANSION DISTRICT</h3>
          <div class="location-badges">
            <span class="min-level">MIN LEVEL 45</span>
            <span class="energy-badge">☢️ 5</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

const selectLocation = (id) => {
  router.push(`/crimes/${id}`)
}
</script>

<style scoped>
.crimes-view {
  max-width: 1400px;
  margin: 0 auto;
}

.view-title {
  text-align: center;
  font-size: 1.125rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  color: #cbd5e1;
}

.locations-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.location-card {
  position: relative;
  height: 180px;
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.5rem;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s;
}

.location-card:hover {
  transform: translateY(-4px);
  border-color: #00bcd4;
  box-shadow: 0 8px 24px rgba(0, 188, 212, 0.2);
}

.location-bg {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  opacity: 0.4;
  transition: opacity 0.3s;
}

.location-card:hover .location-bg {
  opacity: 0.6;
}

.location-bg.street {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
}

.location-bg.alley {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
}

.location-bg.store {
  background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
}

.location-bg.residential {
  background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
}

.location-bg.warehouse {
  background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
}

.location-bg.downtown {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
}

.location-bg.casino {
  background: linear-gradient(135deg, #ca8a04 0%, #a16207 100%);
}

.location-bg.mansion {
  background: linear-gradient(135deg, #7c2d12 0%, #431407 100%);
}

.location-info {
  position: relative;
  z-index: 1;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.5rem;
}

.location-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
}

.location-badges {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  align-self: flex-end;
}

.energy-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.625rem;
  background: rgba(15, 20, 25, 0.8);
  border-radius: 0.25rem;
  font-weight: 600;
  font-size: 0.875rem;
  color: #10b981;
}

.min-level {
  font-size: 0.625rem;
  font-weight: 600;
  color: #ef4444;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

@media (max-width: 768px) {
  .locations-grid {
    grid-template-columns: 1fr;
  }
}
</style>
