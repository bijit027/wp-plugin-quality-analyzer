<template>
  <el-card>
    <template #header>
      <div class="card-header">
        <span>Plugin Health Score Distribution</span>
      </div>
    </template>
    
    <div style="height: 250px;">
      <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
    </div>
    
    <div class="chart-legend">
      <el-tag type="danger" color="#A32D2D" effect="dark" style="border:none">0–40</el-tag>
      <el-tag type="danger" color="#E24B4A" effect="dark" style="border:none">40–60</el-tag>
      <el-tag type="warning" color="#BA7517" effect="dark" style="border:none">60–80</el-tag>
      <el-tag type="success" color="#1D9E75" effect="dark" style="border:none">80–100</el-tag>
    </div>
    
    <p class="research-note">
      <strong>Original Research Metric:</strong> The Plugin Health Score (PHS) is a custom composite metric developed for this study.
    </p>
  </el-card>
</template>

<script setup>
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'

const props = defineProps({
  data: {
    type: Object,
    default: null
  }
})

const chartData = computed(() => {
  if (!props.data || !props.data.labels) return null
  
  return {
    labels: props.data.labels,
    datasets: [
      {
        backgroundColor: ['#A32D2D', '#E24B4A', '#BA7517', '#1D9E75'],
        data: props.data.values
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  }
}
</script>

<style scoped>
.research-note {
  font-size: 12px;
  color: #606266;
  margin-top: 15px;
  font-style: italic;
  text-align: center;
}
.card-header {
  font-weight: bold;
}
.chart-legend {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 15px;
}
</style>
