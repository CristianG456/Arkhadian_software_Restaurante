import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.tsx'

// Mirrors browser console output into Tauri's log file (see src-tauri/src/lib.rs)
// so communication/runtime errors are diagnosable after the window is closed.
// A no-op outside the Tauri shell (e.g. `vite preview` in a plain browser).
if ('__TAURI_INTERNALS__' in window) {
  void import('@tauri-apps/plugin-log').then(({ attachConsole }) => attachConsole())
}

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
