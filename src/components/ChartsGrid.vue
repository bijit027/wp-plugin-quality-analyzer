<template>
  <div class="charts-grid">
    <el-skeleton v-if="loading" :rows="10" animated />
    <el-empty v-else-if="!hasData" description="No chart data available. Fetch plugins first." />
    
    <div v-else class="charts-container">
      <div class="chart-row">
        <HealthScoreChart :data="healthScoreData" />
        <RatingChart :data="ratingData" />
      </div>
      <div class="chart-row">
        <UpdateFrequencyChart :data="updateFrequencyData" />
        <AbandonmentChart :data="abandonmentData" />
      </div>
      <div class="chart-row full-width">
        <ResolutionScatterChart :data="scatterData" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getChartData } from '../utils/api.js'
import HealthScoreChart from './HealthScoreChart.vue'
import RatingChart from './RatingChart.vue'
import UpdateFrequencyChart from './UpdateFrequencyChart.vue'
import AbandonmentChart from './AbandonmentChart.vue'
import ResolutionScatterChart from './ResolutionScatterChart.vue'

const loading = ref(true)
const hasData = ref(false)

const healthScoreData = ref(null)
const ratingData = ref(null)
const updateFrequencyData = ref(null)
const abandonmentData = ref(null)
const scatterData = ref(null)

const loadCharts = async () => {
  try {
    loading.value = true
    const [health, rating, update, abandonment, scatter] = await Promise.all([
      getChartData('health_score'),
      getChartData('rating'),
      getChartData('update_frequency'),
      getChartData('abandonment'),
      getChartData('scatter')
    ])
    
    healthScoreData.value = health.data
    ratingData.value = rating.data
    updateFrequencyData.value = update.data
    abandonmentData.value = abandonment.data
    scatterData.value = scatter.data
    
    if (health.data && health.data.values && health.data.values.some(v => v > 0)) {
      hasData.value = true
    } else {
      hasData.value = false
    }
  } catch (error) {
    console.error('Error loading chart data', error)
    hasData.value = false
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCharts()
})
</script>

<style scoped>
.charts-grid {
  margin-top: 20px;
}
.charts-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.chart-row {
  display: flex;
  gap: 20px;
}
.chart-row > * {
  flex: 1;
  min-width: 0;
}
.full-width > * {
  flex: 100%;
}
@media (max-width: 768px) {
  .chart-row {
    flex-direction: column;
  }
}
</style>
