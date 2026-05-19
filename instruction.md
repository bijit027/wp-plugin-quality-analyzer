# WordPress Plugin Quality Analyzer — Vibe Coding Instructions

**Plugin Name:** WP Plugin Quality Analyzer  
**Author:** Bijit Deb  
**Purpose:** Research tool to collect, analyze and visualize software quality metrics from WordPress.org — for MS CS thesis at Florida Polytechnic University  
**GitHub Repo Name:** `wp-plugin-quality-analyzer`

---

## How to use this file

This file is split into **8 chunks**. Each chunk is one focused coding session.  
For each chunk, paste the instruction into your AI coding tool (Cursor, GitHub Copilot, Claude Code, etc.) and complete it fully before moving to the next chunk.  
Do NOT try to build everything at once.  
Test each chunk before moving forward.

---

## Tech Stack

- **Backend:** PHP 8.0+, WordPress hooks and APIs
- **Database:** WordPress custom tables via `$wpdb`
- **Frontend:** React (via `@vitejs/plugin-vue — Vite build tool
- **API:** WordPress.org Plugin Info API v1.2
- **Export:** CSV via PHP, PDF via mPDF library
- **Code Style:** WordPress Coding Standards (WPCS)

---

## Frontend Tech Stack

- **Framework:** Vue 3 (Composition API)
- **UI Library:** Element Plus
- **Charts:** Chart.js with vue-chartjs wrapper
- **HTTP:** axios
- **Build tool:** Vite

> Structure the frontend however you prefer. Use your own folder layout, component naming, and organization style.

---

## Plugin Health Score Formula

This is the core research metric. Use this formula in BOTH PHP and JS:

```
PHS = (UpdateScore × 0.30) + (RatingScore × 0.25) + (ResolutionScore × 0.25) + (ResponseScore × 0.20)

Where:
- UpdateScore     = max(0, 1 - (days_since_update / 365))         → 0 to 1
- RatingScore     = rating / 100                                   → 0 to 1  (API gives 0-100)
- ResolutionScore = support_threads_resolved / support_threads     → 0 to 1  (0 if no threads)
- ResponseScore   = 1 if avg_response_time < 48hrs, 0.5 if < 7days, 0.1 otherwise

Final PHS is multiplied by 100 for display (0–100 scale)
Abandonment threshold: PHS < 40 = at risk, PHS < 20 = abandoned
```

---

## WordPress.org API Reference

Base URL: `https://api.wordpress.org/plugins/info/1.2/`

```
GET https://api.wordpress.org/plugins/info/1.2/?action=query_plugins
    &request[per_page]=100
    &request[page]=1
    &request[browse]=popular
    &request[fields][active_installs]=1
    &request[fields][last_updated]=1
    &request[fields][rating]=1
    &request[fields][num_ratings]=1
    &request[fields][support_threads]=1
    &request[fields][support_threads_resolved]=1
    &request[fields][downloaded]=1
    &request[fields][added]=1
    &request[fields][contributors]=1
```

Response structure per plugin:
```json
{
  "slug": "woocommerce",
  "name": "WooCommerce",
  "active_installs": 5000000,
  "rating": 82,
  "num_ratings": 4023,
  "last_updated": "2024-11-01 3:14pm GMT",
  "support_threads": 412,
  "support_threads_resolved": 389,
  "downloaded": 281234567,
  "added": "2011-09-27"
}
```

---

---

# CHUNK 1 — Plugin Bootstrap and Database

**Paste this into your AI coding tool:**

---

Create a WordPress plugin called "WP Plugin Quality Analyzer".

### Main plugin file: `wp-plugin-quality-analyzer.php`

Add this header:
```php
/**
 * Plugin Name: WP Plugin Quality Analyzer
 * Plugin URI: https://github.com/bijit027/wp-plugin-quality-analyzer
 * Description: Empirical research tool for analyzing software quality metrics across WordPress plugins. Built for MS CS thesis research.
 * Version: 1.0.0
 * Author: Bijit Deb
 * Author URI: https://profiles.wordpress.org/bijit027
 * License: GPL v2 or later
 * Text Domain: wp-pqa
 */
```

- Define constants: `WPPQA_VERSION`, `WPPQA_PATH`, `WPPQA_URL`, `WPPQA_DB_VERSION`
- On plugin activation, call the database creation method
- Autoload all classes from `includes/` folder
- Hook `admin_menu` to register admin pages
- Hook `rest_api_init` to register REST routes
- Hook `admin_enqueue_scripts` to load React build and pass `wpApiSettings` nonce

### Database file: `includes/class-database.php`

Create a class `WPPQA_Database` with a static method `create_tables()` that creates this table using `$wpdb->prefix . 'pqa_plugins'`:

```sql
CREATE TABLE {prefix}pqa_plugins (
  id              bigint(20) NOT NULL AUTO_INCREMENT,
  slug            varchar(200) NOT NULL,
  name            varchar(500) NOT NULL,
  active_installs bigint(20) DEFAULT 0,
  rating          int(11) DEFAULT 0,
  num_ratings     int(11) DEFAULT 0,
  last_updated    datetime DEFAULT NULL,
  days_since_update int(11) DEFAULT 0,
  support_threads int(11) DEFAULT 0,
  support_threads_resolved int(11) DEFAULT 0,
  resolution_rate decimal(5,2) DEFAULT 0,
  downloaded      bigint(20) DEFAULT 0,
  added           date DEFAULT NULL,
  health_score    decimal(5,2) DEFAULT 0,
  is_abandoned    tinyint(1) DEFAULT 0,
  fetched_at      datetime DEFAULT CURRENT_TIMESTAMP,
  updated_at      datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  KEY health_score (health_score),
  KEY is_abandoned (is_abandoned)
) {charset_collate};
```

Also add these methods to `WPPQA_Database`:
- `get_all_plugins($orderby, $order, $filter)` — returns all plugins from DB with optional filtering
- `get_plugin_count()` — returns total count
- `get_abandoned_count()` — returns count where is_abandoned = 1
- `get_average_health_score()` — returns average health_score
- `upsert_plugin($data)` — insert or update on duplicate slug
- `clear_all()` — truncate the table
- `get_stats_summary()` — returns array with: total, abandoned, avg_rating, avg_health_score, avg_resolution_rate, avg_installs

### Test when done:
- Activate the plugin — table should be created in database
- Check `wp-admin` — menu item should appear (even if empty page for now)

---

---

# CHUNK 2 — WordPress.org API Fetcher

**Paste this into your AI coding tool:**

---

Create `includes/class-api-fetcher.php` — class `WPPQA_API_Fetcher`.

This class is responsible for fetching plugin data from the WordPress.org API and saving it to the database.

### Method: `fetch_plugins($page, $per_page, $browse)`

- Make a `wp_remote_get()` call to the WordPress.org API
- URL: `https://api.wordpress.org/plugins/info/1.2/`
- Pass all fields listed in the API Reference section above
- Handle errors gracefully — return `WP_Error` on failure
- Return decoded JSON response as associative array

### Method: `process_and_save($plugins_array)`

For each plugin in the array:
1. Calculate `days_since_update` from `last_updated` field
2. Calculate `resolution_rate` = resolved / total threads × 100 (handle division by zero)
3. Calculate `health_score` using the Plugin Health Score formula defined above
4. Set `is_abandoned` = 1 if `days_since_update` > 365, else 0
5. Call `WPPQA_Database::upsert_plugin()` with the processed data
6. Return count of successfully saved plugins

### Method: `fetch_all_pages($total_plugins, $per_page)`

- This is the main method called from REST API
- Loop through pages: fetch page 1, page 2, page 3... until total reached
- Add `sleep(1)` between requests to respect WordPress.org rate limits
- Return array: `['fetched' => int, 'saved' => int, 'errors' => array]`

### Method: `get_fetch_status()`

- Store fetch progress in WordPress transient `wppqa_fetch_status`
- Return current status: `['status' => 'idle|running|complete|error', 'fetched' => int, 'total' => int, 'message' => string]`

### Test when done:
- Call `fetch_plugins(1, 10, 'popular')` manually from `wp_footer` action temporarily
- Verify 10 plugins are saved to database table
- Check health scores are calculated correctly

---

---

# CHUNK 3 — Plugin Health Score Calculator

**Paste this into your AI coding tool:**

---

Create `includes/class-health-score.php` — class `WPPQA_Health_Score`.

This is the core research metric class. Every calculation must match the formula exactly.

### Static method: `calculate($plugin_data)`

Input: associative array with plugin data  
Output: float between 0 and 100

```
UpdateScore     = max(0, 1 - (days_since_update / 365))
RatingScore     = rating / 100
ResolutionScore = (support_threads > 0) ? support_threads_resolved / support_threads : 0.5
ResponseScore   = 1.0 (default — full scoring requires forum scraping, Phase 2)

PHS = (UpdateScore × 0.30) + (RatingScore × 0.25) + (ResolutionScore × 0.25) + (ResponseScore × 0.20)
Final = round(PHS × 100, 2)
```

### Static method: `get_risk_label($score)`

- Score >= 70: return `['label' => 'Healthy', 'color' => 'green']`
- Score >= 50: return `['label' => 'Moderate', 'color' => 'orange']`
- Score >= 30: return `['label' => 'At Risk', 'color' => 'red']`
- Score < 30:  return `['label' => 'Abandoned', 'color' => 'darkred']`

### Static method: `get_score_breakdown($plugin_data)`

Returns full breakdown array showing each component score separately — useful for displaying in the research report and thesis.

```php
return [
  'update_score'     => $update_score,
  'rating_score'     => $rating_score,
  'resolution_score' => $resolution_score,
  'response_score'   => $response_score,
  'final_score'      => $final,
  'risk_label'       => self::get_risk_label($final),
  'weights'          => ['update' => 0.30, 'rating' => 0.25, 'resolution' => 0.25, 'response' => 0.20]
];
```

### Static method: `recalculate_all()`

- Fetch all plugins from DB
- Recalculate health score for each
- Update DB record
- Return count of updated records
- This is useful when you change the formula weights during research

### Test when done:
- Pass a mock plugin array and verify PHS calculation is correct
- Test edge cases: plugin with 0 support threads, plugin updated today, plugin not updated in 2 years

---

---

# CHUNK 4 — WordPress REST API Endpoints

**Paste this into your AI coding tool:**

---

Create `includes/class-rest-api.php` — class `WPPQA_REST_API`.

Register all endpoints in `register_routes()` method, called on `rest_api_init`.

### Endpoints to create:

**POST `/wp-json/wppqa/v1/fetch`**
- Permission: `manage_options` (admin only)
- Body params: `total` (int, default 100), `per_page` (int, default 25)
- Starts the fetch process
- Returns: `{ success: true, message: string }`

**GET `/wp-json/wppqa/v1/status`**
- Permission: `manage_options`
- Returns current fetch status from transient
- Returns: `{ status: string, fetched: int, total: int, message: string }`

**GET `/wp-json/wppqa/v1/plugins`**
- Permission: `manage_options`
- Query params: `orderby`, `order`, `filter`, `page`, `per_page`
- Returns paginated plugin list from DB
- Returns: `{ plugins: array, total: int, pages: int }`

**GET `/wp-json/wppqa/v1/stats`**
- Permission: `manage_options`
- Returns summary statistics for dashboard metrics grid
- Returns: `{ total, abandoned, abandoned_percent, avg_health_score, avg_rating, avg_resolution_rate, avg_installs }`

**GET `/wp-json/wppqa/v1/chart-data`**
- Permission: `manage_options`
- Query param: `type` (rating|abandonment|update_frequency|support|health_score)
- Returns pre-formatted data for each chart type
- For rating: buckets of 1-2, 2-3, 3-4, 4-4.5, 4.5-5 stars
- For abandonment: count of active vs abandoned
- For update_frequency: buckets of ≤30, 31-90, 91-180, 181-365, 365+ days
- For health_score: distribution across 0-20, 20-40, 40-60, 60-80, 80-100 ranges

**POST `/wp-json/wppqa/v1/export`**
- Permission: `manage_options`
- Body param: `format` (csv|json)
- Generates and returns download URL for the export file
- CSV should include ALL columns including calculated health_score and risk_label

**POST `/wp-json/wppqa/v1/clear`**
- Permission: `manage_options`
- Clears all plugin data from DB
- Returns: `{ success: true, message: string }`

### Test when done:
- Visit each endpoint URL in browser (GET ones)
- Use Postman or fetch() in browser console to test POST ones
- Verify nonce authentication is working

---

---

# CHUNK 5 — Vue 3 + Element Plus Setup and Dashboard Shell

**Paste this into your AI coding tool:**

---

Set up the Vue 3 frontend using Vite, Element Plus, and Chart.js.

### package.json

```json
{
  "name": "wp-plugin-quality-analyzer",
  "version": "1.0.0",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "vue": "^3.4.0",
    "element-plus": "^2.7.0",
    "@element-plus/icons-vue": "^2.3.0",
    "chart.js": "^4.4.0",
    "vue-chartjs": "^5.3.0",
    "axios": "^1.6.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.0",
    "vite": "^5.0.0"
  }
}
```

### vite.config.js

```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: 'assets/build',
    rollupOptions: {
      input: 'src/main.js',
      output: {
        entryFileNames: 'app.js',
        assetFileNames: 'app.css'
      }
    }
  }
})
```

### src/utils/api.js

Create all API call functions using axios with WordPress nonce from `window.wppqaData`:

```javascript
import axios from 'axios'

const api = axios.create({
  baseURL: window.wppqaData?.apiUrl || '/wp-json/wppqa/v1',
  headers: { 'X-WP-Nonce': window.wppqaData?.nonce || '' }
})

export const fetchPlugins = (params) => api.get('/plugins', { params })
export const startFetch = (total, perPage) => api.post('/fetch', { total, per_page: perPage })
export const getFetchStatus = () => api.get('/status')
export const getStats = () => api.get('/stats')
export const getChartData = (type) => api.get('/chart-data', { params: { type } })
export const exportData = (format) => api.post('/export', { format })
export const clearData = () => api.post('/clear')
```

### src/main.js

```javascript
import { createApp } from 'vue'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import App from './App.vue'

const app = createApp(App)
app.use(ElementPlus)
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}
app.mount('#wppqa-root')
```

### src/App.vue

Main dashboard shell using Element Plus layout:
- `el-container` with header and main content
- Header: "WP Plugin Quality Analyzer — Research Dashboard" + subtitle "MS CS Thesis Research — Bijit Deb"
- `el-tabs` with 4 tabs: Overview | Data Table | Research Insights | Export
- On `mounted()`, call `getStats()` and store in reactive state
- Show `el-skeleton` while loading
- Show `el-empty` with a fetch button if no data in DB yet

### includes/class-admin-pages.php

Register WordPress admin menu:
- Top level menu: "Plugin Analyzer" with dashicon `dashicons-chart-bar`
- Capability: `manage_options`
- Page callback renders: `<div id="wppqa-root"></div>`
- Enqueue the Vite build output and pass localized data:

```php
wp_enqueue_style('wppqa-admin', plugin_dir_url(__FILE__) . '../assets/build/app.css');
wp_enqueue_script('wppqa-admin', plugin_dir_url(__FILE__) . '../assets/build/app.js', [], WPPQA_VERSION, true);
wp_localize_script('wppqa-admin', 'wppqaData', [
  'apiUrl'  => rest_url('wppqa/v1'),
  'nonce'   => wp_create_nonce('wp_rest'),
  'siteUrl' => get_site_url(),
]);
```

### Test when done:
- Run `npm install` then `npm run build`
- Activate plugin and visit wp-admin
- Should see "Plugin Analyzer" in admin menu
- Element Plus dashboard shell should render with tabs
- Empty state should show if no data fetched yet

---

---

# CHUNK 6 — Fetch Panel and Data Collection UI

**Paste this into your AI coding tool:**

---

Build the data fetching UI component and wire it to the backend.

### src/components/FetchPanel.vue

This component handles data collection from WordPress.org API.

**UI Elements:**
- Section title: "Collect Plugin Data"
- Description text explaining what the tool does
- Dropdown: "Number of plugins to fetch" — options: 50, 100, 200, 500
- Dropdown: "Browse by" — options: popular, new, updated, top-rated
- Button: "Start Fetching" — calls `startFetch()` API
- Progress bar: animated, shows `fetched / total` percentage
- Status message: shows current page being fetched
- Button: "Clear All Data" — with confirmation dialog, calls `clearData()` API
- Last fetched timestamp: "Data last collected: X hours ago"

**Behavior:**
- When fetch starts, poll `getFetchStatus()` every 2 seconds
- Update progress bar and status message on each poll
- When status is `complete` — stop polling, show success message, trigger parent to refresh stats
- When status is `error` — stop polling, show error message in red
- Disable "Start Fetching" button while fetch is running
- Show spinner inside button while running

**State to manage:**
```javascript
const [fetchCount, setFetchCount] = useState(100);
const [browse, setBrowse] = useState('popular');
const [status, setStatus] = useState('idle'); // idle | running | complete | error
const [progress, setProgress] = useState({ fetched: 0, total: 0 });
const [statusMessage, setStatusMessage] = useState('');
```

### src/components/MetricsGrid.vue

Displays 5 summary metric cards in a responsive grid:
- Total plugins analyzed
- Average Plugin Health Score (with color coding)
- Abandoned plugins count and percentage
- Average user rating (out of 5)
- Average support resolution rate

Each card has: label, large number value, small subtitle

### Test when done:
- Click "Start Fetching" with 50 plugins
- Progress bar should animate
- After completion, metrics grid should update with real numbers
- Clear data should reset everything

---

---

# CHUNK 7 — Charts and Data Table

**Paste this into your AI coding tool:**

---

Build all visualization components using Chart.js.

### Global Chart Setup in src/index.js

```javascript
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title } from 'chart.js';
ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title);
```

---

### src/components/RatingChart.vue

Bar chart showing rating distribution.

- Fetch data: `getChartData('rating')`
- X axis labels: `['1–2 ★', '2–3 ★', '3–4 ★', '4–4.5 ★', '4.5–5 ★']`
- Bar color: `#378ADD`
- Title: "User Rating Distribution"
- Show count labels on top of each bar
- Add research note below chart: "Higher concentration in 4.5–5 ★ range may indicate rating inflation — a known bias in plugin directories."

---

### src/components/AbandonmentChart.vue

Doughnut chart showing active vs abandoned.

- Fetch data: `getChartData('abandonment')`
- Colors: Active = `#1D9E75`, Abandoned = `#E24B4A`
- Title: "Plugin Maintenance Status"
- Show percentage labels
- Add research note: "Plugins not updated in 365+ days are classified as abandoned per the operational definition in this study."

---

### src/components/UpdateFrequencyChart.vue

Bar chart showing days since last update distribution.

- Fetch data: `getChartData('update_frequency')`
- X axis: `['≤30 days', '31–90', '91–180', '181–365', '>365 (Abandoned)']`
- Colors: first 4 bars green/orange gradient, last bar red `#E24B4A`
- Title: "Update Frequency Distribution"

---

### src/components/SupportChart.vue

Horizontal bar chart showing support resolution rates.

- Fetch data: `getChartData('support')`
- Show resolution rate buckets: 0–25%, 25–50%, 50–75%, 75–100%
- Color gradient from red to green
- Title: "Support Thread Resolution Rate"
- Add research note: "Resolution rate is used as a proxy for maintainer engagement and responsiveness."

---

### src/components/HealthScoreChart.vue

Bar chart showing Plugin Health Score distribution.

- Fetch data: `getChartData('health_score')`
- X axis: `['0–20 (Abandoned)', '20–40 (At Risk)', '40–60 (Moderate)', '60–80 (Good)', '80–100 (Healthy)']`
- Colors: darkred, red, orange, lightgreen, green
- Title: "Plugin Health Score Distribution"
- This is your ORIGINAL research metric — label it clearly

---

### src/components/PluginTable.vue

Sortable, filterable data table showing all plugins.

**Columns:**
- Rank (#)
- Plugin Name (with link to wordpress.org/plugins/slug)
- Active Installs
- Rating (stars display)
- Health Score (colored badge)
- Days Since Update
- Resolution Rate
- Status badge (Healthy / Moderate / At Risk / Abandoned)

**Controls above table:**
- Sort by dropdown: Installs | Rating | Health Score | Days Since Update | Resolution Rate
- Filter dropdown: All | Healthy (≥70) | Moderate (50–70) | At Risk (30–50) | Abandoned (<30)
- Search input: filter by plugin name
- Showing X of Y plugins

**Pagination:**
- Show 25 per page
- Previous / Next buttons
- Page number display

### Test when done:
- All 5 charts should render with real data
- Table should be sortable and filterable
- Charts should be readable and properly labeled

---

---

# CHUNK 8 — Research Insights, Export and Final Polish

**Paste this into your AI coding tool:**

---

Build the final two tab panels and polish the whole plugin.

### src/components/InsightsPanel.vue

Auto-generates research insights from the collected data.

Fetch stats from `/stats` endpoint and generate these insight blocks:

**Insight 1 — Abandonment Rate**
```
"{abandoned_count} out of {total} plugins ({abandoned_percent}%) have not been 
updated in over 365 days. This rate is notable given the sample comprises 
the most widely installed plugins in the WordPress ecosystem."
```

**Insight 2 — Rating vs Maintenance Correlation**
```
"Actively maintained plugins (updated within 90 days) have an average rating 
of {active_avg_rating}/5, compared to {abandoned_avg_rating}/5 for abandoned 
plugins — suggesting a measurable relationship between update frequency 
and user satisfaction (RQ2)."
```

**Insight 3 — Support Quality**
```
"The average support thread resolution rate across the dataset is {avg_resolution}%.
Plugins in the top quartile of resolution rate show significantly higher average 
ratings, supporting the hypothesis that maintainer responsiveness is a key 
predictor of perceived plugin quality (RQ3)."
```

**Insight 4 — Health Score Distribution**
```
"{healthy_count} plugins ({healthy_percent}%) scored above 70 on the Plugin 
Health Score — classified as Healthy. {atrisk_count} plugins are classified 
as At Risk or Abandoned."
```

Also show **Research Questions section** with all 5 RQs listed and a status badge (Preliminary Evidence / In Progress / Pending).

---

### src/components/ExportPanel.vue

**CSV Export:**
- Button: "Download Dataset CSV"
- Calls `exportData('csv')` — downloads CSV file
- Shows columns: all plugin fields + health_score + risk_label + score_breakdown
- File name: `wordpress-plugin-quality-dataset-{date}.csv`

**JSON Export:**
- Button: "Download Dataset JSON"
- Downloads full JSON dataset

**Research Paper Section:**
- Title: "Research Paper Outline"
- Shows the 7-chapter structure from the thesis proposal
- Button: "Copy APA Citation for this Dataset"

```
Deb, B. (2025). WordPress Plugin Quality Dataset [Data set]. 
Collected via WordPress.org Plugin API. 
GitHub: https://github.com/bijit027/wp-plugin-quality-analyzer
```

**Methodology Notes:**
- Shows the Plugin Health Score formula clearly
- Shows data collection date and source
- Shows API endpoint used
- This section is designed to be screenshot and included directly in the thesis

---

### Final Polish — apply to all components:

**Loading states:**
- Every component that fetches data must show a spinner while loading
- Use WordPress dashicon `dashicons-update spin` class for spinner

**Error states:**
- Every API call must handle errors
- Show red error message with retry button

**Empty states:**
- If no data in DB, show: "No data collected yet. Go to the Overview tab and click Fetch Plugins to begin."

**Responsive layout:**
- Charts should be readable on smaller screens
- Table should be horizontally scrollable on small screens

**Admin CSS — assets/css/admin.css:**
- Clean card-based layout for the dashboard
- Consistent color scheme: primary blue `#0073AA` (WordPress blue), success green `#1D9E75`, danger red `#E24B4A`
- Metric cards with subtle shadow and border
- Chart containers with white background and padding
- Table with alternating row colors

---

### readme.txt

Write a proper WordPress plugin readme with:
- Plugin description mentioning it is a research tool
- Installation instructions
- FAQ: "Is this suitable for production sites?" → "This is a research tool designed for academic data collection."
- Changelog

---

### Final test checklist before publishing to GitHub:

- [ ] Plugin activates without errors
- [ ] Database table is created on activation
- [ ] Can fetch 100 plugins from WordPress.org API
- [ ] All 5 charts render correctly with real data
- [ ] Plugin Health Score is calculated for all plugins
- [ ] Data table is sortable and filterable
- [ ] CSV export downloads correctly
- [ ] Admin page is mobile responsive
- [ ] No PHP warnings or notices in debug mode
- [ ] All REST endpoints return correct data
- [ ] Nonce verification works on POST endpoints
- [ ] Plugin deactivates and reactivates cleanly

---

## What to tell Dr. Elish about this plugin

Once you have a working version on GitHub, add this to your professor email:

> "To support this research, I have developed an open source WordPress plugin — 
> WP Plugin Quality Analyzer — that programmatically collects and visualizes 
> software quality metrics from the WordPress.org Plugin Directory. The tool 
> implements the Plugin Health Score framework and generates the dataset used 
> in this study. It is publicly available at github.com/bijit027/wp-plugin-quality-analyzer, 
> making the research fully reproducible."

---

## Build Order Summary

| Chunk | What you build | Est. time |
|-------|---------------|-----------|
| 1 | Plugin bootstrap + database | 1–2 hours |
| 2 | API fetcher | 1–2 hours |
| 3 | Health score calculator | 1 hour |
| 4 | REST API endpoints | 2 hours |
| 5 | React setup + dashboard shell | 2 hours |
| 6 | Fetch panel + metrics grid | 2 hours |
| 7 | All charts + data table | 3 hours |
| 8 | Insights + export + polish | 2 hours |
| **Total** | **Complete plugin** | **~14–16 hours** |

Build one chunk per day and you will have a complete working plugin in 2 weeks.

---

*Built for: MS CS Thesis Research — Florida Polytechnic University*  
*Author: Bijit Deb | bijitdeb70@gmail.com | github.com/bijit027*