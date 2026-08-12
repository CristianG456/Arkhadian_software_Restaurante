import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { readFileSync } from 'node:fs'
import { fileURLToPath, URL } from 'node:url'

// Admin Desktop's version has a single source of truth: package.json.
// tauri.conf.json points at the same file ("version": "../package.json"),
// so this constant keeps the UI in sync without a third hardcoded copy.
const packageJson = JSON.parse(
  readFileSync(fileURLToPath(new URL('./package.json', import.meta.url)), 'utf-8'),
) as { version: string }

// Tauri expects a fixed, predictable dev server port (see src-tauri/tauri.conf.json
// devUrl) and needs its own Rust build artifacts ignored by Vite's watcher.
// https://v2.tauri.app/start/frontend/vite/
export default defineConfig({
  plugins: [react()],
  clearScreen: false,
  define: {
    __APP_VERSION__: JSON.stringify(packageJson.version),
  },
  server: {
    port: 1420,
    strictPort: true,
    watch: {
      ignored: ['**/src-tauri/**'],
    },
  },
})
