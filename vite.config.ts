import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'

// https://vite.dev/config/
export default defineConfig({
  base: '/',
  plugins: [
    vue(),
    vueJsx(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
  },
  server: {
    port: 5175,
    host: true,
    strictPort: false,
    allowedHosts: ['frontend.openpbbg.orb.local', 'frontend.orb.local', 'localhost', '.orb.local'],
    hmr: {
      host: 'frontend.orb.local',
      protocol: 'ws',
    },
  },
})
