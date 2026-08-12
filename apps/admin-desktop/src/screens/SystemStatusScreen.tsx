import { RefreshCw } from 'lucide-react'
import { StatusIndicator } from '../components/StatusIndicator'
import type { IndicatorVariant } from '../components/StatusIndicator'
import { useSystemStatus } from '../hooks/useSystemStatus'
import { env } from '../config/env'
import './SystemStatusScreen.css'

function formatTime(date: Date | null): string {
  if (!date) return '—'
  return date.toLocaleTimeString('es-CO', { hour12: false })
}

export function SystemStatusScreen() {
  const { connectivity, data, lastCheckedAt, lastError, refresh } = useSystemStatus()

  // Downstream signals (database, local server, internet) only mean
  // anything once Admin has actually reached Core — otherwise they render
  // as "unknown", never as a false "disponible"/"sin conexión" guess.
  const knowsDownstream = connectivity === 'ONLINE' && data !== null

  const coreVariant: IndicatorVariant =
    connectivity === 'CHECKING'
      ? 'checking'
      : connectivity === 'OFFLINE'
        ? 'negative'
        : connectivity === 'ERROR'
          ? 'error'
          : data?.core.status === 'degraded'
            ? 'warning'
            : 'positive'

  const coreText =
    connectivity === 'CHECKING'
      ? 'Comprobando...'
      : connectivity === 'OFFLINE'
        ? 'Sin conexión'
        : connectivity === 'ERROR'
          ? 'Error'
          : data?.core.status === 'degraded'
            ? 'Mantenimiento'
            : 'Activo'

  const databaseVariant: IndicatorVariant = !knowsDownstream
    ? connectivity === 'CHECKING'
      ? 'checking'
      : 'unknown'
    : data.database.status === 'connected'
      ? 'positive'
      : 'negative'

  const databaseText = !knowsDownstream
    ? connectivity === 'CHECKING'
      ? 'Comprobando...'
      : 'Desconocido'
    : data.database.status === 'connected'
      ? 'Conectada'
      : 'Sin conexión'

  const localServerVariant: IndicatorVariant = !knowsDownstream
    ? connectivity === 'CHECKING'
      ? 'checking'
      : 'unknown'
    : data.localServer.status === 'online'
      ? 'positive'
      : 'negative'

  const localServerText = !knowsDownstream
    ? connectivity === 'CHECKING'
      ? 'Comprobando...'
      : 'Desconocido'
    : data.localServer.status === 'online'
      ? 'Activo'
      : 'Sin conexión'

  const internetVariant: IndicatorVariant = !knowsDownstream
    ? connectivity === 'CHECKING'
      ? 'checking'
      : 'unknown'
    : data.internet.status === 'online'
      ? 'positive'
      : 'warning'

  const internetText = !knowsDownstream
    ? connectivity === 'CHECKING'
      ? 'Comprobando...'
      : 'Desconocido'
    : data.internet.status === 'online'
      ? 'Disponible'
      : 'No disponible'

  return (
    <div className="screen">
      <header className="screen__header">
        <h1 className="screen__title">Restaurant Admin</h1>
        <span className="screen__version">v{env.appVersion}</span>
      </header>

      <main className="screen__body">
        <section className="card">
          <h2 className="card__title">Estado del sistema</h2>

          <div className="status-list">
            <StatusIndicator label="Restaurant Core" text={coreText} variant={coreVariant} />
            <StatusIndicator label="Base de datos" text={databaseText} variant={databaseVariant} />
            <StatusIndicator label="Servidor local" text={localServerText} variant={localServerVariant} />
            <StatusIndicator label="Internet" text={internetText} variant={internetVariant} />
          </div>

          {connectivity !== 'ONLINE' && lastError && (
            <p className="card__notice" role="status">
              {lastError} Reintentando automáticamente.
            </p>
          )}

          <div className="card__footer">
            <span className="card__timestamp">Última comprobación: {formatTime(lastCheckedAt)}</span>
            <button type="button" className="button" onClick={refresh}>
              <RefreshCw size={16} aria-hidden="true" />
              Actualizar estado
            </button>
          </div>
        </section>

        <p className="screen__footnote">
          Core {data?.core.version ?? '—'} · Instalación {data?.installationId ?? '—'}
        </p>
      </main>
    </div>
  )
}
