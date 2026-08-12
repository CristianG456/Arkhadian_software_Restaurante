import { ErrorBoundary } from './app/ErrorBoundary'
import { ToastProvider } from './components/ui/ToastProvider'
import { SystemStatusProvider } from './app/SystemStatusContext'
import { AppShell } from './app/AppShell'

function App() {
  return (
    <ErrorBoundary>
      <SystemStatusProvider>
        <ToastProvider>
          <AppShell />
        </ToastProvider>
      </SystemStatusProvider>
    </ErrorBoundary>
  )
}

export default App
