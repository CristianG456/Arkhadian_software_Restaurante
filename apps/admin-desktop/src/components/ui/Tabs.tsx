import { useRef } from 'react'
import type { KeyboardEvent, ReactNode } from 'react'
import { cx } from '../../utils/cx'
import './Tabs.css'

export interface TabItem {
  value: string
  label: string
  icon?: ReactNode
  disabled?: boolean
}

export interface TabsProps {
  items: TabItem[]
  value: string
  onChange: (value: string) => void
  className?: string
}

/**
 * Renders only the tab bar (role="tablist"). Callers render the panel
 * content themselves based on `value` - keeps this generic instead of
 * baking in an opinion about what a "panel" looks like.
 */
export function Tabs({ items, value, onChange, className }: TabsProps) {
  const listRef = useRef<HTMLDivElement>(null)

  const enabledItems = items.filter((item) => !item.disabled)

  function focusTab(targetValue: string) {
    const button = listRef.current?.querySelector<HTMLButtonElement>(
      `[data-tab-value="${targetValue}"]`,
    )
    button?.focus()
  }

  function handleKeyDown(event: KeyboardEvent<HTMLButtonElement>, currentValue: string) {
    const currentIndex = enabledItems.findIndex((item) => item.value === currentValue)
    if (currentIndex === -1) return

    let nextIndex: number | null = null
    if (event.key === 'ArrowRight') nextIndex = (currentIndex + 1) % enabledItems.length
    else if (event.key === 'ArrowLeft')
      nextIndex = (currentIndex - 1 + enabledItems.length) % enabledItems.length
    else if (event.key === 'Home') nextIndex = 0
    else if (event.key === 'End') nextIndex = enabledItems.length - 1

    if (nextIndex === null) return
    event.preventDefault()
    const next = enabledItems[nextIndex]
    if (!next) return
    onChange(next.value)
    focusTab(next.value)
  }

  return (
    <div ref={listRef} role="tablist" className={cx('tabs', className)}>
      {items.map((item) => {
        const selected = item.value === value
        return (
          <button
            key={item.value}
            type="button"
            role="tab"
            data-tab-value={item.value}
            aria-selected={selected}
            disabled={item.disabled}
            tabIndex={selected ? 0 : -1}
            className={cx('tabs__tab', selected && 'tabs__tab--active')}
            onClick={() => onChange(item.value)}
            onKeyDown={(event) => handleKeyDown(event, item.value)}
          >
            {item.icon && (
              <span className="tabs__icon" aria-hidden="true">
                {item.icon}
              </span>
            )}
            {item.label}
          </button>
        )
      })}
    </div>
  )
}
