<template>
  <nav class="main-nav">
    <router-link to="/home" class="nav-item" active-class="active">
      <span class="nav-icon">H</span>
      <span class="nav-label">HOME</span>
    </router-link>
    <router-link to="/city" class="nav-item" active-class="active">
      <span class="nav-icon">C</span>
      <span class="nav-label">CITY</span>
    </router-link>
    <router-link to="/inventory" class="nav-item" active-class="active">
      <span class="nav-icon">I</span>
      <span class="nav-label">INVENTORY</span>
    </router-link>
    <router-link to="/missions" class="nav-item" active-class="active">
      <span class="nav-icon">M</span>
      <span class="nav-label">MISSIONS</span>
    </router-link>
    <router-link to="/combat" class="nav-item" active-class="active">
      <span class="nav-icon">C</span>
      <span class="nav-label">COMBAT</span>
    </router-link>
    <router-link to="/crimes" class="nav-item" active-class="active">
      <span class="nav-icon">C</span>
      <span class="nav-label">CRIMES</span>
    </router-link>
    <router-link to="/travel" class="nav-item" active-class="active">
      <span class="nav-icon">T</span>
      <span class="nav-label">TRAVEL</span>
    </router-link>
    <router-link to="/skills" class="nav-item" active-class="active">
      <span class="nav-icon">S</span>
      <span class="nav-label">SKILLS</span>
    </router-link>
    <router-link to="/forums" class="nav-item" active-class="active">
      <span class="nav-icon">F</span>
      <span class="nav-label">FORUMS</span>
    </router-link>
  </nav>
</template>

<script setup lang="ts">
</script>

<style scoped>
.main-nav {
  background: rgba(30, 41, 59, 0.4);
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  padding: 0 1.5rem;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 1rem 1.25rem;
  text-decoration: none;
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  transition: all 0.2s;
  position: relative;
}

.nav-item:hover {
  color: #e2e8f0;
  background: rgba(30, 41, 59, 0.6);
}

.nav-item.active {
  color: #00bcd4;
  background: rgba(0, 188, 212, 0.1);
}

.nav-item.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: #00bcd4;
}

.nav-icon {
  font-size: 1.25rem;
}

@media (max-width: 768px) {
  .main-nav {
    overflow-x: auto;
    justify-content: flex-start;
  }

  .nav-item {
    flex-shrink: 0;
  }
}
</style>
