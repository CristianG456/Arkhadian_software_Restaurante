import { ErrorBoundary } from './components/ErrorBoundary'
import { SystemStatusScreen } from './screens/SystemStatusScreen'

function App() {
  return (
    <ErrorBoundary>
      <SystemStatusScreen />
    </ErrorBoundary>
  )
}

export default App
