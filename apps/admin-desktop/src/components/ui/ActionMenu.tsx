import { useEffect, useLayoutEffect, useRef, useState } from 'react'
import type { KeyboardEvent, ReactNode } from 'react'
import { createPortal } from 'react-dom'
import { MoreVertical } from 'lucide-react'
import { cx } from '../../utils/cx'
import { IconButton } from './IconButton'
import { actionMenuPosition } from './actionMenu.logic'
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
  const menuRef = useRef<HTMLDivElement>(null)
  const itemRefs = useRef<Array<HTMLButtonElement | null>>([])
  const [position, setPosition] = useState({ top: 0, left: 0, ready: false })
  const [portalTarget, setPortalTarget] = useState<Element>(() => document.body)

  useEffect(() => {
    if (!open) return

    function handlePointerDown(event: MouseEvent) {
      const target = event.target as Node
      if (!rootRef.current?.contains(target) && !menuRef.current?.contains(target)) setOpen(false)
    }

    document.addEventListener('mousedown', handlePointerDown)
    return () => document.removeEventListener('mousedown', handlePointerDown)
  }, [open])

  useLayoutEffect(() => {
    if (!open || !rootRef.current || !menuRef.current) return
    const triggerRect = rootRef.current.getBoundingClientRect()
    const menuRect = menuRef.current.getBoundingClientRect()
    const next = actionMenuPosition(triggerRect, menuRect, { width: window.innerWidth, height: window.innerHeight }, align)
    setPosition({ ...next, ready: true })
  }, [align, open])

  useLayoutEffect(() => {
    if (open && rootRef.current) setPortalTarget(rootRef.current.closest('dialog') ?? document.body)
  }, [open])

  useEffect(() => {
    if (!open) return
    const close = () => setOpen(false)
    window.addEventListener('resize', close)
    window.addEventListener('scroll', close, true)
    return () => {
      window.removeEventListener('resize', close)
      window.removeEventListener('scroll', close, true)
    }
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
      rootRef.current?.querySelector<HTMLButtonElement>('button')?.focus()
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
      {open && createPortal(
        <div
          ref={menuRef}
          className={cx('action-menu__list', `action-menu__list--${align}`)}
          role="menu"
          onKeyDown={handleMenuKeyDown}
          style={{ top: position.top, left: position.left, visibility: position.ready ? 'visible' : 'hidden' }}
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
        </div>,
        portalTarget,
      )}
    </div>
  )
}
