import { Component, type ErrorInfo, type ReactNode } from 'react'

interface ErrorBoundaryProps {
  children: ReactNode
}

interface ErrorBoundaryState {
  hasError: boolean
}

/**
 * Last resort for unexpected render-time failures. Network/API failures
 * never reach this — those are handled by useSystemStatus — this only
 * catches genuine bugs, so the whole window doesn't go blank.
 */
export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  state: ErrorBoundaryState = { hasError: false }

  static getDerivedStateFromError(): ErrorBoundaryState {
    return { hasError: true }
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error('[error-boundary]', error, info.componentStack)
  }

  render(): ReactNode {
    if (this.state.hasError) {
      return (
        <div style={{ padding: '2rem', fontFamily: 'sans-serif' }}>
          <h1 style={{ fontSize: '1.1rem' }}>Ocurrió un error inesperado.</h1>
          <p style={{ color: '#6b7280' }}>Reinicia Restaurant Admin. El detalle técnico quedó en el registro.</p>
        </div>
      )
    }

    return this.props.children
  }
}
