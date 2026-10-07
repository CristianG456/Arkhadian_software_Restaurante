import { CheckCircle2, AlertTriangle } from 'lucide-react'
import { useSystemStatusContext } from '../app/SystemStatusContext'
import './HomeScreen.css'

function formatTime(date: Date | null): string {
  if (!date) return '—'
  return date.toLocaleTimeString('es-CO', { hour12: false })
}

/**
 * Deliberately minimal - a welcome + one real, current fact about the
 * system, nothing invented. No sales/orders/revenue/charts: those need
 * real modules to exist first (see docs/architecture.md Phase 2 scope).
 */
export function HomeScreen() {
  const { snapshot, lastCheckedAt } = useSystemStatusContext()
  const systemAvailable = snapshot.localOperationAvailable

  return (
    <div className="home">
      <h1 className="home__title">Restaurant Admin</h1>

      <div className="home__status">
        {systemAvailable ? (
          <CheckCircle2 size={22} className="home__status-icon home__status-icon--ok" aria-hidden="true" />
        ) : (
          <AlertTriangle size={22} className="home__status-icon home__status-icon--warn" aria-hidden="true" />
        )}
        <p className="home__status-text">
          {systemAvailable
            ? 'La operación local está disponible.'
            : 'El sistema no responde. Revisa Configuración → Sistema → Diagnóstico.'}
        </p>
      </div>

      <p className="home__timestamp">Última comprobación: {formatTime(lastCheckedAt)}</p>
    </div>
  )
}
