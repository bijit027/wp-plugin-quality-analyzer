<template>
  <el-card>
    <template #header>
      <div class="card-header">
        <span>Update Frequency Distribution</span>
      </div>
    </template>
    
    <div style="height: 250px;">
      <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
    </div>
    
    <div class="chart-legend">
      <el-tag type="success" color="#1D9E75" effect="dark" style="border:none">≤90 days (Active)</el-tag>
      <el-tag type="warning" color="#BA7517" effect="dark" style="border:none">91–180 (Slowing)</el-tag>
      <el-tag type="danger" color="#E24B4A" effect="dark" style="border:none">181–365 (At Risk)</el-tag>
      <el-tag type="danger" color="#A32D2D" effect="dark" style="border:none">>365 (Abandoned)</el-tag>
    </div>
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
        backgroundColor: ['#1D9E75', '#1D9E75', '#BA7517', '#E24B4A', '#A32D2D'],
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
