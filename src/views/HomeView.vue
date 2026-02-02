<template>
  <div class="home-view">
    <div class="features-grid">
      <router-link to="/gym" class="feature-card">
        <div class="feature-icon">💪</div>
        <h3 class="feature-title">Gym</h3>
        <p class="feature-desc">Train your stats and become stronger</p>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()
</script>

<style scoped>
.home-view {
  max-width: 1400px;
  margin: 0 auto;
}

/* Features Grid */
.features-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
}

.feature-card {
  background: linear-gradient(135deg, rgba(6, 95, 134, 0.4) 0%, rgba(14, 116, 144, 0.4) 100%);
  border: 1px solid rgba(8, 145, 178, 0.3);
  border-radius: 0.5rem;
  padding: 2rem 1.5rem;
  text-decoration: none;
  color: #e2e8f0;
  transition: all 0.3s;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.feature-card:hover {
  transform: translateY(-6px);
  border-color: #0891b2;
  box-shadow: 0 12px 32px rgba(8, 145, 178, 0.3);
  background: linear-gradient(135deg, rgba(6, 95, 134, 0.6) 0%, rgba(14, 116, 144, 0.6) 100%);
}

.feature-icon {
  font-size: 3rem;
  line-height: 1;
}

.feature-title {
  font-size: 1.125rem;
  font-weight: 700;
  margin: 0;
  color: #ffffff;
}

.feature-desc {
  font-size: 0.875rem;
  color: #cbd5e1;
  margin: 0;
  line-height: 1.5;
}

/* Responsive */
@media (max-width: 1024px) {
  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .features-grid {
    grid-template-columns: 1fr;
  }
}
</style>
