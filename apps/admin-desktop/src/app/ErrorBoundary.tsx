import { Component } from 'react'
import type { ErrorInfo, ReactNode } from 'react'
import './ErrorBoundary.css'

interface ErrorBoundaryProps {
  children: ReactNode
}

interface ErrorBoundaryState {
  hasError: boolean
}

/**
 * Last resort for unexpected render-time failures. Network/API failures
 * never reach this - those are handled by useSystemStatus - this only
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
        <div className="crash-screen">
          <h1 className="crash-screen__title">Ocurrió un error inesperado.</h1>
          <p className="crash-screen__body">
            Reinicia Restaurant Admin. El detalle técnico quedó en el registro.
          </p>
        </div>
      )
    }

    return this.props.children
  }
}
