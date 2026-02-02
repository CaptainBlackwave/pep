<template>
  <div class="education-container">
    <h1>🎓 Education Center</h1>

    <!-- Current Stats -->
    <div class="stats-card">
      <div class="stat-item">
        <span class="stat-icon">🧠</span>
        <div>
          <span class="stat-label">Intelligence</span>
          <span class="stat-value">{{ userStats.intelligence || 0 }}</span>
        </div>
      </div>
      <div class="stat-item">
        <span class="stat-icon">💪</span>
        <div>
          <span class="stat-label">Endurance</span>
          <span class="stat-value">{{ userStats.endurance || 0 }}</span>
        </div>
      </div>
    </div>

    <!-- Current Course -->
    <div v-if="currentProgress" class="current-course-card">
      <div class="course-header">
        <div>
          <h2>📚 Currently Enrolled</h2>
          <h3>{{ currentProgress.course.title }}</h3>
          <p class="course-type">{{ currentProgress.course.type }} Course</p>
        </div>
      </div>
      
      <div class="progress-section">
        <div class="progress-bar-container">
          <div class="progress-bar" :style="{ width: progressPercentage + '%' }"></div>
        </div>
        <p class="progress-text">{{ progressPercentage }}% Complete</p>
      </div>

      <div class="time-remaining">
        <span v-if="timeRemaining > 0">
          ⏱️ Time Remaining: {{ formatTime(timeRemaining) }}
        </span>
        <span v-else class="complete-badge">✅ Course Complete!</span>
      </div>

      <div class="course-rewards">
        <h4>Rewards on Completion:</h4>
        <div class="rewards-grid">
          <div v-if="currentProgress.course.intelligence_bonus > 0" class="reward">
            <span class="reward-icon">🧠</span>
            <span>+{{ currentProgress.course.intelligence_bonus }} Intelligence</span>
          </div>
          <div v-if="currentProgress.course.endurance_bonus > 0" class="reward">
            <span class="reward-icon">💪</span>
            <span>+{{ currentProgress.course.endurance_bonus }} Endurance</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Course History -->
    <div v-if="history.length > 0" class="history-section">
      <h2>📜 Completed Courses</h2>
      <div class="history-grid">
        <div v-for="record in history" :key="record.id" class="history-card">
          <div class="history-icon">✓</div>
          <div class="history-info">
            <h4>{{ record.course.title }}</h4>
            <p class="history-date">Completed {{ formatDate(record.completed_at) }}</p>
            <div class="history-rewards">
              <span v-if="record.course.intelligence_bonus > 0">
                🧠 +{{ record.course.intelligence_bonus }}
              </span>
              <span v-if="record.course.endurance_bonus > 0">
                💪 +{{ record.course.endurance_bonus }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Available Courses -->
    <div class="courses-section">
      <h2>{{ currentProgress ? 'Other Courses' : 'Available Courses' }}</h2>
      
      <!-- Filter Buttons -->
      <div class="filter-bar">
        <button 
          @click="selectedType = 'All'" 
          :class="['filter-btn', { active: selectedType === 'All' }]"
        >
          All Courses
        </button>
        <button 
          @click="selectedType = 'Intelligence'" 
          :class="['filter-btn', { active: selectedType === 'Intelligence' }]"
        >
          🧠 Intelligence
        </button>
        <button 
          @click="selectedType = 'Endurance'" 
          :class="['filter-btn', { active: selectedType === 'Endurance' }]"
        >
          💪 Endurance
        </button>
        <button 
          @click="selectedType = 'Mixed'" 
          :class="['filter-btn', { active: selectedType === 'Mixed' }]"
        >
          🎯 Mixed
        </button>
      </div>

      <div class="courses-grid">
        <div v-for="course in filteredCourses" :key="course.id" class="course-card">
          <div class="course-badge">{{ getCourseIcon(course.type) }}</div>
          <h3>{{ course.title }}</h3>
          <p class="course-description">{{ course.description }}</p>
          
          <div class="course-details">
            <div class="detail">
              <span class="detail-label">⏱️ Duration</span>
              <span class="detail-value">{{ course.duration }} hours</span>
            </div>
            <div class="detail">
              <span class="detail-label">💰 Cost</span>
              <span class="detail-value">${{ course.cost.toLocaleString() }}</span>
            </div>
          </div>

          <div class="course-rewards">
            <h4>Rewards:</h4>
            <div class="rewards-list">
              <span v-if="course.intelligence_bonus > 0" class="reward-badge">
                🧠 +{{ course.intelligence_bonus }}
              </span>
              <span v-if="course.endurance_bonus > 0" class="reward-badge">
                💪 +{{ course.endurance_bonus }}
              </span>
            </div>
          </div>

          <button 
            @click="enrollInCourse(course.id)" 
            :disabled="currentProgress || enrolling === course.id"
            class="btn-enroll"
          >
            {{ enrolling === course.id ? 'Enrolling...' : 'Enroll Now' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const courses = ref([])
const currentProgress = ref(null)
const history = ref([])
const selectedType = ref('All')
const enrolling = ref(null)
const userStats = ref({ intelligence: 0, endurance: 0 })

const filteredCourses = computed(() => {
  if (selectedType.value === 'All') return courses.value
  return courses.value.filter(c => c.type === selectedType.value)
})

const progressPercentage = computed(() => {
  if (!currentProgress.value) return 0
  const elapsed = Date.now() - new Date(currentProgress.value.enrolled_at).getTime()
  const total = currentProgress.value.course.duration * 60 * 60 * 1000 // hours to ms
  return Math.min(Math.round((elapsed / total) * 100), 100)
})

const timeRemaining = computed(() => {
  if (!currentProgress.value) return 0
  const enrolledAt = new Date(currentProgress.value.enrolled_at).getTime()
  const duration = currentProgress.value.course.duration * 60 * 60 * 1000
  const completionTime = enrolledAt + duration
  return Math.max(completionTime - Date.now(), 0)
})

const getCourseIcon = (type) => {
  const icons = {
    'Intelligence': '🧠',
    'Endurance': '💪',
    'Mixed': '🎯'
  }
  return icons[type] || '📚'
}

const formatTime = (ms) => {
  const hours = Math.floor(ms / (1000 * 60 * 60))
  const minutes = Math.floor((ms % (1000 * 60 * 60)) / (1000 * 60))
  return `${hours}h ${minutes}m`
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

const loadCourses = async () => {
  try {
    const response = await api.get('/education/courses')
    courses.value = response.data.courses
  } catch (error) {
    console.error('Failed to load courses:', error)
  }
}

const loadProgress = async () => {
  try {
    const response = await api.get('/education/progress')
    currentProgress.value = response.data.progress
  } catch (error) {
    console.error('Failed to load progress:', error)
  }
}

const loadHistory = async () => {
  try {
    const response = await api.get('/education/history')
    history.value = response.data.history
  } catch (error) {
    console.error('Failed to load history:', error)
  }
}

const loadStats = async () => {
  try {
    const response = await api.get('/user')
    userStats.value = {
      intelligence: response.data.intelligence || 0,
      endurance: response.data.endurance || 0
    }
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

const enrollInCourse = async (courseId) => {
  enrolling.value = courseId
  try {
    const response = await api.post('/education/enroll', { course_id: courseId })
    alert(response.data.message)
    await loadProgress()
    await loadCourses()
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to enroll in course')
  } finally {
    enrolling.value = null
  }
}

onMounted(() => {
  loadCourses()
  loadProgress()
  loadHistory()
  loadStats()
  
  // Refresh progress every minute
  setInterval(() => {
    if (currentProgress.value) {
      loadProgress()
    }
  }, 60000)
})
</script>

<style scoped>
.education-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

h1 {
  color: #1e3a8a;
  margin-bottom: 2rem;
  font-size: 2.5rem;
}

.stats-card {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-item {
  background: white;
  padding: 1.5rem;
  border-radius: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  font-size: 2.5rem;
}

.stat-label {
  display: block;
  color: #6b7280;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  display: block;
  color: #1e3a8a;
  font-size: 2rem;
  font-weight: bold;
}

.current-course-card {
  background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
  color: white;
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 3rem;
  box-shadow: 0 8px 16px rgba(124, 58, 237, 0.3);
}

.course-header h2 {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1rem;
  margin: 0 0 0.5rem 0;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.course-header h3 {
  color: white;
  font-size: 2rem;
  margin: 0 0 0.5rem 0;
}

.course-type {
  color: rgba(255, 255, 255, 0.8);
  font-size: 1.1rem;
  margin: 0 0 2rem 0;
}

.progress-section {
  margin-bottom: 1.5rem;
}

.progress-bar-container {
  width: 100%;
  height: 2rem;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 1rem;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
  transition: width 0.3s ease;
  border-radius: 1rem;
}

.progress-text {
  color: white;
  text-align: center;
  font-size: 1.2rem;
  font-weight: 600;
  margin: 0;
}

.time-remaining {
  text-align: center;
  font-size: 1.1rem;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 0.5rem;
}

.complete-badge {
  font-weight: bold;
  font-size: 1.3rem;
}

.course-rewards h4 {
  color: rgba(255, 255, 255, 0.9);
  margin: 0 0 1rem 0;
}

.rewards-grid {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.reward {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
}

.reward-icon {
  font-size: 1.5rem;
}

.history-section {
  margin-bottom: 3rem;
}

.history-section h2 {
  color: #1e3a8a;
  margin-bottom: 1.5rem;
}

.history-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.history-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  gap: 1rem;
  align-items: center;
}

.history-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  font-weight: bold;
  flex-shrink: 0;
}

.history-info h4 {
  color: #1e3a8a;
  margin: 0 0 0.25rem 0;
}

.history-date {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 0.5rem 0;
}

.history-rewards {
  display: flex;
  gap: 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
}

.courses-section h2 {
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
  color: #7c3aed;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.filter-btn:hover {
  border-color: #7c3aed;
}

.filter-btn.active {
  background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
  color: white;
  border-color: #7c3aed;
}

.courses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.course-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.course-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(124, 58, 237, 0.2);
}

.course-badge {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.course-card h3 {
  color: #1e3a8a;
  margin: 0 0 0.5rem 0;
}

.course-description {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 1.5rem 0;
  line-height: 1.4;
}

.course-details {
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

.detail-label {
  color: #6b7280;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  color: #1f2937;
  font-weight: 600;
}

.course-rewards h4 {
  color: #1e3a8a;
  font-size: 0.875rem;
  margin: 0 0 0.5rem 0;
}

.rewards-list {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.reward-badge {
  background: #f3e8ff;
  color: #7c3aed;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.btn-enroll {
  width: 100%;
  padding: 0.75rem;
  background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 1rem;
}

.btn-enroll:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(124, 58, 237, 0.4);
}

.btn-enroll:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
