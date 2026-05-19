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
            <el-option label="1000" :value="1000" />
            <el-option label="5000" :value="5000" />
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
          <div class="btn-group">
            <el-button 
              type="primary" 
              @click="handleStartFetch(false)" 
              :loading="status === 'running' && !isResuming"
              :disabled="status === 'running'">
              Start Fetching
            </el-button>
            <el-button 
              v-if="isResumable"
              type="warning" 
              @click="handleStartFetch(true)" 
              :loading="status === 'running' && isResuming"
              :disabled="status === 'running'">
              Resume Fetching
            </el-button>
          </div>
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
        :striped="status === 'running'" 
        :striped-flow="status === 'running'" 
      />
      <div class="status-message" :class="{'error-text': status === 'error', 'success-text': status === 'complete'}">
        {{ statusMessage }}
      </div>
    </div>
  </el-card>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { startFetch, getFetchStatus, clearData } from '../utils/api'
import { ElMessage } from 'element-plus'

const emit = defineEmits(['refresh'])

const fetchCount = ref(100)
const browse = ref('popular')
const status = ref('idle')
const progress = ref({ fetched: 0, total: 0 })
const statusMessage = ref('')

const currentPage = ref(1)
const isResumable = ref(false)
const isResuming = ref(false)

const totalPages = computed(() => Math.ceil(fetchCount.value / 25))

const progressPercentage = computed(() => {
  if (progress.value.total === 0) return 0
  return Math.round((progress.value.fetched / progress.value.total) * 100)
})

const progressStatus = computed(() => {
  if (status.value === 'error') return 'exception'
  if (status.value === 'complete') return 'success'
  return ''
})

const fetchNextPage = async () => {
  if (status.value !== 'running') return
  
  try {
    statusMessage.value = `Fetching page ${currentPage.value} of ${totalPages.value}...`
    const res = await startFetch(fetchCount.value, 25, browse.value, currentPage.value)
    const data = res.data
    
    progress.value.fetched = data.fetched
    progress.value.total = fetchCount.value
    
    if (data.is_complete || currentPage.value >= totalPages.value) {
      status.value = 'complete'
      statusMessage.value = 'Fetch completed successfully'
      isResumable.value = false
      isResuming.value = false
      ElMessage.success('Fetch completed successfully')
      emit('refresh')
    } else {
      currentPage.value++
      // Respectful delay between API calls to avoid rate limiting
      setTimeout(fetchNextPage, 1000)
    }
  } catch (error) {
    status.value = 'error'
    isResumable.value = true
    isResuming.value = false
    statusMessage.value = `Fetch failed on page ${currentPage.value}. You can resume.`
    ElMessage.error(`Fetch failed on page ${currentPage.value}`)
  }
}

const handleStartFetch = (isResume = false) => {
  status.value = 'running'
  isResuming.value = isResume
  
  if (!isResume) {
    currentPage.value = 1
    progress.value = { fetched: 0, total: fetchCount.value }
    isResumable.value = false
  }
  
  statusMessage.value = isResume ? `Resuming fetch from page ${currentPage.value}...` : 'Starting fetch...'
  fetchNextPage()
}

const handleClearData = async () => {
  try {
    await clearData()
    ElMessage.success('Data cleared successfully')
    status.value = 'idle'
    progress.value = { fetched: 0, total: 0 }
    statusMessage.value = ''
    isResumable.value = false
    isResuming.value = false
    emit('refresh')
  } catch (error) {
    ElMessage.error('Failed to clear data')
  }
}

onMounted(async () => {
  try {
    const res = await getFetchStatus()
    if (res.data && res.data.status === 'running') {
      // Fetch was running (possibly interrupted by a refresh)
      // Restore page location and mark as resumable
      status.value = 'error'
      currentPage.value = Math.floor(res.data.fetched / 25) + 1
      fetchCount.value = res.data.total
      progress.value.fetched = res.data.fetched
      progress.value.total = res.data.total
      isResumable.value = true
      statusMessage.value = `Previous fetch was interrupted at page ${currentPage.value - 1}. You can resume.`
    } else if (res.data && res.data.status !== 'idle') {
      status.value = res.data.status
      progress.value.fetched = res.data.fetched
      progress.value.total = res.data.total
      statusMessage.value = res.data.message
    }
  } catch (e) {
    console.error(e)
  }
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
.btn-group {
  display: flex;
  gap: 10px;
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
