<template>
  <el-card class="chart-card">
    <template #header>
      <div class="card-header">
        <span>Resolution Rate vs Health Score</span>
      </div>
    </template>
    
    <div style="height: 250px;">
      <Scatter v-if="chartData" :data="chartData" :options="chartOptions" />
    </div>
    
    <div class="chart-legend">
      <el-tag type="info">One point per plugin</el-tag>
    </div>
  </el-card>
</template>

<script setup>
import { computed } from 'vue'
import { Scatter } from 'vue-chartjs'

const props = defineProps({
  data: {
    type: Object,
    default: null
  }
})

const chartData = computed(() => {
  if (!props.data || !props.data.points) return null
  
  return {
    datasets: [
      {
        label: 'Plugin',
        data: props.data.points,
        backgroundColor: 'rgba(127, 119, 221, 0.6)',
        borderColor: 'rgba(127, 119, 221, 0.8)',
        borderWidth: 1,
        pointRadius: 4,
        pointHoverRadius: 6
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          return `Resolution: ${context.raw.x}% | Health Score: ${context.raw.y}`
        }
      }
    }
  },
  scales: {
    x: {
      title: {
        display: true,
        text: 'Resolution Rate (%)'
      },
      min: -5,
      max: 105
    },
    y: {
      title: {
        display: true,
        text: 'Plugin Health Score'
      },
      min: -5,
      max: 105
    }
  }
}
</script>

<style scoped>
.chart-card {
  margin-bottom: 20px;
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
