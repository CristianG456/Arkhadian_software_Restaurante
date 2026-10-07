import { AlertTriangle, RefreshCw, WifiOff } from 'lucide-react'
import { useSystemStatusContext } from '../../app/SystemStatusContext'
import { Button } from '../ui/Button'

export function ConnectivityBanner() {
  const { snapshot, refresh, refreshing } = useSystemStatusContext()
  const { core, database, internet } = snapshot.components

  let message: string | null = null
  let variant = 'warning'
  let Icon = WifiOff

  if (core === 'OFFLINE' || core === 'ERROR') {
    message = 'Restaurant Core sin conexión · Reconectando automáticamente.'
    variant = 'danger'
    Icon = AlertTriangle
  } else if (database === 'OFFLINE') {
    message = 'Base de datos local no disponible · Las acciones con datos están bloqueadas.'
    variant = 'danger'
    Icon = AlertTriangle
  } else if (internet === 'OFFLINE' && snapshot.localOperationAvailable) {
    message = 'Sin Internet · Operación local activa.'
  } else if (core === 'DEGRADED') {
    message = 'Restaurant Core está en modo degradado.'
  }

  if (!message) return null

  return (
    <div className={'connectivity-banner connectivity-banner--' + variant} role='status'>
      <Icon size={17} aria-hidden='true' />
      <span>{message}</span>
      {(variant === 'danger') && (
        <Button
          size='sm'
          variant='ghost'
          icon={<RefreshCw size={15} />}
          loading={refreshing}
          onClick={() => void refresh()}
        >
          Reintentar
        </Button>
      )}
    </div>
  )
}
