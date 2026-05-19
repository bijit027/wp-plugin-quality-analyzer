<template>
  <el-card class="export-panel">
    <template #header>
      <div class="card-header">
        <span>Dataset Export & Methodology</span>
      </div>
    </template>

    <div class="export-actions">
      <h3>Download Dataset</h3>
      <p>Export the current database of analyzed plugins for external processing in R, Python, or Excel.</p>
      
      <el-button type="primary" @click="handleExport('csv')" :loading="exportingCSV">
        Download Dataset CSV
      </el-button>
      <el-button @click="handleExport('json')" :loading="exportingJSON">
        Download Dataset JSON
      </el-button>
    </div>

    <el-divider />

    <div class="methodology">
      <h3>Methodology Notes</h3>
      <el-descriptions :column="1" border>
        <el-descriptions-item label="Data Source">WordPress.org Plugin API v1.2</el-descriptions-item>
        <el-descriptions-item label="Collection Date">{{ new Date().toLocaleDateString() }}</el-descriptions-item>
        <el-descriptions-item label="Plugin Health Score Formula">
          <code>PHS = (UpdateScore × 0.30) + (RatingScore × 0.25) + (ResolutionScore × 0.25) + (ResponseScore × 0.20)</code>
        </el-descriptions-item>
      </el-descriptions>
    </div>

    <el-divider />

    <div class="paper-outline">
      <h3>Research Paper Outline</h3>
      <ul>
        <li>Chapter 1: Introduction</li>
        <li>Chapter 2: Literature Review</li>
        <li>Chapter 3: Methodology and the Plugin Health Score</li>
        <li>Chapter 4: Data Collection via WP Plugin Quality Analyzer</li>
        <li>Chapter 5: Analysis and Results</li>
        <li>Chapter 6: Discussion</li>
        <li>Chapter 7: Conclusion</li>
      </ul>
    </div>

    <el-divider />

    <div class="citation">
      <h3>Citation</h3>
      <p>To cite this dataset in the thesis or related publications:</p>
      <div class="citation-box">Deb, B. (2025). WordPress Plugin Quality Dataset [Data set]. 
Collected via WordPress.org Plugin API. 
GitHub: https://github.com/bijit027/wp-plugin-quality-analyzer</div>
      <el-button size="small" @click="copyCitation" style="margin-top: 10px;">
        Copy APA Citation
      </el-button>
    </div>
  </el-card>
</template>

<script setup>
import { ref } from 'vue'
import { exportData } from '../utils/api'
import { ElMessage } from 'element-plus'

const exportingCSV = ref(false)
const exportingJSON = ref(false)

const handleExport = async (format) => {
  if (format === 'csv') exportingCSV.value = true
  if (format === 'json') exportingJSON.value = true
  
  try {
    const res = await exportData(format)
    if (res.data.success) {
      window.open(res.data.url, '_blank')
      ElMessage.success(`Dataset exported as ${format.toUpperCase()}`)
    }
  } catch (error) {
    ElMessage.error(`Failed to export dataset as ${format.toUpperCase()}`)
  } finally {
    exportingCSV.value = false
    exportingJSON.value = false
  }
}

const copyCitation = async () => {
  const citation = `Deb, B. (2025). WordPress Plugin Quality Dataset [Data set]. Collected via WordPress.org Plugin API. GitHub: https://github.com/bijit027/wp-plugin-quality-analyzer`
  try {
    await navigator.clipboard.writeText(citation)
    ElMessage.success('Citation copied to clipboard')
  } catch (err) {
    ElMessage.error('Failed to copy citation')
  }
}
</script>

<style scoped>
.export-actions, .methodology, .paper-outline, .citation {
  margin-bottom: 20px;
}
.export-actions h3, .methodology h3, .paper-outline h3, .citation h3 {
  margin-top: 0;
  color: #303133;
}
.citation-box {
  background-color: #f4f4f5;
  padding: 15px;
  border-radius: 4px;
  font-family: monospace;
  color: #606266;
  white-space: pre-wrap;
}
ul {
  padding-left: 20px;
  color: #606266;
}
ul li {
  margin-bottom: 5px;
}
</style>
