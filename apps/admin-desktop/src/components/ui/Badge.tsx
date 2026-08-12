import type { ReactNode } from 'react'
import { AlertTriangle, CheckCircle2, CircleDashed, Info, Loader2, XCircle } from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { cx } from '../../utils/cx'
import './Badge.css'

/**
 * Generic status pill - knows nothing about tables, orders, or any other
 * domain. Text is always required alongside color; nothing here is ever
 * communicated by color alone.
 */
export type BadgeVariant = 'success' | 'danger' | 'warning' | 'info' | 'neutral' | 'checking'

const DEFAULT_ICON: Record<BadgeVariant, LucideIcon> = {
  success: CheckCircle2,
  danger: XCircle,
  warning: AlertTriangle,
  info: Info,
  neutral: CircleDashed,
  checking: Loader2,
}

export interface BadgeProps {
  variant: BadgeVariant
  children: ReactNode
  /** Overrides the variant's default icon. Pass `null` to hide the icon entirely. */
  icon?: ReactNode | null
  className?: string
}

export function Badge({ variant, children, icon, className }: BadgeProps) {
  const DefaultIcon = DEFAULT_ICON[variant]
  const resolvedIcon = icon === undefined ? <DefaultIcon size={14} aria-hidden="true" /> : icon

  return (
    <span className={cx('badge', `badge--${variant}`, className)}>
      {resolvedIcon && (
        <span
          className={cx('badge__icon', variant === 'checking' && 'badge__icon--spin')}
          aria-hidden="true"
        >
          {resolvedIcon}
        </span>
      )}
      <span>{children}</span>
    </span>
  )
}
