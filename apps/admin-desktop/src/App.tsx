import { ErrorBoundary } from './app/ErrorBoundary'
import { ToastProvider } from './components/ui/ToastProvider'
import { SystemStatusProvider } from './app/SystemStatusContext'
import { AppShell } from './app/AppShell'

function App() {
  return (
    <ErrorBoundary>
      <ToastProvider>
        <SystemStatusProvider>
          <AppShell />
        </SystemStatusProvider>
      </ToastProvider>
    </ErrorBoundary>
  )
}

export default App
