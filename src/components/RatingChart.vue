<template>
  <el-card>
    <template #header>
      <div class="card-header">
        <span>User Rating Distribution</span>
      </div>
    </template>
    
    <div style="height: 250px;">
      <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
    </div>
    
    <div class="chart-legend">
      <el-tag type="primary" color="#378ADD" effect="dark" style="border:none">Rating buckets</el-tag>
    </div>
    
    <p class="research-note">
      Higher concentration in 4.5–5 ★ range may indicate rating inflation — a known bias in plugin directories.
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
        backgroundColor: '#378ADD',
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
