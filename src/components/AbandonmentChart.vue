<template>
  <el-card>
    <template #header>
      <div class="card-header">
        <span>Plugin Maintenance Status</span>
      </div>
    </template>
    <div v-if="loading" class="chart-loading">
      <el-skeleton animated :rows="5" />
    </div>
    <div v-else>
      <Doughnut :data="chartData" :options="chartOptions" />
      <p class="research-note">
        <strong>Research Note:</strong> Plugins not updated in 365+ days are classified as abandoned per the operational definition in this study.
      </p>
    </div>
  </el-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { getChartData } from '../utils/api'

const loading = ref(true)
const chartData = ref({
  labels: [],
  datasets: []
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false
}

onMounted(async () => {
  try {
    const res = await getChartData('abandonment')
    chartData.value = {
      labels: res.data.labels,
      datasets: [
        {
          backgroundColor: ['#1D9E75', '#E24B4A'],
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
