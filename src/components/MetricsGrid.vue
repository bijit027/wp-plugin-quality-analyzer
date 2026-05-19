<template>
  <div class="metrics-grid">
    <el-row :gutter="20">
      <el-col :span="4" :xs="12" :sm="8">
        <el-card shadow="hover" class="metric-card">
          <div class="metric-label">Total Plugins</div>
          <div class="metric-value">{{ stats.total }}</div>
        </el-card>
      </el-col>
      <el-col :span="5" :xs="12" :sm="8">
        <el-card shadow="hover" class="metric-card">
          <div class="metric-label">Avg Health Score</div>
          <div class="metric-value" :style="{ color: getHealthColor(stats.avg_health_score) }">
            {{ stats.avg_health_score ? stats.avg_health_score.toFixed(2) : '0' }}
          </div>
        </el-card>
      </el-col>
      <el-col :span="5" :xs="12" :sm="8">
        <el-card shadow="hover" class="metric-card">
          <div class="metric-label">Abandoned Plugins</div>
          <div class="metric-value" style="color: #F56C6C">
            {{ stats.abandoned }}
          </div>
          <div class="metric-sub">{{ stats.abandoned_percent }}%</div>
        </el-card>
      </el-col>
      <el-col :span="5" :xs="12" :sm="8">
        <el-card shadow="hover" class="metric-card">
          <div class="metric-label">Avg Rating</div>
          <div class="metric-value" style="color: #E6A23C">
            {{ (stats.avg_rating / 20).toFixed(2) }} / 5
          </div>
        </el-card>
      </el-col>
      <el-col :span="5" :xs="12" :sm="8">
        <el-card shadow="hover" class="metric-card">
          <div class="metric-label">Avg Resolution Rate</div>
          <div class="metric-value">
            {{ stats.avg_resolution_rate ? stats.avg_resolution_rate.toFixed(1) : '0' }}%
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
      total: 0,
      abandoned: 0,
      abandoned_percent: 0,
      avg_health_score: 0,
      avg_rating: 0,
      avg_resolution_rate: 0,
      avg_installs: 0
    })
  }
})

const getHealthColor = (score) => {
  if (score >= 70) return '#67C23A' // green
  if (score >= 50) return '#E6A23C' // orange
  if (score >= 30) return '#F56C6C' // red
  return '#8B0000' // darkred
}
</script>

<style scoped>
.metrics-grid {
  margin-top: 20px;
}
.metric-card {
  text-align: center;
  padding: 10px 0;
}
.metric-label {
  font-size: 14px;
  color: #909399;
  margin-bottom: 10px;
}
.metric-value {
  font-size: 28px;
  font-weight: bold;
  color: #303133;
}
.metric-sub {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
}
</style>
