import { Loader2 } from 'lucide-react'
import { cx } from '../../utils/cx'
import './Spinner.css'

export interface SpinnerProps {
  size?: number
  /** Accessible name for screen readers - always visually hidden here. */
  label?: string
  className?: string
}

/** A bare spinner, accessible name only. For a full-area loading state
 * with visible text, use <LoadingState />. */
export function Spinner({ size = 20, label = 'Cargando', className }: SpinnerProps) {
  return (
    <span className={cx('spinner', className)} role="status">
      <Loader2 size={size} className="spinner__icon" aria-hidden="true" />
      <span className="spinner__sr-only">{label}</span>
    </span>
  )
}

export interface LoadingStateProps {
  label?: string
  className?: string
}

export function LoadingState({ label = 'Cargando...', className }: LoadingStateProps) {
  return (
    <div className={cx('loading-state', className)}>
      <Loader2 size={24} className="loading-state__icon" aria-hidden="true" />
      <span className="loading-state__label">{label}</span>
    </div>
  )
}
