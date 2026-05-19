<template>
  <el-card>
    <div class="table-controls">
      <el-form :inline="true">
        <el-form-item label="Filter">
          <el-select v-model="filter" @change="fetchData">
            <el-option label="All" value="all" />
            <el-option label="Healthy (≥70)" value="healthy" />
            <el-option label="Moderate (50-70)" value="moderate" />
            <el-option label="At Risk (30-50)" value="at_risk" />
            <el-option label="Abandoned (<30)" value="abandoned" />
          </el-select>
        </el-form-item>
      </el-form>
      <div class="table-stats">
        Showing {{ plugins.length }} of {{ total }} plugins
      </div>
    </div>

    <el-table :data="plugins" v-loading="loading" stripe @sort-change="handleSort">
      <el-table-column type="index" label="#" width="50" />
      <el-table-column label="Plugin Name" min-width="150">
        <template #default="scope">
          <el-link :href="`https://wordpress.org/plugins/${scope.row.slug}`" target="_blank" type="primary">
            {{ scope.row.name }}
          </el-link>
        </template>
      </el-table-column>
      <el-table-column prop="active_installs" label="Installs" sortable="custom" />
      <el-table-column prop="rating" label="Rating" sortable="custom">
        <template #default="scope">
          <el-rate :model-value="scope.row.rating / 20" disabled show-score text-color="#ff9900" score-template="{value}" />
        </template>
      </el-table-column>
      <el-table-column prop="health_score" label="Health Score" sortable="custom">
        <template #default="scope">
          <el-tag :type="getScoreTagType(scope.row.health_score)">
            {{ scope.row.health_score }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="days_since_update" label="Days Since Update" sortable="custom" />
      <el-table-column prop="resolution_rate" label="Resolution Rate" sortable="custom">
        <template #default="scope">
          {{ scope.row.resolution_rate }}%
        </template>
      </el-table-column>
      <el-table-column label="Status">
        <template #default="scope">
          <el-tag :type="getScoreTagType(scope.row.health_score)" effect="dark">
            {{ getRiskLabel(scope.row.health_score) }}
          </el-tag>
        </template>
      </el-table-column>
    </el-table>

    <div class="pagination">
      <el-pagination
        v-model:current-page="page"
        v-model:page-size="perPage"
        :total="total"
        layout="prev, pager, next"
        @current-change="fetchData"
      />
    </div>
  </el-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { fetchPlugins } from '../utils/api'

const loading = ref(false)
const plugins = ref([])
const total = ref(0)
const page = ref(1)
const perPage = ref(25)
const orderby = ref('id')
const order = ref('DESC')
const filter = ref('all')

const fetchData = async () => {
  loading.value = true
  try {
    const res = await fetchPlugins({
      page: page.value,
      per_page: perPage.value,
      orderby: orderby.value,
      order: order.value,
      filter: filter.value
    })
    plugins.value = res.data.plugins
    total.value = res.data.total
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const handleSort = ({ prop, order: sortOrder }) => {
  orderby.value = prop || 'id'
  order.value = sortOrder === 'ascending' ? 'ASC' : 'DESC'
  page.value = 1
  fetchData()
}

const getScoreTagType = (score) => {
  if (score >= 70) return 'success'
  if (score >= 50) return 'warning'
  if (score >= 30) return 'danger'
  return 'info'
}

const getRiskLabel = (score) => {
  if (score >= 70) return 'Healthy'
  if (score >= 50) return 'Moderate'
  if (score >= 30) return 'At Risk'
  return 'Abandoned'
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.table-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}
</style>
