CHART IMPLEMENTATION NOTES
Package setup

Install chart.js and vue-chartjs via npm
Register all ChartJS components ONCE globally in main.js — never per component
Components to register: CategoryScale, LinearScale, BarElement, ArcElement, PointElement, LineElement, Title, Tooltip, Legend

Available chart components from vue-chartjs

Bar chart → Bar
Pie / Doughnut → Doughnut
Scatter plot → Scatter
Line chart → Line

Charts to build (5 total)

Health Score distribution — Bar chart — 4 buckets: 0–40, 40–60, 60–80, 80–100
Rating distribution — Bar chart — 5 buckets: <2★, 2–3★, 3–4★, 4–4.5★, 4.5–5★
Update frequency — Bar chart — 5 buckets: ≤30 days, 31–90, 91–180, 181–365, >365
Active vs Abandoned — Doughnut chart — 2 segments only
Resolution rate vs Health score — Scatter chart — one dot per plugin

Colors to use (hardcoded hex — required for Chart.js canvas)

Healthy / Active / Good → #1D9E75
Moderate / Slowing → #BA7517
At risk → #E24B4A
Abandoned / Critical → #A32D2D
General bar (rating chart) → #378ADD
Scatter dots → rgba(127, 119, 221, 0.6)

Data flow pattern

Each chart component receives data from parent via props
Parent fetches from your REST API endpoint /wp-json/wppqa/v1/chart-data?type=X
Use Vue computed() to transform raw API response into Chart.js data object
Chart auto-updates reactively when prop data changes — no manual refresh needed

Important Chart.js rules

Always wrap <canvas> in a <div> with explicit pixel height (e.g. 250px)
Set responsive: true and maintainAspectRatio: false in options
Disable default legend with plugins: { legend: { display: false } }
Build custom HTML legend using Element Plus instead for better styling
Canvas cannot read CSS variables — always use hardcoded hex colors
For scatter chart: set axis min/max slightly beyond data range to avoid clipping

Element Plus integration

Wrap each chart in el-card component
Show el-skeleton while data is loading
Show el-empty if no data available yet
Use el-tag for the custom legend items beside each chart