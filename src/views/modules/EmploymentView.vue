<template>
  <div class="employment-container">
    <h1>💼 Employment Center</h1>

    <!-- Current Job Section -->
    <div v-if="currentJob" class="current-job-card">
      <div class="job-header">
        <div>
          <h2>Current Position</h2>
          <h3>{{ currentJob.position?.title }}</h3>
          <p class="company-name">{{ currentJob.company?.name }}</p>
        </div>
        <button @click="quitJob" class="btn-quit">Quit Job</button>
      </div>
      <div class="job-stats">
        <div class="stat">
          <span class="label">Salary</span>
          <span class="value">${{ (currentJob.salary || 0).toLocaleString() }}</span>
        </div>
        <div class="stat">
          <span class="label">Shifts Worked</span>
          <span class="value">{{ currentJob.total_days_worked || 0 }}</span>
        </div>
        <div class="stat">
          <span class="label">Performance</span>
          <span class="value">{{ (currentJob.performance_rating || 0).toFixed(1) }}</span>
        </div>
        <div class="stat">
          <span class="label">Total Earned</span>
          <span class="value">${{ (currentJob.total_earned || 0).toLocaleString() }}</span>
        </div>
      </div>
      <button @click="work" :disabled="working || hasWorkedToday" class="btn-work">
        {{ working ? 'Working...' : hasWorkedToday ? 'Already Worked Today' : '⚡ Work Shift' }}
      </button>
      <p v-if="workMessage" :class="['work-message', workSuccess ? 'success' : 'error']">
        {{ workMessage }}
      </p>
    </div>

    <!-- Job Listings -->
    <div class="jobs-section">
      <h2>{{ currentJob ? 'Other Opportunities' : 'Available Positions' }}</h2>
      
      <!-- Sector Filter -->
      <div class="filter-bar">
        <button 
          v-for="sector in sectors" 
          :key="sector"
          @click="selectedSector = sector"
          :class="['filter-btn', { active: selectedSector === sector }]"
        >
          {{ sector }}
        </button>
      </div>

      <div class="jobs-grid">
        <div v-for="position in filteredPositions" :key="position.id" class="job-card">
          <div class="job-icon">{{ getSectorIcon(position.company?.type) }}</div>
          <div class="job-info">
            <h3>{{ position.title }}</h3>
            <p class="company">{{ position.company?.name }}</p>
            <p class="description">{{ position.description }}</p>
            
            <div class="requirements">
              <div class="req">
                <span class="req-label">Required Stats:</span>
                <span class="req-value">Level {{ position.required_level || 1 }}</span>
              </div>
            </div>
            
            <div class="job-details">
              <div class="detail">
                <span class="label">💰 Salary</span>
                <span class="value">${{ (position.base_salary || 0).toLocaleString() }}</span>
              </div>
              <div class="detail">
                <span class="label">📊 Level</span>
                <span class="value">{{ position.required_level || 1 }}</span>
              </div>
            </div>
            
            <button 
              @click="applyForJob(position.id)" 
              :disabled="currentJob || applying === position.id"
              class="btn-apply"
            >
              {{ applying === position.id ? 'Applying...' : 'Apply Now' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const positions = ref([])
const currentJob = ref(null)
const selectedSector = ref('All')
const applying = ref(null)
const working = ref(false)
const hasWorkedToday = ref(false)
const workMessage = ref('')
const workSuccess = ref(false)

const sectors = computed(() => {
  const sectorSet = new Set(['All'])
  positions.value.forEach(p => sectorSet.add(p.company.type))
  return Array.from(sectorSet)
})

const filteredPositions = computed(() => {
  if (selectedSector.value === 'All') return positions.value
  return positions.value.filter(p => p.company.type === selectedSector.value)
})

const getSectorIcon = (sector) => {
  const icons = {
    'Medical': '🏥',
    'Law': '⚖️',
    'Technology': '💻',
    'Casino': '🎰',
    'Security': '🛡️'
  }
  return icons[sector] || '💼'
}

const loadPositions = async () => {
  try {
    const response = await api.get('/employment/positions')
    positions.value = response.data.positions
  } catch (error) {
    console.error('Failed to load positions:', error)
  }
}

const loadCurrentJob = async () => {
  try {
    const response = await api.get('/employment/current')
    if (response.data.employment) {
      currentJob.value = response.data.employment
      hasWorkedToday.value = response.data.has_worked_today || false
    }
  } catch (error) {
    console.error('Failed to load current job:', error)
  }
}

const applyForJob = async (positionId) => {
  applying.value = positionId
  try {
    const response = await api.post('/employment/apply', { position_id: positionId })
    alert(response.data.message)
    await loadCurrentJob()
    await loadPositions()
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to apply for job')
  } finally {
    applying.value = null
  }
}

const work = async () => {
  working.value = true
  workMessage.value = ''
  try {
    const response = await api.post('/employment/work')
    workMessage.value = response.data.message
    workSuccess.value = true
    hasWorkedToday.value = true
    await loadCurrentJob()
  } catch (error) {
    workMessage.value = error.response?.data?.message || 'Failed to complete work shift'
    workSuccess.value = false
  } finally {
    working.value = false
  }
}

const quitJob = async () => {
  if (!confirm('Are you sure you want to quit your job?')) return
  
  try {
    const response = await api.post('/employment/quit')
    alert(response.data.message)
    currentJob.value = null
    hasWorkedToday.value = false
    await loadPositions()
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to quit job')
  }
}

onMounted(() => {
  loadPositions()
  loadCurrentJob()
})
</script>

<style scoped>
.employment-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

h1 {
  color: #1e3a8a;
  margin-bottom: 2rem;
  font-size: 2.5rem;
}

.current-job-card {
  background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
  color: white;
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 3rem;
  box-shadow: 0 8px 16px rgba(30, 58, 138, 0.3);
}

.job-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.job-header h2 {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1rem;
  margin: 0 0 0.5rem 0;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.job-header h3 {
  color: white;
  font-size: 2rem;
  margin: 0 0 0.5rem 0;
}

.company-name {
  color: rgba(255, 255, 255, 0.8);
  font-size: 1.1rem;
  margin: 0;
}

.job-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stat .label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat .value {
  color: white;
  font-size: 1.5rem;
  font-weight: bold;
}

.btn-work {
  width: 100%;
  padding: 1rem;
  background: white;
  color: #1e3a8a;
  border: none;
  border-radius: 0.5rem;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-work:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.btn-work:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-quit {
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 2px solid white;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-quit:hover {
  background: rgba(255, 255, 255, 0.3);
}

.work-message {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 0.5rem;
  text-align: center;
  font-weight: 600;
}

.work-message.success {
  background: rgba(34, 197, 94, 0.2);
  border: 2px solid rgba(34, 197, 94, 0.5);
}

.work-message.error {
  background: rgba(239, 68, 68, 0.2);
  border: 2px solid rgba(239, 68, 68, 0.5);
}

.jobs-section h2 {
  color: #1e3a8a;
  margin-bottom: 1.5rem;
}

.filter-bar {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 0.5rem 1rem;
  background: white;
  color: #1e3a8a;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.filter-btn:hover {
  border-color: #3b82f6;
  color: #3b82f6;
}

.filter-btn.active {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border-color: #3b82f6;
}

.jobs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.job-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  display: flex;
  gap: 1rem;
}

.job-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(59, 130, 246, 0.2);
}

.job-icon {
  font-size: 3rem;
  flex-shrink: 0;
}

.job-info {
  flex: 1;
}

.job-info h3 {
  color: #1e3a8a;
  margin: 0 0 0.25rem 0;
  font-size: 1.25rem;
}

.company {
  color: #3b82f6;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.description {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 1rem 0;
  line-height: 1.4;
}

.requirements {
  margin-bottom: 1rem;
}

.req {
  display: flex;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.req-label {
  color: #6b7280;
  font-weight: 600;
}

.req-value {
  color: #1f2937;
}

.job-details {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.detail {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail .label {
  color: #6b7280;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail .value {
  color: #1f2937;
  font-weight: 600;
  font-size: 1.1rem;
}

.btn-apply {
  width: 100%;
  padding: 0.75rem;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-apply:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-apply:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
