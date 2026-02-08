<template>
  <div class="tickets-container">
    <header class="tickets-header">
      <h1>Support Tickets</h1>
      <button @click="showCreateModal = true" class="btn-primary">
        <span>✉️</span>
        Create Ticket
      </button>
    </header>

    <!-- Ticket Stats -->
    <div class="ticket-stats">
      <div class="stat-card">
        <div class="stat-value">{{ tickets.filter(t => t.status === 'open').length }}</div>
        <div class="stat-label">Open</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ tickets.filter(t => t.status === 'in_progress').length }}</div>
        <div class="stat-label">In Progress</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ tickets.filter(t => t.status === 'resolved').length }}</div>
        <div class="stat-label">Resolved</div>
      </div>
    </div>

    <!-- Tickets List -->
    <div v-if="loading" class="loading">Loading tickets...</div>
    <div v-else-if="error" class="error-message">{{ error }}</div>
    <div v-else-if="tickets.length === 0" class="empty-state">
      <span class="empty-icon">📭</span>
      <p>No tickets yet</p>
      <button @click="showCreateModal = true" class="btn-secondary">Create your first ticket</button>
    </div>
    <div v-else class="tickets-list">
      <div
        v-for="ticket in tickets"
        :key="ticket.id"
        class="ticket-card"
        @click="viewTicket(ticket.id)"
      >
        <div class="ticket-header">
          <div class="ticket-priority" :class="`priority-${ticket.priority}`">
            {{ ticket.priority }}
          </div>
          <div class="ticket-status" :class="`status-${ticket.status}`">
            {{ ticket.status.replace('_', ' ') }}
          </div>
        </div>
        <h3 class="ticket-subject">{{ ticket.subject }}</h3>
        <p class="ticket-description">{{ truncateText(ticket.description, 100) }}</p>
        <div class="ticket-footer">
          <span class="ticket-category">{{ ticket.category?.name || 'General' }}</span>
          <span class="ticket-date">{{ formatDate(ticket.created_at) }}</span>
          <span v-if="ticket.messages_count" class="ticket-replies">
            {{ ticket.messages_count }} replies
          </span>
        </div>
      </div>
    </div>

    <!-- Create Ticket Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Create Support Ticket</h2>
          <button @click="showCreateModal = false" class="modal-close">×</button>
        </div>
        <form @submit.prevent="createTicket" class="modal-body">
          <div class="form-group">
            <label>Category</label>
            <select v-model="newTicket.ticket_category_id" required>
              <option value="">Select a category</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Subject</label>
            <input v-model="newTicket.subject" type="text" required maxlength="255" />
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="newTicket.description" rows="6" required></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" @click="showCreateModal = false" class="btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="submitting">
              {{ submitting ? 'Creating...' : 'Create Ticket' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Ticket Modal -->
    <div v-if="selectedTicket" class="modal-overlay" @click.self="selectedTicket = null">
      <div class="modal-content large">
        <div class="modal-header">
          <div>
            <h2>{{ selectedTicket.subject }}</h2>
            <div class="ticket-meta">
              <span class="ticket-priority" :class="`priority-${selectedTicket.priority}`">
                {{ selectedTicket.priority }}
              </span>
              <span class="ticket-status" :class="`status-${selectedTicket.status}`">
                {{ selectedTicket.status.replace('_', ' ') }}
              </span>
            </div>
          </div>
          <button @click="selectedTicket = null" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="ticket-detail">
            <div class="ticket-info">
              <strong>Category:</strong> {{ selectedTicket.category?.name || 'General' }}<br>
              <strong>Created:</strong> {{ formatDate(selectedTicket.created_at) }}
            </div>
            <div class="ticket-description-full">
              {{ selectedTicket.description }}
            </div>
          </div>

          <!-- Messages -->
          <div class="ticket-messages">
            <h3>Messages</h3>
            <div
              v-for="message in selectedTicket.messages"
              :key="message.id"
              class="message"
              :class="{ 'staff-message': message.is_staff || message.is_staff_reply }"
            >
              <div class="message-header">
                <strong>{{ message.user?.username || 'Staff' }}</strong>
                <span class="message-time">{{ formatDate(message.created_at) }}</span>
              </div>
              <div class="message-body">{{ message.message }}</div>
            </div>
          </div>

          <!-- Reply Form -->
          <form v-if="selectedTicket.status !== 'closed'" @submit.prevent="replyToTicket" class="reply-form">
            <textarea
              v-model="replyMessage"
              placeholder="Type your reply..."
              rows="3"
              required
            ></textarea>
            <div class="reply-actions">
              <button type="submit" class="btn-primary" :disabled="submitting">
                {{ submitting ? 'Sending...' : 'Send Reply' }}
              </button>
              <button
                v-if="selectedTicket.status !== 'closed'"
                type="button"
                @click="closeTicket"
                class="btn-danger"
              >
                Close Ticket
              </button>
            </div>
          </form>
          <div v-else class="ticket-closed-notice">
            This ticket is closed. Create a new ticket if you need further assistance.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const error = ref(null)
const tickets = ref([])
const categories = ref([])
const showCreateModal = ref(false)
const selectedTicket = ref(null)
const submitting = ref(false)
const replyMessage = ref('')

const newTicket = ref({
  ticket_category_id: '',
  subject: '',
  description: ''
})

async function loadTickets() {
  try {
    loading.value = true
    const response = await api.get('/tickets')
    tickets.value = response.data
  } catch (err) {
    error.value = 'Failed to load tickets'
    console.error(err)
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const response = await api.get('/tickets/categories')
    categories.value = response.data
  } catch (err) {
    console.error('Failed to load categories:', err)
  }
}

async function createTicket() {
  try {
    submitting.value = true
    await api.post('/tickets', newTicket.value)
    showCreateModal.value = false
    newTicket.value = { ticket_category_id: '', subject: '', description: '' }
    await loadTickets()
  } catch (err) {
    error.value = 'Failed to create ticket'
    console.error(err)
  } finally {
    submitting.value = false
  }
}

async function viewTicket(id) {
  try {
    const response = await api.get(`/tickets/${id}`)
    selectedTicket.value = response.data
  } catch (err) {
    error.value = 'Failed to load ticket details'
    console.error(err)
  }
}

async function replyToTicket() {
  if (!replyMessage.value.trim()) return

  try {
    submitting.value = true
    await api.post(`/tickets/${selectedTicket.value.id}/reply`, {
      message: replyMessage.value
    })
    replyMessage.value = ''
    await viewTicket(selectedTicket.value.id)
  } catch (err) {
    error.value = 'Failed to send reply'
    console.error(err)
  } finally {
    submitting.value = false
  }
}

async function closeTicket() {
  if (!confirm('Are you sure you want to close this ticket?')) return

  try {
    await api.post(`/tickets/${selectedTicket.value.id}/close`)
    selectedTicket.value = null
    await loadTickets()
  } catch (err) {
    error.value = 'Failed to close ticket'
    console.error(err)
  }
}

function truncateText(text, length) {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadTickets()
  loadCategories()
})
</script>

<style scoped>
.tickets-container {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.tickets-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.tickets-header h1 {
  color: #fff;
  font-size: 2rem;
  margin: 0;
}

.ticket-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 0.75rem;
  padding: 1rem;
  text-align: center;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #3b82f6;
}

.stat-label {
  font-size: 0.875rem;
  color: #94a3b8;
  text-transform: uppercase;
}

.tickets-list {
  display: grid;
  gap: 1rem;
}

.ticket-card {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 0.75rem;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.ticket-card:hover {
  border-color: #3b82f6;
  transform: translateY(-2px);
}

.ticket-header {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.ticket-priority, .ticket-status {
  padding: 0.25rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.priority-low { background: #6b7280; color: #fff; }
.priority-medium { background: #f59e0b; color: #fff; }
.priority-high { background: #ef4444; color: #fff; }
.priority-urgent { background: #dc2626; color: #fff; animation: pulse 2s infinite; }

.status-open { background: #10b981; color: #fff; }
.status-in_progress { background: #3b82f6; color: #fff; }
.status-resolved { background: #8b5cf6; color: #fff; }
.status-closed { background: #6b7280; color: #fff; }

.ticket-subject {
  color: #fff;
  font-size: 1.25rem;
  margin: 0 0 0.5rem 0;
}

.ticket-description {
  color: #94a3b8;
  margin: 0 0 1rem 0;
}

.ticket-footer {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #64748b;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: #1e293b;
  border-radius: 1rem;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content.large {
  max-width: 800px;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.modal-header h2 {
  color: #fff;
  margin: 0;
  font-size: 1.5rem;
}

.ticket-meta {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.modal-close {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 2rem;
  cursor: pointer;
  line-height: 1;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  color: #94a3b8;
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.form-group input, .form-group textarea, .form-group select {
  width: 100%;
  padding: 0.75rem;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.5rem;
  color: #fff;
  font-family: inherit;
}

.modal-footer {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

.btn-primary, .btn-secondary, .btn-danger {
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-primary {
  background: #3b82f6;
  color: #fff;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: rgba(148, 163, 184, 0.1);
  color: #94a3b8;
}

.btn-secondary:hover {
  background: rgba(148, 163, 184, 0.2);
}

.btn-danger {
  background: #ef4444;
  color: #fff;
}

.btn-danger:hover {
  background: #dc2626;
}

.ticket-messages {
  margin-top: 2rem;
}

.ticket-messages h3 {
  color: #fff;
  margin-bottom: 1rem;
}

.message {
  background: rgba(15, 23, 42, 0.5);
  border-left: 3px solid #3b82f6;
  padding: 1rem;
  margin-bottom: 1rem;
  border-radius: 0.5rem;
}

.message.staff-message {
  background: rgba(59, 130, 246, 0.1);
  border-left-color: #10b981;
}

.message-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  color: #94a3b8;
  font-size: 0.875rem;
}

.message-body {
  color: #fff;
  white-space: pre-wrap;
}

.reply-form {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
}

.reply-form textarea {
  width: 100%;
  padding: 0.75rem;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 0.5rem;
  color: #fff;
  font-family: inherit;
  margin-bottom: 1rem;
}

.reply-actions {
  display: flex;
  gap: 1rem;
}

.loading, .error-message, .empty-state {
  text-align: center;
  padding: 3rem;
  color: #94a3b8;
}

.empty-icon {
  font-size: 4rem;
  display: block;
  margin-bottom: 1rem;
}

.ticket-closed-notice {
  background: rgba(107, 114, 128, 0.2);
  padding: 1rem;
  border-radius: 0.5rem;
  color: #94a3b8;
  text-align: center;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>
