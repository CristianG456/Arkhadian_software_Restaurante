import { useEffect, useRef, useState } from 'react'
import type { KeyboardEvent, ReactNode } from 'react'
import { MoreVertical } from 'lucide-react'
import { cx } from '../../utils/cx'
import { IconButton } from './IconButton'
import './ActionMenu.css'

export interface ActionMenuItem {
  label: string
  icon?: ReactNode
  onSelect: () => void
  danger?: boolean
  disabled?: boolean
}

export interface ActionMenuProps {
  items: ActionMenuItem[]
  /** Custom trigger element. Defaults to an icon button (kebab menu) -
   * always tap-to-open, never right-click. */
  trigger?: ReactNode
  triggerLabel?: string
  align?: 'start' | 'end'
}

export function ActionMenu({ items, trigger, triggerLabel = 'Más opciones', align = 'end' }: ActionMenuProps) {
  const [open, setOpen] = useState(false)
  const rootRef = useRef<HTMLDivElement>(null)
  const itemRefs = useRef<Array<HTMLButtonElement | null>>([])

  useEffect(() => {
    if (!open) return

    function handlePointerDown(event: MouseEvent) {
      if (!rootRef.current?.contains(event.target as Node)) setOpen(false)
    }

    document.addEventListener('mousedown', handlePointerDown)
    return () => document.removeEventListener('mousedown', handlePointerDown)
  }, [open])

  useEffect(() => {
    if (open) itemRefs.current.find((el) => el && !el.disabled)?.focus()
  }, [open])

  function handleMenuKeyDown(event: KeyboardEvent<HTMLDivElement>) {
    const enabled = itemRefs.current.filter((el): el is HTMLButtonElement => !!el && !el.disabled)
    const currentIndex = enabled.findIndex((el) => el === document.activeElement)

    if (event.key === 'Escape') {
      event.preventDefault()
      setOpen(false)
      rootRef.current?.querySelector<HTMLButtonElement>('.action-menu__trigger')?.focus()
    } else if (event.key === 'ArrowDown') {
      event.preventDefault()
      enabled[(currentIndex + 1) % enabled.length]?.focus()
    } else if (event.key === 'ArrowUp') {
      event.preventDefault()
      enabled[(currentIndex - 1 + enabled.length) % enabled.length]?.focus()
    }
  }

  return (
    <div className="action-menu" ref={rootRef}>
      <span className="action-menu__trigger" onClick={() => setOpen((value) => !value)}>
        {trigger ?? (
          <IconButton icon={<MoreVertical size={18} />} label={triggerLabel} variant="ghost" />
        )}
      </span>
      {open && (
        <div
          className={cx('action-menu__list', `action-menu__list--${align}`)}
          role="menu"
          onKeyDown={handleMenuKeyDown}
        >
          {items.map((item, index) => (
            <button
              key={item.label}
              ref={(el) => {
                itemRefs.current[index] = el
              }}
              type="button"
              role="menuitem"
              disabled={item.disabled}
              className={cx('action-menu__item', item.danger && 'action-menu__item--danger')}
              onClick={() => {
                setOpen(false)
                item.onSelect()
              }}
            >
              {item.icon && (
                <span className="action-menu__icon" aria-hidden="true">
                  {item.icon}
                </span>
              )}
              {item.label}
            </button>
          ))}
        </div>
      )}
    </div>
  )
}
