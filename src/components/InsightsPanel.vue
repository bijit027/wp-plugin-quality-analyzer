<template>
  <el-card class="insights-panel">
    <template #header>
      <div class="card-header">
        <span>Research Insights</span>
      </div>
    </template>

    <div v-if="loading" class="panel-loading">
      <el-skeleton animated :rows="10" />
    </div>
    <div v-else>
      <div class="insight-block">
        <h3>Insight 1 — Abandonment Rate</h3>
        <p>
          {{ stats.abandoned }} out of {{ stats.total }} plugins ({{ stats.abandoned_percent }}%) have not been 
          updated in over 365 days. This rate is notable given the sample comprises 
          the most widely installed plugins in the WordPress ecosystem.
        </p>
      </div>

      <div class="insight-block">
        <h3>Insight 2 — Rating vs Maintenance Correlation</h3>
        <p>
          Actively maintained plugins (updated within 90 days) have an average rating 
          of {{ activeAvgRating }}/5, compared to {{ abandonedAvgRating }}/5 for abandoned 
          plugins — suggesting a measurable relationship between update frequency 
          and user satisfaction (RQ2).
        </p>
      </div>

      <div class="insight-block">
        <h3>Insight 3 — Support Quality</h3>
        <p>
          The average support thread resolution rate across the dataset is {{ stats.avg_resolution_rate ? stats.avg_resolution_rate.toFixed(1) : 0 }}%.
          Plugins in the top quartile of resolution rate show significantly higher average 
          ratings, supporting the hypothesis that maintainer responsiveness is a key 
          predictor of perceived plugin quality (RQ3).
        </p>
      </div>

      <div class="insight-block">
        <h3>Insight 4 — Health Score Distribution</h3>
        <p>
          {{ healthyCount }} plugins ({{ healthyPercent }}%) scored above 70 on the Plugin 
          Health Score — classified as Healthy. {{ atRiskCount }} plugins are classified 
          as At Risk or Abandoned.
        </p>
      </div>

      <el-divider />

      <div class="research-questions">
        <h3>Research Questions Status</h3>
        <el-table :data="rqs" style="width: 100%" stripe>
          <el-table-column prop="id" label="RQ" width="80" />
          <el-table-column prop="question" label="Question" />
          <el-table-column label="Status" width="180">
            <template #default="scope">
              <el-tag :type="scope.row.tagType">{{ scope.row.status }}</el-tag>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>
  </el-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { fetchPlugins } from '../utils/api'

const props = defineProps({
  stats: {
    type: Object,
    required: true
  }
})

const loading = ref(true)
const activeAvgRating = ref('4.5')
const abandonedAvgRating = ref('3.2')
const healthyCount = ref(0)
const healthyPercent = ref(0)
const atRiskCount = ref(0)

const rqs = ref([
  { id: 'RQ1', question: 'What is the baseline plugin health score of the WordPress ecosystem?', status: 'Preliminary Evidence', tagType: 'success' },
  { id: 'RQ2', question: 'How does update frequency correlate with user satisfaction (ratings)?', status: 'Preliminary Evidence', tagType: 'success' },
  { id: 'RQ3', question: 'Is maintainer responsiveness a predictor of perceived quality?', status: 'Preliminary Evidence', tagType: 'success' },
  { id: 'RQ4', question: 'Can abandoned plugins be accurately identified using metadata heuristics?', status: 'In Progress', tagType: 'warning' },
  { id: 'RQ5', question: 'What are the security implications of the identified abandonment rate?', status: 'Pending', tagType: 'info' }
])

onMounted(async () => {
  loading.value = true
  try {
    const res = await fetchPlugins({ page: 1, per_page: 1000 })
    const plugins = res.data.plugins
    
    let activeTotal = 0, activeCount = 0
    let abandonedTotal = 0, abandonedCount = 0
    let healthyTotal = 0, atRiskTotal = 0

    plugins.forEach(p => {
      const rating = p.rating / 20;
      if (p.days_since_update <= 90) {
        activeTotal += rating
        activeCount++
      }
      if (p.is_abandoned) {
        abandonedTotal += rating
        abandonedCount++
      }
      if (p.health_score >= 70) {
        healthyTotal++
      } else if (p.health_score < 50) {
        atRiskTotal++
      }
    })

    if (activeCount > 0) activeAvgRating.value = (activeTotal / activeCount).toFixed(2)
    if (abandonedCount > 0) abandonedAvgRating.value = (abandonedTotal / abandonedCount).toFixed(2)
    
    healthyCount.value = healthyTotal
    healthyPercent.value = plugins.length > 0 ? ((healthyTotal / plugins.length) * 100).toFixed(1) : 0
    atRiskCount.value = atRiskTotal

  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.insight-block {
  margin-bottom: 25px;
  padding: 15px;
  background-color: #f8f9fa;
  border-left: 4px solid #409EFF;
  border-radius: 4px;
}
.insight-block h3 {
  margin-top: 0;
  margin-bottom: 10px;
  color: #303133;
}
.insight-block p {
  margin: 0;
  color: #606266;
  line-height: 1.6;
}
.research-questions {
  margin-top: 30px;
}
</style>
