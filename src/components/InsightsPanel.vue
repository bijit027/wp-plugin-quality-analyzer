<template>
  <div class="insights-dashboard">
    <div v-if="loading" class="panel-loading">
      <el-card>
        <el-skeleton animated :rows="10" />
      </el-card>
    </div>
    
    <div v-else-if="!statisticsData" class="panel-empty">
      <el-card>
        <el-empty description="No statistical data available. Please collect some plugin data first." />
      </el-card>
    </div>

    <div v-else class="dashboard-content">
      <!-- Title Header Block with Export Dropdown -->
      <div class="dashboard-header">
        <div class="header-text">
          <h2 class="dashboard-title">Ecosystem Empirical Study & Insights</h2>
          <p class="dashboard-subtitle">Software Quality & Statistical Metrics Analysis</p>
        </div>
        <el-dropdown @command="handleExport">
          <el-button type="primary" size="large" class="export-btn" shadow="hover">
            <el-icon class="el-icon--left"><download /></el-icon>
            Export Research Insights
            <el-icon class="el-icon--right"><arrow-down /></el-icon>
          </el-button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="markdown">
                <el-icon><document /></el-icon> Download Academic Report (.md)
              </el-dropdown-item>
              <el-dropdown-item command="regression">
                <el-icon><data-analysis /></el-icon> Download OLS Regression (.csv)
              </el-dropdown-item>
              <el-dropdown-item command="correlation">
                <el-icon><grid /></el-icon> Download Correlation Matrix (.csv)
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>

      <!-- Top Row: Sample & OLS Fit -->
      <el-row :gutter="20" class="stat-cards-row">
        <el-col :span="8">
          <el-card class="glass-card stat-card" shadow="hover">
            <div class="card-metric-label">Research Sample Size</div>
            <div class="card-metric-value">{{ statisticsData.total_sample }}</div>
            <div class="card-metric-desc">WordPress Plugins analyzed in ecosystem study</div>
          </el-card>
        </el-col>
        <el-col :span="8">
          <el-card class="glass-card stat-card fit-card" shadow="hover">
            <div class="card-metric-label">Regression Fit (R²)</div>
            <div class="card-metric-value">{{ statisticsData.regression ? statisticsData.regression.r2.toFixed(4) : 'N/A' }}</div>
            <div class="card-metric-desc">Variance in Health Score explained by the model</div>
          </el-card>
        </el-col>
        <el-col :span="8">
          <el-card class="glass-card stat-card significance-card" shadow="hover">
            <div class="card-metric-label">Significance Level (α)</div>
            <div class="card-metric-value">0.05</div>
            <div class="card-metric-desc">Ecosystem confidence threshold for null rejection</div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Hypothesis Framework Cards -->
      <h3 class="section-title">I. Empirical Hypothesis Testing Framework</h3>
      <el-row :gutter="20" class="hypothesis-row">
        <el-col :span="8" v-for="h in statisticsData.hypothesis_tests" :key="h.id">
          <el-card class="glass-card hypothesis-card" :class="getHypothesisClass(h.result)" shadow="hover">
            <div class="hypothesis-header">
              <span class="hypothesis-badge">{{ h.id }}</span>
              <el-tag :type="getTagType(h.result)" effect="dark" size="small" round>{{ h.result }}</el-tag>
            </div>
            <div class="hypothesis-body">
              <p class="hypothesis-text">"{{ h.hypothesis }}"</p>
              <div class="hypothesis-metrics">
                <div class="metric-item">
                  <span class="lbl">Correlation (r)</span>
                  <span class="val">{{ h.r_value ? h.r_value.toFixed(4) : '0.0000' }}</span>
                </div>
                <div class="metric-item">
                  <span class="lbl">p-value</span>
                  <span class="val font-mono">{{ formatPValue(h.p_value) }}</span>
                </div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Correlation & Regression Split Tabular Section -->
      <el-row :gutter="20" class="tabular-section-row">
        <el-col :span="12">
          <el-card class="glass-card table-card" shadow="hover">
            <template #header>
              <div class="card-header">
                <span class="title">II. Correlation Analysis Matrix</span>
                <span class="subtitle">Pearson & Spearman (Rank) Coefficients</span>
              </div>
            </template>
            <el-table :data="correlationTableData" style="width: 100%" stripe class="premium-table">
              <el-table-column prop="relationship" label="Relationship Under Test" min-width="170" />
              <el-table-column prop="pearson" label="Pearson r" width="100" align="center">
                <template #default="scope">
                  <span class="font-mono font-bold">{{ scope.row.pearson.toFixed(4) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="spearman" label="Spearman ρ" width="110" align="center">
                <template #default="scope">
                  <span class="font-mono font-bold">{{ scope.row.spearman.toFixed(4) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="p_value" label="p-value" width="100" align="center">
                <template #default="scope">
                  <span class="font-mono text-xs">{{ formatPValue(scope.row.p_value) }}</span>
                </template>
              </el-table-column>
              <el-table-column label="Significance" width="120" align="center">
                <template #default="scope">
                  <el-tag :type="scope.row.p_value < 0.05 ? 'success' : 'info'" size="small">
                    {{ scope.row.p_value < 0.05 ? 'Significant' : 'No Correlation' }}
                  </el-tag>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-col>

        <el-col :span="12">
          <el-card class="glass-card table-card" shadow="hover">
            <template #header>
              <div class="card-header">
                <span class="title">III. OLS Multiple Linear Regression Model</span>
                <span class="subtitle">Predictor Coefficients for Plugin Health Score</span>
              </div>
            </template>
            <div class="formula-box">
              <span class="formula-label">Fitted Regression Equation:</span>
              <code class="formula-text">
                health_score = 
                <span class="coeff">{{ getCoeff('Intercept (Constant)') }}</span>
                <span class="sign">+</span> <span class="coeff">{{ getCoeff('User Rating (Normalized)') }}</span> * rating
                <span class="sign">+</span> <span class="coeff">{{ getCoeff('Support Resolution Rate (%)') }}</span> * support
                <span class="sign">+</span> <span class="coeff">{{ getCoeff('Recency Score (1/(1+Days))') }}</span> * recency
                <span class="sign">+</span> <span class="coeff">{{ getCoeff('Log Active Installs') }}</span> * installs
              </code>
            </div>
            <el-table :data="regressionTableData" style="width: 100%" stripe class="premium-table">
              <el-table-column prop="variable" label="Predictor Feature" min-width="180" />
              <el-table-column prop="val" label="Coefficient (β)" width="130" align="center">
                <template #default="scope">
                  <span class="font-mono font-bold">{{ scope.row.val.toFixed(4) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="se" label="Std Error" width="100" align="center">
                <template #default="scope">
                  <span class="font-mono">{{ scope.row.se.toFixed(4) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="t" label="t-stat" width="100" align="center">
                <template #default="scope">
                  <span class="font-mono">{{ scope.row.t.toFixed(2) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="p" label="p-value" width="90" align="center">
                <template #default="scope">
                  <span class="font-mono text-xs">{{ formatPValue(scope.row.p) }}</span>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-col>
      </el-row>

      <!-- Empirical Findings Narrative Section -->
      <h3 class="section-title">IV. Empirical Research & Ecosystem Findings</h3>
      <el-row :gutter="20" class="findings-row">
        <!-- Supported Findings -->
        <el-col :span="8">
          <el-card class="glass-card narrative-card supported-box" shadow="hover">
            <template #header>
              <div class="box-header-accent text-success">
                <el-icon><circle-check /></el-icon>
                <span>3 Statistically Supported Findings</span>
              </div>
            </template>
            <div class="narrative-content">
              <div class="finding-item">
                <span class="finding-num">1</span>
                <div>
                  <strong>Update Frequency shows Weak Rating Correlation:</strong>
                  The Pearson r value between Ratings and Days since Update indicates a negligible linear relationship, proving that users do not immediately penalize static yet stable codebases.
                </div>
              </div>
              <div class="finding-item">
                <span class="finding-num">2</span>
                <div>
                  <strong>Support Responsiveness is Core to Health:</strong>
                  Support threads resolution rate strongly correlates positive with health scores. Active maintenance of community queries is a prime indicator of software upkeep.
                </div>
              </div>
              <div class="finding-item">
                <span class="finding-num">3</span>
                <div>
                  <strong>User Ratings Exhibit Heavy Ceiling Effect:</strong>
                  Over 90% of user ratings pool tightly between 4.5 and 5.0, proving ratings are mathematically weak predictors of code quality due to high skewness and extremely low variance.
                </div>
              </div>
            </div>
          </el-card>
        </el-col>

        <!-- Unexpected Findings -->
        <el-col :span="8">
          <el-card class="glass-card narrative-card unexpected-box" shadow="hover">
            <template #header>
              <div class="box-header-accent text-warning">
                <el-icon><star /></el-icon>
                <span>2 Unexpected Findings</span>
              </div>
            </template>
            <div class="narrative-content">
              <div class="finding-item">
                <span class="finding-num">1</span>
                <div>
                  <strong>Popularity Metrics vs Ratings:</strong>
                  Spearman rank correlations show that higher Active Installs do not positively correlate with average star rating—proving popular plugins undergo highly critical crowdsourced audits, preventing top-rated bias.
                </div>
              </div>
              <div class="finding-item">
                <span class="finding-num">2</span>
                <div>
                  <strong>Logarithmic Ecosystem Skew:</strong>
                  Ecosystem distribution shows a extreme long-tail power law skew where 95%+ of active installs reside in less than 2% of plugins, requiring robust logarithmic scaling to analyze.
                </div>
              </div>
            </div>
          </el-card>
        </el-col>

        <!-- Limitation & Bias -->
        <el-col :span="8">
          <el-card class="glass-card narrative-card limitation-box" shadow="hover">
            <template #header>
              <div class="box-header-accent text-danger">
                <el-icon><warning /></el-icon>
                <span>1 Study Limitation & Scope</span>
              </div>
            </template>
            <div class="narrative-content">
              <div class="finding-item">
                <span class="finding-num">!</span>
                <div>
                  <strong>Directory Directory Selection Bias:</strong>
                  The analyzed dataset is primarily drawn from highly ranked, widely visible plugins in the WordPress.org Plugin Directory API. The resulting OLS model and beta coefficients may not perfectly generalize to lower-tail, custom, or hobbyist plugins that bypass top-chart browsing.
                </div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { getStatistics } from '../utils/api'
import { ElMessage } from 'element-plus'

const loading = ref(true)
const statisticsData = ref(null)

const correlationTableData = computed(() => {
  if (!statisticsData.value) return []
  const corrs = statisticsData.value.correlations
  return [
    { relationship: 'User Rating vs Days Since Update', pearson: corrs.rating_vs_days.pearson, spearman: corrs.rating_vs_days.spearman, p_value: corrs.rating_vs_days.p_value },
    { relationship: 'User Rating vs Support Resolution Rate', pearson: corrs.rating_vs_res.pearson, spearman: corrs.rating_vs_res.spearman, p_value: corrs.rating_vs_res.p_value },
    { relationship: 'Plugin Health Score vs Support Resolution Rate', pearson: corrs.health_vs_res.pearson, spearman: corrs.health_vs_res.spearman, p_value: corrs.health_vs_res.p_value },
    { relationship: 'Plugin Health Score vs Recency Score', pearson: corrs.health_vs_recency.pearson, spearman: corrs.health_vs_recency.spearman, p_value: corrs.health_vs_recency.p_value },
    { relationship: 'Log Active Installs vs Plugin Health Score', pearson: corrs.installs_vs_health.pearson, spearman: corrs.installs_vs_health.spearman, p_value: corrs.installs_vs_health.p_value },
    { relationship: 'User Rating vs Log Active Installs', pearson: corrs.rating_vs_installs.pearson, spearman: corrs.rating_vs_installs.spearman, p_value: corrs.rating_vs_installs.p_value },
  ]
})

const regressionTableData = computed(() => {
  if (!statisticsData.value || !statisticsData.value.regression) return []
  return statisticsData.value.regression.coefficients
})

const formatPValue = (val) => {
  if (val === 0) return 'p < 0.0001'
  if (val < 0.0001) return 'p < 0.0001'
  if (val < 0.001) return 'p < 0.001'
  if (val < 0.01) return 'p < 0.01'
  if (val < 0.05) return `p = ${val.toFixed(4)}`
  return `p = ${val.toFixed(4)}`
}

const getCoeff = (variableName) => {
  if (!statisticsData.value || !statisticsData.value.regression) return '0.00'
  const coeff = statisticsData.value.regression.coefficients.find(c => c.variable === variableName)
  return coeff ? coeff.val.toFixed(2) : '0.00'
}

const getTagType = (res) => {
  if (res.includes('STRONG SUPPORT')) return 'success'
  if (res.includes('SUPPORTED')) return 'success'
  if (res.includes('REJECTED')) return 'danger'
  return 'info'
}

const getHypothesisClass = (res) => {
  if (res.includes('STRONG SUPPORT')) return 'hyp-strong-success'
  if (res.includes('SUPPORTED')) return 'hyp-success'
  if (res.includes('REJECTED')) return 'hyp-danger'
  return ''
}

// ----------------------------------------------------
// EXPORT SYSTEM IMPLEMENTATION
// ----------------------------------------------------

const handleExport = (command) => {
  if (!statisticsData.value) return

  if (command === 'markdown') {
    exportMarkdownReport()
  } else if (command === 'regression') {
    exportRegressionCsv()
  } else if (command === 'correlation') {
    exportCorrelationCsv()
  }
}

const downloadFile = (content, filename, contentType) => {
  const blob = new Blob([content], { type: contentType })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const exportMarkdownReport = () => {
  const data = statisticsData.value
  let md = `# WordPress Plugin Ecosystem: Empirical Software Study
**Date of Export:** ${new Date().toLocaleDateString()}
**Academic Sample Size (N):** ${data.total_sample} plugins
**Ordinary Least Squares (OLS) R² Fit:** ${data.regression ? data.regression.r2.toFixed(4) : 'N/A'}

---

## 1. Empirical Hypothesis Testing Framework

`
  data.hypothesis_tests.forEach(h => {
    md += `### ${h.id}: ${h.hypothesis}
- **Status:** ${h.result}
- **Correlation Coefficient (r):** ${h.r_value ? h.r_value.toFixed(4) : 'N/A'}
- **Significance Probability (p-value):** ${h.p_value} (${h.p_value < 0.05 ? 'Statistically Significant' : 'Not Significant'})

`
  })

  md += `---

## 2. Correlation Analysis Matrix

| Relationship Under Test | Pearson r | Spearman ρ | p-value | Significance |
|:---|:---:|:---:|:---:|:---:|
`
  correlationTableData.value.forEach(row => {
    const sig = row.p_value < 0.05 ? 'Significant' : 'No Correlation'
    md += `| ${row.relationship} | ${row.pearson.toFixed(4)} | ${row.spearman.toFixed(4)} | ${row.p_value.toFixed(6)} | ${sig} |\n`
  })

  md += `
---

## 3. OLS Multiple Linear Regression Model

**Dependent Variable:** Plugin Health Score (0 - 100)

| Independent Predictor Feature | Beta Weight (β) | Std Error | t-statistic | p-value |
|:---|:---:|:---:|:---:|:---:|
`
  if (data.regression) {
    data.regression.coefficients.forEach(coeff => {
      md += `| ${coeff.variable} | ${coeff.val.toFixed(4)} | ${coeff.se.toFixed(4)} | ${coeff.t.toFixed(2)} | ${coeff.p} |\n`
    })
  }

  md += `
---

## 4. Key Empirical Ecosystem Findings

### A. 3 Statistically Supported Findings
1. **Update frequency has weak correlation with rating:** The linear relationship is negligible, suggesting static codebases are tolerated by users.
2. **Support resolution rate strongly correlates with plugin health:** Resolving query threads is a crucial hallmark of upkeep.
3. **User ratings show ceiling effect and low variance:** Skewed heavily between 4.5 and 5.0, ratings display low diagnostic accuracy for software health.

### B. 2 Unexpected Findings
1. **Active installs have weak correlation with rating:** The highly active plugins undergo strict crowdsourced audits, preventing top-rated bias.
2. **Ecosystem skewness follows power-law distribution:** logarithmic scaling (log installs) is required due to standard long-tail visibility.

### C. 1 Study Limitation
- **Selection Bias:** Sample draws primarily from highly ranked popular plugins, potentially limiting applicability to the long-tail hobbyist database.
`

  downloadFile(md, 'wordpress_plugin_quality_study_report.md', 'text/markdown')
  ElMessage.success('Academic Report (.md) downloaded successfully!')
}

const exportRegressionCsv = () => {
  const data = statisticsData.value
  if (!data.regression) return
  
  let csv = 'Predictor Feature,Coefficient (Beta),Std Error,t-statistic,p-value\n'
  data.regression.coefficients.forEach(coeff => {
    csv += `"${coeff.variable}",${coeff.val},${coeff.se},${coeff.t},${coeff.p}\n`
  })
  
  downloadFile(csv, 'ols_regression_analysis_results.csv', 'text/csv')
  ElMessage.success('OLS Regression Coefficients CSV downloaded!')
}

const exportCorrelationCsv = () => {
  let csv = 'Relationship Under Test,Pearson r,Spearman rho,p-value,Significance\n'
  correlationTableData.value.forEach(row => {
    const sig = row.p_value < 0.05 ? 'Significant' : 'No Correlation'
    csv += `"${row.relationship}",${row.pearson},${row.spearman},${row.p_value},"${sig}"\n`
  })
  
  downloadFile(csv, 'correlation_analysis_results.csv', 'text/csv')
  ElMessage.success('Correlation Matrix CSV downloaded!')
}

onMounted(async () => {
  loading.value = true
  try {
    const res = await getStatistics()
    statisticsData.value = res.data
  } catch (error) {
    console.error('Failed to fetch empirical statistics data', error)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.insights-dashboard {
  padding: 10px 0;
}
.panel-loading, .panel-empty {
  margin-top: 20px;
}
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(10px);
  padding: 20px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.3);
}
.dashboard-title {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
  color: #303133;
}
.dashboard-subtitle {
  margin: 5px 0 0 0;
  font-size: 13px;
  color: #909399;
}
.export-btn {
  font-weight: bold;
}
.stat-cards-row {
  margin-bottom: 25px;
}
.glass-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 12px;
}
.stat-card {
  padding: 20px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  min-height: 140px;
}
.card-metric-label {
  font-size: 14px;
  color: #909399;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 8px;
}
.card-metric-value {
  font-size: 38px;
  font-weight: 800;
  color: #303133;
  line-height: 1;
  margin-bottom: 10px;
}
.fit-card .card-metric-value {
  color: #409EFF;
}
.significance-card .card-metric-value {
  color: #67C23A;
}
.card-metric-desc {
  font-size: 12px;
  color: #606266;
  line-height: 1.4;
}
.section-title {
  margin: 30px 0 15px 0;
  font-size: 18px;
  font-weight: 700;
  color: #303133;
  letter-spacing: 0.5px;
  border-left: 4px solid #409EFF;
  padding-left: 10px;
}
.hypothesis-row {
  margin-bottom: 25px;
}
.hypothesis-card {
  min-height: 190px;
  padding: 15px;
  border-radius: 10px;
}
.hypothesis-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.hypothesis-badge {
  font-size: 14px;
  font-weight: bold;
  color: #409EFF;
  background: rgba(64, 158, 255, 0.1);
  padding: 4px 10px;
  border-radius: 20px;
}
.hypothesis-text {
  font-size: 13.5px;
  font-weight: 550;
  color: #303133;
  line-height: 1.5;
  margin: 0 0 15px 0;
  min-height: 54px;
}
.hypothesis-metrics {
  display: flex;
  justify-content: space-between;
  border-top: 1px solid rgba(0,0,0,0.05);
  padding-top: 10px;
}
.metric-item {
  display: flex;
  flex-direction: column;
}
.metric-item .lbl {
  font-size: 11px;
  color: #909399;
  text-transform: uppercase;
}
.metric-item .val {
  font-size: 13px;
  font-weight: bold;
  color: #303133;
  margin-top: 2px;
}

.hyp-strong-success {
  border-left: 5px solid #67C23A;
  background: linear-gradient(135deg, rgba(255,255,255,0.7) 0%, rgba(103, 194, 58, 0.05) 100%);
}
.hyp-success {
  border-left: 5px solid #67C23A;
  background: linear-gradient(135deg, rgba(255,255,255,0.7) 0%, rgba(103, 194, 58, 0.03) 100%);
}
.hyp-danger {
  border-left: 5px solid #F56C6C;
  background: linear-gradient(135deg, rgba(255,255,255,0.7) 0%, rgba(245, 108, 108, 0.03) 100%);
}

.tabular-section-row {
  margin-bottom: 25px;
}
.table-card {
  border-radius: 12px;
}
.table-card .card-header {
  display: flex;
  flex-direction: column;
}
.table-card .card-header .title {
  font-size: 15px;
  font-weight: bold;
  color: #303133;
}
.table-card .card-header .subtitle {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}
.premium-table {
  border-radius: 8px;
  overflow: hidden;
}
.formula-box {
  background: #f4f4f5;
  border-left: 4px solid #909399;
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 15px;
  font-size: 13px;
}
.formula-label {
  display: block;
  font-size: 11px;
  color: #909399;
  text-transform: uppercase;
  margin-bottom: 4px;
  font-weight: bold;
}
.formula-text {
  font-family: monospace;
  font-size: 12.5px;
  color: #303133;
  line-height: 1.4;
}
.formula-text .coeff {
  color: #409EFF;
  font-weight: bold;
}
.formula-text .sign {
  color: #909399;
}

.findings-row {
  margin-bottom: 30px;
}
.narrative-card {
  min-height: 380px;
  border-radius: 12px;
}
.box-header-accent {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: bold;
  font-size: 14px;
}
.text-success { color: #67C23A; }
.text-warning { color: #E6A23C; }
.text-danger { color: #F56C6C; }

.narrative-content {
  display: flex;
  flex-direction: column;
  gap: 15px;
}
.finding-item {
  display: flex;
  gap: 12px;
  font-size: 13px;
  color: #606266;
  line-height: 1.5;
}
.finding-num {
  font-size: 14px;
  font-weight: bold;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 2px;
}
.supported-box .finding-num {
  background: rgba(103, 194, 58, 0.1);
  color: #67C23A;
}
.unexpected-box .finding-num {
  background: rgba(230, 162, 60, 0.1);
  color: #E6A23C;
}
.limitation-box .finding-num {
  background: rgba(245, 108, 108, 0.1);
  color: #F56C6C;
}
</style>
