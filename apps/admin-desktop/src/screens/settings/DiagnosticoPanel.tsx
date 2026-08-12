import { useState } from 'react'
import { Copy, RefreshCw } from 'lucide-react'
import { useSystemStatusContext } from '../../app/SystemStatusContext'
import { Panel } from '../../components/ui/Panel'
import { Badge } from '../../components/ui/Badge'
import type { BadgeVariant } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { IconButton } from '../../components/ui/IconButton'
import { Alert } from '../../components/ui/Alert'
import { env } from '../../config/env'
import './DiagnosticoPanel.css'

function formatTime(date: Date | null): string {
  if (!date) return '—'
  return date.toLocaleTimeString('es-CO', { hour12: false })
}

/**
 * The Phase 1 status screen, migrated as-is in substance (same real
 * data, same connectivity-state logic) onto the Phase 2 component
 * system. See docs/architecture.md "Connectivity states" for why Core
 * connectivity and the downstream fields (database/localServer/internet)
 * are computed separately.
 */
export function DiagnosticoPanel() {
  const { connectivity, data, lastCheckedAt, lastError, refresh } = useSystemStatusContext()
  const [copied, setCopied] = useState(false)

  const knowsDownstream = connectivity === 'ONLINE' && data !== null

  let coreVariant: BadgeVariant
  let coreText: string
  if (connectivity === 'CHECKING') {
    coreVariant = 'checking'
    coreText = 'Comprobando...'
  } else if (connectivity === 'OFFLINE') {
    coreVariant = 'danger'
    coreText = 'Sin conexión'
  } else if (connectivity === 'ERROR') {
    coreVariant = 'danger'
    coreText = 'Error'
  } else if (data?.core.status === 'degraded') {
    coreVariant = 'warning'
    coreText = 'Mantenimiento'
  } else {
    coreVariant = 'success'
    coreText = 'Activo'
  }

  function downstreamRow(
    online: boolean,
    onlineText: string,
    offlineText: string,
    offlineVariant: BadgeVariant = 'danger',
  ): { variant: BadgeVariant; text: string } {
    if (!knowsDownstream) {
      return connectivity === 'CHECKING'
        ? { variant: 'checking', text: 'Comprobando...' }
        : { variant: 'neutral', text: 'Desconocido' }
    }
    return online ? { variant: 'success', text: onlineText } : { variant: offlineVariant, text: offlineText }
  }

  const database = downstreamRow(data?.database.status === 'connected', 'Conectada', 'Sin conexión')
  const localServer = downstreamRow(data?.localServer.status === 'online', 'Activo', 'Sin conexión')
  // Internet being unreachable is expected and non-critical (Local-First),
  // so it renders as a warning, not the same danger as a real outage.
  const internet = downstreamRow(data?.internet.status === 'online', 'Disponible', 'No disponible', 'warning')

  async function copyInstallationId() {
    if (!data?.installationId) return
    try {
      await navigator.clipboard.writeText(data.installationId)
      setCopied(true)
      window.setTimeout(() => setCopied(false), 1500)
    } catch {
      // Clipboard access can be denied by the OS - not worth surfacing as
      // an error, the id is still visible to copy manually.
    }
  }

  return (
    <Panel
      title="Diagnóstico"
      description="Estado real de Restaurant Core, la base de datos, el servidor local e Internet."
      actions={
        <Button variant="secondary" icon={<RefreshCw size={16} />} onClick={refresh}>
          Actualizar estado
        </Button>
      }
    >
      <div className="diagnostico__rows">
        <div className="diagnostico__row">
          <span className="diagnostico__row-label">Restaurant Core</span>
          <Badge variant={coreVariant}>{coreText}</Badge>
        </div>
        <div className="diagnostico__row">
          <span className="diagnostico__row-label">Base de datos</span>
          <Badge variant={database.variant}>{database.text}</Badge>
        </div>
        <div className="diagnostico__row">
          <span className="diagnostico__row-label">Servidor local</span>
          <Badge variant={localServer.variant}>{localServer.text}</Badge>
        </div>
        <div className="diagnostico__row">
          <span className="diagnostico__row-label">Internet</span>
          <Badge variant={internet.variant}>{internet.text}</Badge>
        </div>
      </div>

      {connectivity !== 'ONLINE' && lastError && (
        <Alert variant={connectivity === 'ERROR' ? 'danger' : 'warning'} className="diagnostico__alert">
          {lastError} Reintentando automáticamente.
        </Alert>
      )}

      <div className="diagnostico__meta">
        <div className="diagnostico__meta-item">
          <span className="diagnostico__meta-label">Versión de administración</span>
          <span className="diagnostico__meta-value">{env.appVersion}</span>
        </div>
        <div className="diagnostico__meta-item">
          <span className="diagnostico__meta-label">Versión de Restaurant Core</span>
          <span className="diagnostico__meta-value">{data?.core.version ?? '—'}</span>
        </div>
        <div className="diagnostico__meta-item">
          <span className="diagnostico__meta-label">Última comprobación</span>
          <span className="diagnostico__meta-value">{formatTime(lastCheckedAt)}</span>
        </div>
        <div className="diagnostico__meta-item">
          <span className="diagnostico__meta-label">ID de instalación</span>
          <span className="diagnostico__meta-value diagnostico__meta-value--mono">
            {data?.installationId ?? '—'}
            {data?.installationId && (
              <IconButton
                icon={<Copy size={14} />}
                label={copied ? 'ID de instalación copiado.' : 'Copiar ID de instalación'}
                size="sm"
                variant="ghost"
                onClick={copyInstallationId}
              />
            )}
          </span>
        </div>
      </div>
    </Panel>
  )
}
