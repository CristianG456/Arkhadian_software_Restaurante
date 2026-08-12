import { PanelLeftClose, PanelLeftOpen } from 'lucide-react'
import { cx } from '../../utils/cx'
import { NAV_ITEMS } from '../../app/navigation'
import type { ScreenKey } from '../../app/navigation'
import { Tooltip } from '../ui/Tooltip'
import './Sidebar.css'

export interface SidebarProps {
  activeKey: ScreenKey
  onSelect: (key: ScreenKey) => void
  collapsed: boolean
  onToggleCollapse: () => void
}

export function Sidebar({ activeKey, onSelect, collapsed, onToggleCollapse }: SidebarProps) {
  return (
    <nav className={cx('sidebar', collapsed && 'sidebar--collapsed')} aria-label="Navegación principal">
      <ul className="sidebar__list">
        {NAV_ITEMS.map((item) => {
          const Icon = item.icon
          const active = item.key === activeKey
          const button = (
            <button
              type="button"
              className={cx('sidebar__item', active && 'sidebar__item--active')}
              disabled={!item.enabled}
              aria-current={active ? 'page' : undefined}
              onClick={() => onSelect(item.key)}
            >
              <Icon size={20} className="sidebar__icon" aria-hidden="true" />
              {!collapsed && <span className="sidebar__label">{item.label}</span>}
            </button>
          )

          return (
            <li key={item.key}>
              {collapsed ? (
                <Tooltip label={item.label} side="bottom">
                  {button}
                </Tooltip>
              ) : (
                button
              )}
            </li>
          )
        })}
      </ul>

      <button
        type="button"
        className="sidebar__collapse-toggle"
        onClick={onToggleCollapse}
        aria-label={collapsed ? 'Expandir navegación' : 'Contraer navegación'}
      >
        {collapsed ? (
          <PanelLeftOpen size={18} aria-hidden="true" />
        ) : (
          <>
            <PanelLeftClose size={18} aria-hidden="true" />
            <span>Contraer</span>
          </>
        )}
      </button>
    </nav>
  )
}
