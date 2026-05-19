<template>
  <el-card>
    <template #header>
      <div class="card-header">
        <span>Plugin Maintenance Status</span>
      </div>
    </template>
    
    <div style="height: 250px;">
      <Doughnut v-if="chartData" :data="chartData" :options="chartOptions" />
    </div>
    
    <div class="chart-legend">
      <el-tag type="success" color="#1D9E75" effect="dark" style="border:none">Active</el-tag>
      <el-tag type="danger" color="#A32D2D" effect="dark" style="border:none">Abandoned</el-tag>
    </div>
    
    <p class="research-note">
      Plugins not updated in 365+ days are classified as abandoned per the operational definition in this study.
    </p>
  </el-card>
</template>

<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'

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
        backgroundColor: ['#1D9E75', '#A32D2D'],
        hoverBackgroundColor: ['#1D9E75', '#A32D2D'],
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
  gap: 10px;
  margin-top: 15px;
}
</style>
