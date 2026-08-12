import {
  BarChart3,
  ChefHat,
  ClipboardList,
  Home,
  LayoutGrid,
  Package,
  Settings,
  UtensilsCrossed,
  Wallet,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'

export type ScreenKey =
  | 'home'
  | 'areas-mesas'
  | 'pedidos'
  | 'productos'
  | 'produccion'
  | 'caja'
  | 'inventario'
  | 'reportes'
  | 'settings'

export interface NavItem {
  key: ScreenKey
  label: string
  icon: LucideIcon
  /** Future modules stay visible but disabled, not hidden - so the shape
   * of the product is legible before every module is built. */
  enabled: boolean
}

export const NAV_ITEMS: NavItem[] = [
  { key: 'home', label: 'Inicio', icon: Home, enabled: true },
  { key: 'areas-mesas', label: 'Áreas y mesas', icon: LayoutGrid, enabled: false },
  { key: 'pedidos', label: 'Pedidos', icon: ClipboardList, enabled: false },
  { key: 'productos', label: 'Productos', icon: UtensilsCrossed, enabled: false },
  { key: 'produccion', label: 'Producción', icon: ChefHat, enabled: false },
  { key: 'caja', label: 'Caja', icon: Wallet, enabled: false },
  { key: 'inventario', label: 'Inventario', icon: Package, enabled: false },
  { key: 'reportes', label: 'Reportes', icon: BarChart3, enabled: false },
  { key: 'settings', label: 'Configuración', icon: Settings, enabled: true },
]
