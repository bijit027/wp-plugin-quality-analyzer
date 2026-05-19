# Statistical Analysis Implementation Guide
WordPress Plugin Ecosystem Study

## 1. Goal

Transform a dataset of ~5,000 WordPress plugins into statistically valid research findings using correlation analysis, hypothesis testing, and regression modeling.

The objective is to move from descriptive analytics to empirical software engineering research.

---

## 2. Dataset Structure

Each plugin record includes:

- rating (0–100 or 0–5)
- num_ratings
- active_installs
- last_updated
- days_since_update
- support_threads
- support_threads_resolved
- resolution_rate
- downloaded
- health_score
- is_abandoned

---

## 3. Data Preprocessing

### 3.1 Normalization

Normalize all numeric fields:

- rating → rating / 100
- active_installs → log(1 + active_installs)
- downloads → log(1 + downloads)
- support_threads → log(1 + support_threads)

---

### 3.2 Missing Values

- resolution_rate missing → set to 0
- support data missing → mark as 0 or "no_support"
- last_updated missing → exclude from time-based analysis

---

### 3.3 Feature Engineering

Create derived variables:

- recency_score = 1 / (1 + days_since_update)
- support_efficiency = support_threads_resolved / (support_threads + 1)
- engagement_score = log(1 + active_installs) + log(1 + downloads)

---

## 4. Statistical Analysis Plan

---

## 4.1 Correlation Analysis

Compute relationships between variables using:

### Pearson Correlation
Used for linear relationships:

- rating vs days_since_update
- rating vs resolution_rate
- health_score vs resolution_rate
- health_score vs recency_score

### Spearman Correlation
Used for ranked or non-linear relationships:

- installs vs health_score
- rating vs popularity metrics

Output format:

- Pearson r value
- Spearman ρ value
- p-value

---

## 4.2 Hypothesis Testing

Define and test:

### H1: Update frequency affects rating
- Null hypothesis: no relationship
- Method: correlation significance test

### H2: Support responsiveness affects plugin quality
- Test: health_score vs resolution_rate
- Expected: positive correlation

### H3: Ratings are weak predictors of plugin health
- Test: rating vs health_score

Include:
- p-values
- statistical significance (α = 0.05)

---

## 4.3 Regression Analysis

Build predictive model:

health_score =
  β1 * rating +
  β2 * resolution_rate +
  β3 * recency_score +
  β4 * log(active_installs)

Report:

- coefficients (β values)
- p-values
- R² score

---

## 4.4 Optional Machine Learning

Use models like:

- Linear Regression
- Random Forest Regressor
- XGBoost (optional)

Extract:

- feature importance ranking
- prediction accuracy

---

## 5. Visualization Requirements

Generate:

### Correlation Heatmap
- all feature relationships

### Scatter Plots
- resolution_rate vs health_score
- days_since_update vs rating
- installs vs health_score

### Distribution Plots
- rating distribution (expect ceiling effect)
- health_score distribution

---

## 6. Expected Research Findings

Target validated conclusions:

1. Update frequency has weak correlation with rating
2. Support resolution rate strongly correlates with plugin health
3. User ratings show ceiling effect and low variance
4. Plugin ecosystem follows long-tail distribution

---

## 7. Output Format

### Summary Table

Feature | Correlation | p-value | Significance

---

### Final Insights Section

Include:

- 3 statistically supported findings
- 2 unexpected findings
- 1 limitation

---

## 8. Minimum Requirements for Research-Grade Output

To be considered valid empirical research:

- Dataset size: ~5000 plugins
- Correlation analysis (Pearson + Spearman)
- Regression model with coefficients
- Statistical significance (p-values)
- Clear hypothesis testing framework

---

## 9. Final Objective

Convert plugin dataset into:

Empirical evidence of software ecosystem behavior in WordPress

NOT just a dashboard or analytics tool.