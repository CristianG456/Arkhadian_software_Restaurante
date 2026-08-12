import type { ReactNode } from 'react'
import { cx } from '../../utils/cx'
import './Tooltip.css'

export interface TooltipProps {
  label: string
  children: ReactNode
  side?: 'top' | 'bottom'
  className?: string
}

/**
 * Pure-CSS hover/focus tooltip - supplementary only. The accessible name
 * always comes from the wrapped control's own aria-label/label, never from
 * this alone, so it adds nothing for touch (where hover doesn't exist) and
 * costs nothing either.
 */
export function Tooltip({ label, children, side = 'top', className }: TooltipProps) {
  return (
    <span className={cx('tooltip', className)}>
      {children}
      <span className={cx('tooltip__bubble', `tooltip__bubble--${side}`)} role="tooltip">
        {label}
      </span>
    </span>
  )
}
