<template>
  <el-card class="fetch-panel">
    <template #header>
      <div class="card-header">
        <h3>Collect Plugin Data</h3>
      </div>
    </template>
    
    <p>This tool fetches plugin data from the WordPress.org Plugin Directory API and calculates software quality metrics.</p>
    
    <div class="controls">
      <el-form :inline="true">
        <el-form-item label="Number of plugins to fetch">
          <el-select v-model="fetchCount" :disabled="status === 'running'">
            <el-option label="50" :value="50" />
            <el-option label="100" :value="100" />
            <el-option label="200" :value="200" />
            <el-option label="500" :value="500" />
          </el-select>
        </el-form-item>
        <el-form-item label="Browse by">
          <el-select v-model="browse" :disabled="status === 'running'">
            <el-option label="Popular" value="popular" />
            <el-option label="New" value="new" />
            <el-option label="Updated" value="updated" />
            <el-option label="Top Rated" value="top-rated" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button 
            type="primary" 
            @click="handleStartFetch" 
            :loading="status === 'running'"
            :disabled="status === 'running'">
            Start Fetching
          </el-button>
        </el-form-item>
        <el-form-item>
          <el-popconfirm
            title="Are you sure you want to clear all data?"
            confirm-button-text="Yes, clear data"
            cancel-button-text="No, cancel"
            @confirm="handleClearData"
            width="250"
          >
            <template #reference>
              <el-button type="danger" :disabled="status === 'running'">Clear All Data</el-button>
            </template>
          </el-popconfirm>
        </el-form-item>
      </el-form>
    </div>

    <div v-if="status !== 'idle'" class="progress-section">
      <el-progress 
        :percentage="progressPercentage" 
        :status="progressStatus"
        :stroke-width="15" 
        striped 
        striped-flow 
      />
      <div class="status-message" :class="{'error-text': status === 'error', 'success-text': status === 'complete'}">
        {{ statusMessage }}
      </div>
    </div>
  </el-card>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { startFetch, getFetchStatus, clearData } from '../utils/api'
import { ElMessage } from 'element-plus'

const emit = defineEmits(['refresh'])

const fetchCount = ref(100)
const browse = ref('popular')
const status = ref('idle')
const progress = ref({ fetched: 0, total: 0 })
const statusMessage = ref('')
let pollInterval = null

const progressPercentage = computed(() => {
  if (progress.value.total === 0) return 0
  return Math.round((progress.value.fetched / progress.value.total) * 100)
})

const progressStatus = computed(() => {
  if (status.value === 'error') return 'exception'
  if (status.value === 'complete') return 'success'
  return ''
})

const startPolling = () => {
  if (pollInterval) clearInterval(pollInterval)
  pollInterval = setInterval(async () => {
    try {
      const res = await getFetchStatus()
      const data = res.data
      
      status.value = data.status
      progress.value.fetched = data.fetched
      progress.value.total = data.total
      statusMessage.value = data.message

      if (data.status === 'complete') {
        clearInterval(pollInterval)
        ElMessage.success('Fetch completed successfully')
        emit('refresh')
      } else if (data.status === 'error') {
        clearInterval(pollInterval)
        ElMessage.error(data.message || 'Error occurred during fetch')
      }
    } catch (e) {
      console.error(e)
    }
  }, 2000)
}

const handleStartFetch = () => {
  status.value = 'running'
  progress.value = { fetched: 0, total: fetchCount.value }
  statusMessage.value = 'Starting fetch...'
  
  startFetch(fetchCount.value, 25).catch((error) => {
    status.value = 'error'
    statusMessage.value = 'Failed to start fetch process'
    ElMessage.error('Failed to start fetch process')
    if (pollInterval) clearInterval(pollInterval)
  })
  
  startPolling()
}

const handleClearData = async () => {
  try {
    await clearData()
    ElMessage.success('Data cleared successfully')
    status.value = 'idle'
    progress.value = { fetched: 0, total: 0 }
    statusMessage.value = ''
    emit('refresh')
  } catch (error) {
    ElMessage.error('Failed to clear data')
  }
}

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<style scoped>
.fetch-panel {
  margin-bottom: 20px;
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.card-header h3 {
  margin: 0;
}
.controls {
  margin-top: 20px;
}
.progress-section {
  margin-top: 20px;
}
.status-message {
  margin-top: 10px;
  font-size: 14px;
  color: #606266;
}
.error-text {
  color: #F56C6C;
}
.success-text {
  color: #67C23A;
}
</style>
