import { Suspense, lazy, useState } from 'react'
import { LayoutTemplate } from 'lucide-react'
import { Sidebar } from '../components/layout/Sidebar'
import { Topbar } from '../components/layout/Topbar'
import type { ScreenKey } from './navigation'
import { HomeScreen } from '../screens/HomeScreen'
import { AreasTablesScreen } from '../screens/areas-tables/AreasTablesScreen'
import { SettingsScreen } from '../screens/settings/SettingsScreen'
import { ProductsScreen } from '../screens/catalog/ProductsScreen'
import { OrdersScreen } from '../screens/orders/OrdersScreen'
import { ProductionScreen } from '../screens/production/ProductionScreen'
import { LoadingState } from '../components/ui/Spinner'
import './AppShell.css'

const COLLAPSE_STORAGE_KEY = 'restaurant-admin.sidebar-collapsed'

type AppScreenKey = ScreenKey | 'dev-showcase'

// Code-split: nothing from the showcase (or its module graph) is fetched
// unless a developer actually opens it, and Vite excludes an unused
// dynamic-import target from a production build's initial bundle either
// way - see screens/dev/README.md.
const ComponentShowcaseScreen = lazy(() =>
  import('../screens/dev/ComponentShowcaseScreen').then((module) => ({
    default: module.ComponentShowcaseScreen,
  })),
)

function readStoredCollapse(): boolean {
  try {
    return localStorage.getItem(COLLAPSE_STORAGE_KEY) === '1'
  } catch {
    return false
  }
}

export function AppShell() {
  const [activeScreen, setActiveScreen] = useState<AppScreenKey>('home')
  const [collapsed, setCollapsed] = useState(readStoredCollapse)

  function toggleCollapsed() {
    setCollapsed((current) => {
      const next = !current
      try {
        localStorage.setItem(COLLAPSE_STORAGE_KEY, next ? '1' : '0')
      } catch {
        // Non-fatal - the app still works without persisting the preference.
      }
      return next
    })
  }

  return (
    <div className="app-shell">
      <Topbar />
      <div className="app-shell__body">
        <Sidebar
          activeKey={activeScreen === 'dev-showcase' ? 'home' : activeScreen}
          onSelect={setActiveScreen}
          collapsed={collapsed}
          onToggleCollapse={toggleCollapsed}
        />
        <main className="app-shell__content">
          {activeScreen === 'dev-showcase' && (
            <Suspense fallback={<LoadingState label="Cargando componentes..." />}>
              <ComponentShowcaseScreen />
            </Suspense>
          )}
          {activeScreen === 'home' && <HomeScreen />}
          {activeScreen === 'areas-mesas' && <AreasTablesScreen />}
          {activeScreen === 'pedidos' && <OrdersScreen />}
          {activeScreen === 'produccion' && <ProductionScreen />}
          {activeScreen === 'productos' && <ProductsScreen />}
          {activeScreen === 'settings' && <SettingsScreen />}
        </main>
      </div>

      {import.meta.env.DEV && (
        <button
          type="button"
          className="app-shell__dev-trigger"
          onClick={() =>
            setActiveScreen((current) => (current === 'dev-showcase' ? 'home' : 'dev-showcase'))
          }
        >
          <LayoutTemplate size={16} aria-hidden="true" />
          Pruebas de interfaz
        </button>
      )}
    </div>
  )
}
