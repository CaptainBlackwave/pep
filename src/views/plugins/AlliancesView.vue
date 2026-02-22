<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '@/services/api';

const alliances = ref([]);
const myAlliance = ref(null);
const pendingInvites = ref([]);
const loading = ref(true);
const processing = ref(false);
const error = ref('');
const successMessage = ref('');

const showCreateForm = ref(false);
const allianceName = ref('');
const allianceTag = ref('');
const allianceDescription = ref('');
const creationCost = ref(5000000);

const formatMoney = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0
  }).format(amount);
};

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US').format(num);
};

const isLeader = computed(() => {
  return myAlliance.value && myAlliance.value.is_leader;
});

const fetchData = async () => {
  try {
    loading.value = true;
    error.value = '';
    const response = await api.get('/api/v1/alliances');
    alliances.value = response.data.alliances || [];
    myAlliance.value = response.data.myAlliance || null;
    pendingInvites.value = response.data.pendingInvites || [];
    creationCost.value = response.data.creationCost || 5000000;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load alliance data';
  } finally {
    loading.value = false;
  }
};

const createAlliance = async () => {
  if (processing.value || !allianceName.value || !allianceTag.value) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post('/api/v1/alliances/create', {
      name: allianceName.value,
      tag: allianceTag.value,
      description: allianceDescription.value
    });

    successMessage.value = response.data.message || 'Alliance created successfully!';
    showCreateForm.value = false;
    allianceName.value = '';
    allianceTag.value = '';
    allianceDescription.value = '';
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to create alliance';
  } finally {
    processing.value = false;
  }
};

const joinAlliance = async (allianceId) => {
  if (processing.value) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post(`/api/v1/alliances/${allianceId}/join`);

    successMessage.value = response.data.message || 'Request sent!';
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to join alliance';
  } finally {
    processing.value = false;
  }
};

const leaveAlliance = async () => {
  if (processing.value || !confirm('Are you sure you want to leave your alliance?')) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post('/api/v1/alliances/leave');

    successMessage.value = response.data.message || 'Left alliance successfully';
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to leave alliance';
  } finally {
    processing.value = false;
  }
};

const acceptInvite = async (inviteId) => {
  if (processing.value) return;

  try {
    processing.value = true;
    error.value = '';
    successMessage.value = '';

    const response = await api.post(`/api/v1/alliances/invites/${inviteId}/accept`);

    successMessage.value = response.data.message || 'Joined alliance!';
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to accept invite';
  } finally {
    processing.value = false;
  }
};

const declineInvite = async (inviteId) => {
  if (processing.value) return;

  try {
    processing.value = true;
    error.value = '';

    await api.post(`/api/v1/alliances/invites/${inviteId}/decline`);
    await fetchData();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to decline invite';
  } finally {
    processing.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="alliances-view">
    <div class="page-header">
      <h1>⚔️ Alliances</h1>
      <p class="subtitle">Form powerful alliances with other gangs</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading alliances...</p>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="alert alert-error">
      {{ error }}
    </div>

    <!-- Success Message -->
    <div v-if="successMessage" class="alert alert-success">
      {{ successMessage }}
    </div>

    <!-- Content -->
    <div v-if="!loading" class="alliances-content">

      <!-- Pending Invites -->
      <div v-if="pendingInvites.length > 0" class="card invites-card">
        <h2>📩 Pending Invites</h2>
        <div class="invites-list">
          <div v-for="invite in pendingInvites" :key="invite.id" class="invite-item">
            <div class="invite-info">
              <span class="invite-alliance">[{{ invite.alliance_tag }}] {{ invite.alliance_name }}</span>
              <span class="invite-from">from {{ invite.invited_by }}</span>
            </div>
            <div class="invite-actions">
              <button @click="acceptInvite(invite.id)" :disabled="processing" class="btn btn-success btn-sm">
                Accept
              </button>
              <button @click="declineInvite(invite.id)" :disabled="processing" class="btn btn-danger btn-sm">
                Decline
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- My Alliance -->
      <div v-if="myAlliance" class="card my-alliance-card">
        <h2>🏰 My Alliance</h2>
        <div class="alliance-details">
          <div class="alliance-header">
            <span class="alliance-tag">[{{ myAlliance.tag }}]</span>
            <span class="alliance-name">{{ myAlliance.name }}</span>
          </div>
          <p class="alliance-description">{{ myAlliance.description || 'No description' }}</p>

          <div class="alliance-stats">
            <div class="stat">
              <span class="stat-label">Members</span>
              <span class="stat-value">{{ formatNumber(myAlliance.member_count || 0) }}</span>
            </div>
            <div class="stat">
              <span class="stat-label">Total Power</span>
              <span class="stat-value">{{ formatNumber(myAlliance.total_power || 0) }}</span>
            </div>
            <div class="stat">
              <span class="stat-label">Wars Won</span>
              <span class="stat-value">{{ formatNumber(myAlliance.wars_won || 0) }}</span>
            </div>
          </div>

          <div class="alliance-members" v-if="myAlliance.members">
            <h3>Members</h3>
            <div class="members-list">
              <div v-for="member in myAlliance.members" :key="member.id" class="member-item">
                <span class="member-name">{{ member.gang_name }}</span>
                <span class="member-role">{{ member.role }}</span>
              </div>
            </div>
          </div>

          <div class="alliance-actions">
            <button v-if="!isLeader" @click="leaveAlliance" :disabled="processing" class="btn btn-danger">
              Leave Alliance
            </button>
          </div>
        </div>
      </div>

      <!-- Create Alliance -->
      <div v-if="!myAlliance" class="card create-card">
        <h2>🏗️ Create Alliance</h2>
        <p class="create-info">Cost to create: {{ formatMoney(creationCost) }}</p>

        <button v-if="!showCreateForm" @click="showCreateForm = true" class="btn btn-primary">
          Create New Alliance
        </button>

        <form v-if="showCreateForm" @submit.prevent="createAlliance" class="create-form">
          <div class="form-group">
            <label for="allianceName">Alliance Name</label>
            <input
              type="text"
              id="allianceName"
              v-model="allianceName"
              placeholder="Enter alliance name"
              maxlength="30"
              required
            />
          </div>
          <div class="form-group">
            <label for="allianceTag">Alliance Tag (3-5 characters)</label>
            <input
              type="text"
              id="allianceTag"
              v-model="allianceTag"
              placeholder="TAG"
              minlength="3"
              maxlength="5"
              required
            />
          </div>
          <div class="form-group">
            <label for="allianceDescription">Description</label>
            <textarea
              id="allianceDescription"
              v-model="allianceDescription"
              placeholder="Describe your alliance..."
              maxlength="500"
              rows="3"
            ></textarea>
          </div>
          <div class="form-actions">
            <button type="submit" :disabled="processing" class="btn btn-success">
              Create Alliance
            </button>
            <button type="button" @click="showCreateForm = false" class="btn btn-secondary">
              Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- All Alliances -->
      <div class="card alliances-list-card">
        <h2>🌐 All Alliances</h2>

        <div v-if="alliances.length === 0" class="empty-state">
          <p>No alliances found. Be the first to create one!</p>
        </div>

        <div v-else class="alliances-table">
          <table>
            <thead>
              <tr>
                <th>Tag</th>
                <th>Name</th>
                <th>Members</th>
                <th>Power</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="alliance in alliances" :key="alliance.id">
                <td class="tag-cell">[{{ alliance.tag }}]</td>
                <td>{{ alliance.name }}</td>
                <td>{{ formatNumber(alliance.member_count || 0) }}</td>
                <td>{{ formatNumber(alliance.total_power || 0) }}</td>
                <td>
                  <button
                    v-if="!myAlliance"
                    @click="joinAlliance(alliance.id)"
                    :disabled="processing"
                    class="btn btn-sm btn-primary"
                  >
                    Request Join
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.alliances-view {
  max-width: 1000px;
  margin: 0 auto;
  padding: 1rem;
}

.page-header {
  text-align: center;
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  color: var(--color-heading);
}

.subtitle {
  color: var(--color-text-muted);
}

.loading-state {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.alert {
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.alert-error {
  background-color: rgba(239, 68, 68, 0.1);
  border: 1px solid var(--color-danger);
  color: var(--color-danger);
}

.alert-success {
  background-color: rgba(34, 197, 94, 0.1);
  border: 1px solid var(--color-success);
  color: var(--color-success);
}

.card {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.card h2 {
  margin-bottom: 1rem;
  font-size: 1.25rem;
  color: var(--color-heading);
}

.invites-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.invite-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: var(--color-background);
  border-radius: 0.5rem;
}

.invite-alliance {
  font-weight: 600;
  color: var(--color-primary);
}

.invite-from {
  color: var(--color-text-muted);
  font-size: 0.875rem;
  margin-left: 0.5rem;
}

.invite-actions {
  display: flex;
  gap: 0.5rem;
}

.alliance-header {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.alliance-tag {
  color: var(--color-primary);
  font-weight: 700;
}

.alliance-name {
  margin-left: 0.5rem;
}

.alliance-description {
  color: var(--color-text-muted);
  margin-bottom: 1rem;
}

.alliance-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat {
  text-align: center;
  padding: 1rem;
  background: var(--color-background);
  border-radius: 0.5rem;
}

.stat-label {
  display: block;
  font-size: 0.75rem;
  color: var(--color-text-muted);
  text-transform: uppercase;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-heading);
}

.members-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.member-item {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem;
  background: var(--color-background);
  border-radius: 0.25rem;
}

.member-role {
  color: var(--color-primary);
  font-size: 0.875rem;
}

.alliance-actions {
  margin-top: 1rem;
}

.create-info {
  color: var(--color-warning);
  margin-bottom: 1rem;
}

.create-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: var(--color-text);
}

.form-group input,
.form-group textarea {
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  background: var(--color-background);
  color: var(--color-text);
  font-size: 1rem;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: var(--color-primary);
}

.form-actions {
  display: flex;
  gap: 0.5rem;
}

.alliances-table {
  overflow-x: auto;
}

.alliances-table table {
  width: 100%;
  border-collapse: collapse;
}

.alliances-table th,
.alliances-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

.alliances-table th {
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  font-size: 0.75rem;
}

.tag-cell {
  color: var(--color-primary);
  font-weight: 600;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: var(--color-text-muted);
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
}

.btn-primary {
  background-color: var(--color-primary);
  color: white;
}

.btn-success {
  background-color: var(--color-success);
  color: white;
}

.btn-danger {
  background-color: var(--color-danger);
  color: white;
}

.btn-secondary {
  background-color: var(--color-border);
  color: var(--color-text);
}

@media (max-width: 768px) {
  .alliance-stats {
    grid-template-columns: 1fr;
  }

  .invite-item {
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-start;
  }
}
</style>
