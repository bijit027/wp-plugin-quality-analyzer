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
