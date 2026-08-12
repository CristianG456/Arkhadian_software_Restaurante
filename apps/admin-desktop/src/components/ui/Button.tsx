import { forwardRef } from 'react'
import type { ButtonHTMLAttributes, ReactNode } from 'react'
import { Loader2 } from 'lucide-react'
import { cx } from '../../utils/cx'
import './Button.css'

export type ButtonVariant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger'
export type ButtonSize = 'sm' | 'md' | 'lg'

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant
  size?: ButtonSize
  /** Leading icon - hidden while loading (the spinner takes its place). */
  icon?: ReactNode
  loading?: boolean
  fullWidth?: boolean
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  (
    {
      variant = 'primary',
      size = 'md',
      icon,
      loading = false,
      fullWidth = false,
      disabled,
      className,
      children,
      ...rest
    },
    ref,
  ) => {
    return (
      <button
        ref={ref}
        type={rest.type ?? 'button'}
        className={cx(
          'btn',
          `btn--${variant}`,
          `btn--${size}`,
          fullWidth && 'btn--full',
          className,
        )}
        disabled={disabled || loading}
        aria-busy={loading || undefined}
        {...rest}
      >
        {loading ? (
          <Loader2 size={16} className="btn__icon btn__icon--spin" aria-hidden="true" />
        ) : (
          icon && (
            <span className="btn__icon" aria-hidden="true">
              {icon}
            </span>
          )
        )}
        {children && <span className="btn__label">{children}</span>}
      </button>
    )
  },
)

Button.displayName = 'Button'
