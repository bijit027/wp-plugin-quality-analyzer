<template>
  <el-container>
    <el-header height="80px">
      <h2>WP Plugin Quality Analyzer — Research Dashboard</h2>
      <p>MS CS Thesis Research — Bijit Deb</p>
    </el-header>
    
    <el-main>
      <div v-if="loading">
        <el-skeleton :rows="10" animated />
      </div>
      <div v-else-if="stats.total === 0">
        <el-empty description="No data collected yet. Go to the Fetch Panel to begin.">
          <el-button type="primary" @click="activeTab = 'overview'">Go to Fetch Panel</el-button>
        </el-empty>
        <el-tabs v-model="activeTab" v-show="activeTab === 'overview'">
           <el-tab-pane label="Overview" name="overview">
             <FetchPanel @refresh="loadStats" />
           </el-tab-pane>
        </el-tabs>
      </div>
      <el-tabs v-else v-model="activeTab">
        <el-tab-pane label="Overview" name="overview">
          <FetchPanel @refresh="loadStats" />
          <MetricsGrid :stats="stats" />
        </el-tab-pane>
        <el-tab-pane label="Visualizations" name="visualizations">
          <ChartsGrid />
        </el-tab-pane>
        <el-tab-pane label="Data Table" name="data-table">
          <PluginTable />
        </el-tab-pane>
        <el-tab-pane label="Research Insights" name="insights">
          <InsightsPanel :stats="stats" />
        </el-tab-pane>
        <el-tab-pane label="Export" name="export">
          <ExportPanel />
        </el-tab-pane>
      </el-tabs>
    </el-main>
  </el-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getStats } from './utils/api'

import FetchPanel from './components/FetchPanel.vue'
import MetricsGrid from './components/MetricsGrid.vue'
import ChartsGrid from './components/ChartsGrid.vue'
import PluginTable from './components/PluginTable.vue'
import InsightsPanel from './components/InsightsPanel.vue'
import ExportPanel from './components/ExportPanel.vue'

const activeTab = ref('overview')
const loading = ref(true)
const stats = ref({
  total: 0,
  abandoned: 0,
  abandoned_percent: 0,
  avg_health_score: 0,
  avg_rating: 0,
  avg_resolution_rate: 0,
  avg_installs: 0
})

const loadStats = async () => {
  loading.value = true
  try {
    const res = await getStats()
    stats.value = res.data
  } catch (error) {
    console.error('Failed to load stats', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadStats()
})
</script>

<style>
.el-header {
  background-color: #f0f2f5;
  padding: 10px 20px;
  border-bottom: 1px solid #e4e7ed;
}
.el-header h2 {
  margin: 0;
  color: #303133;
}
.el-header p {
  margin: 5px 0 0;
  color: #606266;
  font-size: 14px;
}
.el-main {
  padding: 20px;
}
</style>
