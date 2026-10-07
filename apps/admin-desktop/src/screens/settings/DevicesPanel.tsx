import { useCallback, useEffect, useState } from 'react'
import QRCode from 'qrcode'
import { Link, RefreshCw, Smartphone } from 'lucide-react'
import * as api from '../../api/mobileDevices'
import { isInfrastructureError } from '../../api/ApiClientError'
import { useSystemStatusContext } from '../../app/SystemStatusContext'
import { Alert } from '../../components/ui/Alert'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Dialog } from '../../components/ui/Dialog'
import { EmptyState } from '../../components/ui/EmptyState'
import { useToast } from '../../components/ui/ToastProvider'
import { operationError } from '../../hooks/useOrdersOperation'
import { devicesViewState } from './devices.logic'

export function DevicesPanel() {
  const system = useSystemStatusContext()
  const toast = useToast()
  const [devices, setDevices] = useState<api.MobileDevice[]>([])
  const [installation, setInstallation] = useState<{ restaurantCode: string; restaurantName: string } | null>(null)
  const [pairing, setPairing] = useState<api.DevicePairing | null>(null)
  const [qr, setQr] = useState('')
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState<string | null>(null)
  const available = system.snapshot.localOperationAvailable

  const load = useCallback(async (signal?: AbortSignal) => {
    setLoading(true)
    try {
      const [deviceResponse, installationResponse] = await Promise.all([
        api.fetchDevices(signal),
        api.fetchInstallation(signal),
      ])
      setDevices(deviceResponse.data.devices)
      setInstallation(installationResponse.data.installation)
      setLoadError(null)
    } catch (error) {
      if (!signal?.aborted) setLoadError(operationError(error))
    } finally {
      if (!signal?.aborted) setLoading(false)
    }
  }, [])

  useEffect(() => {
    if (!available) {
      const unavailableId = window.setTimeout(() => setLoading(false), 0)
      return () => window.clearTimeout(unavailableId)
    }
    const controller = new AbortController()
    const timerId = window.setTimeout(() => void load(controller.signal), 0)
    return () => {
      window.clearTimeout(timerId)
      controller.abort()
    }
  }, [available, load])

  async function create() {
    if (!available) return
    try {
      const response = await api.createPairing()
      setPairing(response.data.pairing)
      setQr(await QRCode.toDataURL(response.data.pairing.qrPayload, { width: 320, margin: 1 }))
    } catch (error) {
      if (isInfrastructureError(error)) setLoadError(operationError(error))
      else toast.error(operationError(error))
    }
  }

  async function revoke(id: string) {
    if (!available) return
    try {
      await api.revokeDevice(id)
      toast.success('Dispositivo revocado.')
      await load()
    } catch (error) {
      if (isInfrastructureError(error)) setLoadError(operationError(error))
      else toast.error(operationError(error))
    }
  }

  const view = devicesViewState(system.snapshot, loading, loadError, devices.length)

  function renderContent() {
    if (view.mode === 'error') return <Alert variant='danger'>{view.message}</Alert>
    if (view.mode === 'checking') return <p>Cargando...</p>
    return <DeviceList devices={devices} available={available} onRevoke={revoke} />
  }

  return <section className='devices-panel'>
    <header>
      <div><h2>Dispositivos del mesero</h2><p>Vincula teléfonos mediante un código temporal.</p></div>
      <span>
        <Button variant='outline' icon={<RefreshCw size={16} />} loading={loading || system.refreshing}
          onClick={() => { void system.refresh(); if (available) void load() }}>Actualizar</Button>
        <Button icon={<Link size={16} />} disabled={!available}
          title={!available ? 'Restaurant Core y la base de datos deben estar conectados.' : undefined}
          onClick={() => void create()}>Vincular dispositivo</Button>
      </span>
    </header>
    {installation && <div className='restaurant-code'><small>Restaurante</small><strong>{installation.restaurantName} - {installation.restaurantCode}</strong></div>}
    {renderContent()}
    <PairingDialog pairing={pairing} qr={qr} onClose={() => setPairing(null)} />
  </section>
}

function DeviceList({ devices, available, onRevoke }: {
  devices: api.MobileDevice[]
  available: boolean
  onRevoke: (id: string) => Promise<void>
}) {
  if (devices.length === 0) {
    return <EmptyState icon={<Smartphone size={30} />} title='No hay dispositivos vinculados.'
      description='Vincula el primer teléfono para que un mesero pueda iniciar sesión.' />
  }
  return <div className='device-list'>{devices.map((device) => <article key={device.id}>
    <Smartphone size={24} />
    <span><strong>{device.name}</strong><small>{device.platform} - Última conexión: {device.lastSeenAt ? new Date(device.lastSeenAt).toLocaleString('es-CO') : 'Nunca'}</small></span>
    <Badge variant={device.status === 'ACTIVE' ? 'success' : 'neutral'}>{device.status === 'ACTIVE' ? 'Activo' : 'Revocado'}</Badge>
    {device.status === 'ACTIVE' && <Button size='sm' variant='outline' disabled={!available}
      onClick={() => void onRevoke(device.id)}>Revocar</Button>}
  </article>)}</div>
}

function PairingDialog({ pairing, qr, onClose }: {
  pairing: api.DevicePairing | null
  qr: string
  onClose: () => void
}) {
  return <Dialog open={Boolean(pairing)} onClose={onClose} title='Vincular dispositivo'
    description='Este código vence y solo puede utilizarse una vez.'
    footer={<Button onClick={onClose}>Cerrar</Button>}>
    {pairing && <div className='pairing-card'>
      {qr && <img src={qr} alt='QR temporal de vinculación' />}
      <span><small>Código restaurante</small><strong>{pairing.restaurantCode}</strong></span>
      <span><small>Código temporal</small><b>{pairing.pairingCode}</b></span>
      <small>Vence: {new Date(pairing.expiresAt).toLocaleTimeString('es-CO')}</small>
    </div>}
  </Dialog>
}
