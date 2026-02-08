<template>
  <div class="city-view">
    <div class="city-banner">
      <div class="map-pattern"></div>
    </div>

    <div class="city-content">
      <div class="city-section">
        <h3 class="section-title">TRADING</h3>
        <div class="city-links">
          <router-link to="/shop" class="city-link">Market</router-link>
          <router-link to="/trades" class="city-link">Player Trades</router-link>
        </div>
      </div>

      <div class="city-section">
        <h3 class="section-title">INFO</h3>
        <div class="city-links">
          <router-link to="/hall-of-fame" class="city-link">Hall Of Fame</router-link>
          <router-link to="/city-stats" class="city-link">City Stats</router-link>
          <router-link to="/survivors" class="city-link">Survivors</router-link>
          <router-link to="/referrals" class="city-link">Referrals</router-link>
        </div>
      </div>

      <div class="city-section">
        <h3 class="section-title">RETAIL</h3>
        <div class="city-links">
          <router-link to="/shop/glockbuster" class="city-link">Glockbuster</router-link>
          <router-link to="/shop/junk-store" class="city-link">Junk Store</router-link>
          <router-link to="/shop/clothes-weaver" class="city-link">Clothes Weaver</router-link>
          <router-link to="/shop/toolsmith" class="city-link">Toolsmith</router-link>
          <router-link to="/shop/zed-mart" class="city-link">Zed Mart</router-link>
          <router-link to="/shop/carpenter" class="city-link">Carpenter</router-link>
          <router-link to="/shop/premium-store" class="city-link">Premium Store</router-link>
        </div>
      </div>

      <div class="city-section">
        <h3 class="section-title">FACTIONS</h3>
        <div class="city-links">
          <router-link to="/gang" class="city-link">Factions</router-link>
        </div>
      </div>

      <div class="city-section">
        <h3 class="section-title">EXPERTS</h3>
        <div class="city-links">
          <router-link to="/experts/vehicle" class="city-link">Vehicle Expert</router-link>
          <router-link to="/experts/equipment" class="city-link">Equipment Expert</router-link>
          <router-link to="/experts/armour" class="city-link">Armour Expert</router-link>
          <router-link to="/experts/weapons" class="city-link">Weapons Expert</router-link>
          <router-link to="/experts/scrap" class="city-link">Scrap Expert</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.city-view {
  max-width: 1400px;
  margin: 0 auto;
}

.city-banner {
  height: 150px;
  background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.6)),
              url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="%231e293b" width="100" height="100"/><path fill="%2310b981" opacity="0.3" d="M10,10h5v5h-5z M30,20h3v3h-3z M60,15h4v4h-4z M80,25h3v3h-3z"/></svg>');
  background-size: cover;
  border-radius: 0.5rem;
  margin-bottom: 2rem;
  border: 1px solid rgba(148, 163, 184, 0.1);
}

.map-pattern {
  width: 100%;
  height: 100%;
  background: repeating-linear-gradient(
    90deg,
    transparent,
    transparent 50px,
    rgba(16, 185, 129, 0.1) 50px,
    rgba(16, 185, 129, 0.1) 51px
  ),
  repeating-linear-gradient(
    0deg,
    transparent,
    transparent 50px,
    rgba(16, 185, 129, 0.1) 50px,
    rgba(16, 185, 129, 0.1) 51px
  );
}

.city-content {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
}

.city-section {
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 0.5rem;
  overflow: hidden;
}

.section-title {
  background: rgba(15, 20, 25, 0.8);
  padding: 0.875rem 1.25rem;
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #cbd5e1;
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}

.city-links {
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
}

.city-link {
  padding: 0.875rem 1.25rem;
  color: #e2e8f0;
  text-decoration: none;
  font-size: 0.9375rem;
  transition: all 0.2s;
  border-radius: 0.375rem;
}

.city-link:hover {
  background: rgba(0, 188, 212, 0.1);
  color: #00bcd4;
  transform: translateX(4px);
}

@media (max-width: 768px) {
  .city-content {
    grid-template-columns: 1fr;
  }
}
</style>
