<template>
  <el-card>
    <template #header>
      <div class="card-header">
        <span>Plugin Health Score Distribution</span>
      </div>
    </template>
    <div v-if="loading" class="chart-loading">
      <el-skeleton animated :rows="5" />
    </div>
    <div v-else>
      <Bar :data="chartData" :options="chartOptions" />
      <p class="research-note">
        <strong>Original Research Metric:</strong> The Plugin Health Score (PHS) is a custom composite metric developed for this study.
      </p>
    </div>
  </el-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Bar } from 'vue-chartjs'
import { getChartData } from '../utils/api'

const loading = ref(true)
const chartData = ref({
  labels: [],
  datasets: []
})

const chartOptions = {
  responsive: true,
  plugins: {
    legend: { display: false }
  }
}

onMounted(async () => {
  try {
    const res = await getChartData('health_score')
    chartData.value = {
      labels: res.data.labels,
      datasets: [
        {
          backgroundColor: ['#8B0000', '#F56C6C', '#E6A23C', '#85CE61', '#67C23A'],
          data: res.data.values
        }
      ]
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.research-note {
  font-size: 12px;
  color: #606266;
  margin-top: 15px;
  font-style: italic;
}
.chart-loading {
  padding: 20px;
}
</style>
