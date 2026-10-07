import { useSystemStatusContext } from '../../app/SystemStatusContext'
import { Badge } from '../ui/Badge'
import type { BadgeVariant } from '../ui/Badge'
import './Topbar.css'

export function Topbar() {
  const { snapshot } = useSystemStatusContext()
  const { core, database, internet } = snapshot.components
  let badge: { variant: BadgeVariant; text: string }

  if (core === 'CHECKING') badge = { variant: 'checking', text: 'Comprobando sistema' }
  else if (core === 'OFFLINE') badge = { variant: 'danger', text: 'Sistema sin conexión' }
  else if (core === 'ERROR') badge = { variant: 'danger', text: 'Error del sistema' }
  else if (database === 'OFFLINE') badge = { variant: 'danger', text: 'Base de datos no disponible' }
  else if (core === 'DEGRADED') badge = { variant: 'warning', text: 'Sistema degradado' }
  else if (internet === 'OFFLINE') badge = { variant: 'warning', text: 'Operación local activa' }
  else badge = { variant: 'success', text: 'Sistema activo' }

  return (
    <header className='topbar'>
      <span className='topbar__brand'>Restaurant Admin</span>
      <div className='topbar__status'>
        <Badge variant={badge.variant}>{badge.text}</Badge>
      </div>
    </header>
  )
}
