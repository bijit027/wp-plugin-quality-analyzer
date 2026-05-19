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
