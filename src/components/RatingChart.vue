<template>
  <el-card>
    <template #header>
      <div class="card-header">
        <span>User Rating Distribution</span>
      </div>
    </template>
    <div v-if="loading" class="chart-loading">
      <el-skeleton animated :rows="5" />
    </div>
    <div v-else>
      <Bar :data="chartData" :options="chartOptions" />
      <p class="research-note">
        <strong>Research Note:</strong> Higher concentration in 4.5–5 ★ range may indicate rating inflation — a known bias in plugin directories.
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
    legend: { display: false },
    title: { display: false }
  }
}

onMounted(async () => {
  try {
    const res = await getChartData('rating')
    chartData.value = {
      labels: res.data.labels,
      datasets: [
        {
          label: 'Plugins',
          backgroundColor: '#378ADD',
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
