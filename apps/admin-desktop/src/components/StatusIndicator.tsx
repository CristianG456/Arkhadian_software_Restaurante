import { AlertTriangle, CheckCircle2, CircleAlert, CircleDashed, Loader2, XCircle } from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import './StatusIndicator.css'

/**
 * Visual variant only — the accompanying `text` always carries the actual
 * meaning so state is never communicated by color alone.
 */
export type IndicatorVariant = 'positive' | 'negative' | 'checking' | 'warning' | 'error' | 'unknown'

const VARIANT_ICON: Record<IndicatorVariant, LucideIcon> = {
  positive: CheckCircle2,
  negative: XCircle,
  checking: Loader2,
  warning: AlertTriangle,
  error: CircleAlert,
  unknown: CircleDashed,
}

interface StatusIndicatorProps {
  label: string
  text: string
  variant: IndicatorVariant
}

export function StatusIndicator({ label, text, variant }: StatusIndicatorProps) {
  const Icon = VARIANT_ICON[variant]

  return (
    <div className="status-row">
      <span className="status-row__label">{label}</span>
      <span className={`status-badge status-badge--${variant}`}>
        <Icon
          size={16}
          className={variant === 'checking' ? 'status-badge__icon status-badge__icon--spin' : 'status-badge__icon'}
          aria-hidden="true"
        />
        <span>{text}</span>
      </span>
    </div>
  )
}
