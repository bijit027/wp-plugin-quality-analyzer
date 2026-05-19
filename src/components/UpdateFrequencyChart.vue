<template>
  <el-card>
    <template #header>
      <div class="card-header">
        <span>Update Frequency Distribution</span>
      </div>
    </template>
    <div v-if="loading" class="chart-loading">
      <el-skeleton animated :rows="5" />
    </div>
    <div v-else>
      <Bar :data="chartData" :options="chartOptions" />
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
    const res = await getChartData('update_frequency')
    chartData.value = {
      labels: res.data.labels,
      datasets: [
        {
          backgroundColor: ['#67C23A', '#85CE61', '#E6A23C', '#F3D19E', '#E24B4A'],
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
.chart-loading {
  padding: 20px;
}
</style>
