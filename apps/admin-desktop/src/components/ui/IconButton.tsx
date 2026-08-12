import { forwardRef } from 'react'
import type { ButtonHTMLAttributes, ReactNode } from 'react'
import { Loader2 } from 'lucide-react'
import { cx } from '../../utils/cx'
import { Tooltip } from './Tooltip'
import './IconButton.css'

export type IconButtonVariant = 'ghost' | 'outline' | 'secondary' | 'danger'
export type IconButtonSize = 'sm' | 'md' | 'lg'

export interface IconButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  icon: ReactNode
  /** Required - doubles as the accessible name and the default tooltip text. */
  label: string
  variant?: IconButtonVariant
  size?: IconButtonSize
  loading?: boolean
  /** Set false to suppress the tooltip while keeping the aria-label. */
  tooltip?: boolean
  tooltipSide?: 'top' | 'bottom'
}

export const IconButton = forwardRef<HTMLButtonElement, IconButtonProps>(
  (
    {
      icon,
      label,
      variant = 'ghost',
      size = 'md',
      loading = false,
      tooltip = true,
      tooltipSide = 'top',
      disabled,
      className,
      ...rest
    },
    ref,
  ) => {
    const button = (
      <button
        ref={ref}
        type={rest.type ?? 'button'}
        aria-label={label}
        className={cx('icon-btn', `icon-btn--${variant}`, `icon-btn--${size}`, className)}
        disabled={disabled || loading}
        aria-busy={loading || undefined}
        {...rest}
      >
        {loading ? (
          <Loader2 size={18} className="icon-btn__icon icon-btn__icon--spin" aria-hidden="true" />
        ) : (
          <span className="icon-btn__icon" aria-hidden="true">
            {icon}
          </span>
        )}
      </button>
    )

    if (!tooltip) return button

    return (
      <Tooltip label={label} side={tooltipSide}>
        {button}
      </Tooltip>
    )
  },
)

IconButton.displayName = 'IconButton'
