import type { ReactNode } from 'react'
import { cx } from '../../utils/cx'
import './Panel.css'

export interface PanelProps {
  title?: string
  description?: string
  actions?: ReactNode
  children: ReactNode
  className?: string
  padded?: boolean
}

/** A plain bordered container - the one "card" primitive. Not meant to
 * wrap every piece of UI; most of the shell is not cards. */
export function Panel({ title, description, actions, children, className, padded = true }: PanelProps) {
  const hasHeader = title || description || actions

  return (
    <section className={cx('panel', className)}>
      {hasHeader && (
        <header className="panel__header">
          <div>
            {title && <h2 className="panel__title">{title}</h2>}
            {description && <p className="panel__description">{description}</p>}
          </div>
          {actions && <div className="panel__actions">{actions}</div>}
        </header>
      )}
      <div className={cx('panel__body', !padded && 'panel__body--flush')}>{children}</div>
    </section>
  )
}
