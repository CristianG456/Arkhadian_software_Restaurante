import type { ReactNode } from 'react'
import { AlertTriangle, CheckCircle2, Info, X, XCircle } from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { cx } from '../../utils/cx'
import './Alert.css'

export type AlertVariant = 'info' | 'success' | 'warning' | 'danger'

const VARIANT_ICON: Record<AlertVariant, LucideIcon> = {
  info: Info,
  success: CheckCircle2,
  warning: AlertTriangle,
  danger: XCircle,
}

export interface AlertProps {
  variant: AlertVariant
  title?: string
  children: ReactNode
  onDismiss?: () => void
  className?: string
}

/** Inline, persistent alert - stays on screen until the user dismisses it
 * or the condition it describes changes. Unlike Toast, it doesn't
 * auto-expire. */
export function Alert({ variant, title, children, onDismiss, className }: AlertProps) {
  const Icon = VARIANT_ICON[variant]

  return (
    <div className={cx('alert', `alert--${variant}`, className)} role={variant === 'danger' ? 'alert' : 'status'}>
      <Icon size={18} className="alert__icon" aria-hidden="true" />
      <div className="alert__content">
        {title && <p className="alert__title">{title}</p>}
        <div className="alert__body">{children}</div>
      </div>
      {onDismiss && (
        <button type="button" className="alert__close" aria-label="Descartar" onClick={onDismiss}>
          <X size={16} aria-hidden="true" />
        </button>
      )}
    </div>
  )
}
