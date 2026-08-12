import { useSystemStatusContext } from '../../app/SystemStatusContext'
import type { ConnectivityState } from '../../hooks/useSystemStatus'
import { Badge } from '../ui/Badge'
import type { BadgeVariant } from '../ui/Badge'
import './Topbar.css'

const CONNECTIVITY_BADGE: Record<ConnectivityState, { variant: BadgeVariant; text: string }> = {
  CHECKING: { variant: 'checking', text: 'Comprobando sistema' },
  ONLINE: { variant: 'success', text: 'Sistema activo' },
  OFFLINE: { variant: 'danger', text: 'Sistema sin conexión' },
  ERROR: { variant: 'danger', text: 'Error del sistema' },
}

/**
 * Prepared for future content (restaurante activo, usuario, Local/Nube,
 * alertas, sesión - see docs) but only ever shows real or neutral data
 * today. The connectivity badge reuses the same polled state Diagnóstico
 * shows in full, so the two are never inconsistent with each other.
 *
 * Deliberately says "Sistema" here, not "Core" - that's implementation
 * detail for Diagnóstico, which does spell out "Restaurant Core".
 */
export function Topbar() {
  const { connectivity } = useSystemStatusContext()
  const badge = CONNECTIVITY_BADGE[connectivity]

  return (
    <header className="topbar">
      <span className="topbar__brand">Restaurant Admin</span>
      <div className="topbar__status">
        <Badge variant={badge.variant}>{badge.text}</Badge>
      </div>
    </header>
  )
}
