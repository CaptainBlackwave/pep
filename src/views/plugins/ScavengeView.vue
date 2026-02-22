<template>
  <div class="scavenge-view">
    <h2 class="view-title">SELECT A LOCATION TO CONTINUE</h2>

    <div class="locations-grid">
      <div class="location-card" @click="selectLocation('Abandoned Cabin')">
        <div class="location-bg abandoned-cabin"></div>
        <div class="location-info">
          <h3 class="location-name">ABANDONED CABIN</h3>
          <div class="location-stats">
            <span class="energy-cost">☢️ 1</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation('Scrapyard')">
        <div class="location-bg scrapyard"></div>
        <div class="location-info">
          <h3 class="location-name">SCRAPYARD</h3>
          <div class="location-stats">
            <span class="energy-cost">☢️ 1</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation('Coal Mine')">
        <div class="location-bg coal-mine"></div>
        <div class="location-info">
          <h3 class="location-name">COAL MINE</h3>
          <div class="location-stats">
            <span class="energy-cost">☢️ 1</span>
            <span class="progress-indicator">86%</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation('Iron Mine')">
        <div class="location-bg iron-mine"></div>
        <div class="location-info">
          <h3 class="location-name">IRON MINE</h3>
          <div class="location-stats">
            <span class="energy-cost">☢️ 2</span>
            <span class="item-count">🎒 2</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation('Forest')">
        <div class="location-bg forest"></div>
        <div class="location-info">
          <h3 class="location-name">FOREST</h3>
          <div class="location-stats">
            <span class="energy-cost">☢️ 2</span>
            <span class="item-count">🎒 2</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation('Hunters Shack')">
        <div class="location-bg hunters-shack"></div>
        <div class="location-info">
          <h3 class="location-name">HUNTERS SHACK</h3>
          <div class="location-stats">
            <span class="energy-cost">☢️ 2</span>
            <span class="item-count">🎒 2</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation('Marshlands')">
        <div class="location-bg marshlands"></div>
        <div class="location-info">
          <h3 class="location-name">MARSHLANDS</h3>
          <div class="location-stats">
            <span class="energy-cost">☢️ 3</span>
            <span class="item-count">🎒 3</span>
          </div>
        </div>
      </div>

      <div class="location-card" @click="selectLocation('Lake')">
        <div class="location-bg lake"></div>
        <div class="location-info">
          <h3 class="location-name">LAKE</h3>
          <div class="location-stats">
            <span class="energy-cost">☢️ 3</span>
            <span class="item-count">🎒 3</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const selectLocation = (name) => {
  console.log('Selected location:', name)
}
</script>

<style scoped>
.scavenge-view {
  max-width: 1400px;
  margin: 0 auto;
}

.view-title {
  text-align: center;
  font-size: 1.125rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  color: #cbd5e1;
  margin-bottom: 2rem;
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

.location-bg.abandoned-cabin {
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
}

.location-bg.scrapyard {
  background: linear-gradient(135deg, #334155 0%, #475569 100%);
}

.location-bg.coal-mine {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
}

.location-bg.iron-mine {
  background: linear-gradient(135deg, #475569 0%, #334155 100%);
}

.location-bg.forest {
  background: linear-gradient(135deg, #065f46 0%, #047857 100%);
}

.location-bg.hunters-shack {
  background: linear-gradient(135deg, #78350f 0%, #92400e 100%);
}

.location-bg.marshlands {
  background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
}

.location-bg.lake {
  background: linear-gradient(135deg, #0e7490 0%, #0891b2 100%);
}

.location-info {
  position: relative;
  z-index: 1;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 1.5rem;
}

.location-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 0.5rem 0;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
}

.location-stats {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
}

.energy-cost,
.item-count,
.progress-indicator {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.625rem;
  background: rgba(15, 20, 25, 0.8);
  border-radius: 0.25rem;
  color: #10b981;
  font-weight: 600;
}

.progress-indicator {
  color: #f59e0b;
}

@media (max-width: 768px) {
  .locations-grid {
    grid-template-columns: 1fr;
  }
}
</style>
