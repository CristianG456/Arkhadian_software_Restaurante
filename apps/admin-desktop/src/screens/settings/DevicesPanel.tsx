import { useCallback, useEffect, useState } from 'react'
import QRCode from 'qrcode'
import { Link, RefreshCw, Smartphone } from 'lucide-react'
import * as api from '../../api/mobileDevices'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Dialog } from '../../components/ui/Dialog'
import { useToast } from '../../components/ui/ToastProvider'
import { operationError } from '../../hooks/useOrdersOperation'

export function DevicesPanel() {
  const [devices, setDevices] = useState<api.MobileDevice[]>([]); const [installation, setInstallation] = useState<{ restaurantCode: string; restaurantName: string } | null>(null); const [pairing, setPairing] = useState<api.DevicePairing | null>(null); const [qr, setQr] = useState(''); const [loading, setLoading] = useState(true); const toast = useToast()
  const load = useCallback(async () => { try { const [d, i] = await Promise.all([api.fetchDevices(), api.fetchInstallation()]); setDevices(d.data.devices); setInstallation(i.data.installation) } catch (error) { toast.error(operationError(error)) } finally { setLoading(false) } }, [toast])
  useEffect(() => { const id = window.setTimeout(() => void load(), 0); return () => clearTimeout(id) }, [load])
  async function create() { try { const response = await api.createPairing(); setPairing(response.data.pairing); setQr(await QRCode.toDataURL(response.data.pairing.qrPayload, { width: 320, margin: 1 })) } catch (error) { toast.error(operationError(error)) } }
  async function revoke(id: string) { try { await api.revokeDevice(id); toast.success('Dispositivo revocado.'); await load() } catch (error) { toast.error(operationError(error)) } }
  return <section className='devices-panel'><header><div><h2>Dispositivos del mesero</h2><p>Vincula teléfonos mediante un código temporal. El código del restaurante identifica, pero no autoriza por sí solo.</p></div><span><Button variant='outline' icon={<RefreshCw size={16} />} onClick={() => void load()}>Actualizar</Button><Button icon={<Link size={16} />} onClick={() => void create()}>Vincular dispositivo</Button></span></header>{installation && <div className='restaurant-code'><small>Restaurante</small><strong>{installation.restaurantName} · {installation.restaurantCode}</strong></div>}{loading ? <p>Cargando...</p> : devices.length === 0 ? <p>No hay dispositivos vinculados.</p> : <div className='device-list'>{devices.map((device) => <article key={device.id}><Smartphone size={24} /><span><strong>{device.name}</strong><small>{device.platform} · Última conexión: {device.lastSeenAt ? new Date(device.lastSeenAt).toLocaleString('es-CO') : 'Nunca'}</small></span><Badge variant={device.status === 'ACTIVE' ? 'success' : 'neutral'}>{device.status === 'ACTIVE' ? 'Activo' : 'Revocado'}</Badge>{device.status === 'ACTIVE' && <Button size='sm' variant='outline' onClick={() => void revoke(device.id)}>Revocar</Button>}</article>)}</div>}<Dialog open={Boolean(pairing)} onClose={() => setPairing(null)} title='Vincular dispositivo' description='Este código vence y solo puede utilizarse una vez.' footer={<Button onClick={() => setPairing(null)}>Cerrar</Button>}>{pairing && <div className='pairing-card'>{qr && <img src={qr} alt='QR temporal de vinculación' />}<span><small>Código restaurante</small><strong>{pairing.restaurantCode}</strong></span><span><small>Código temporal</small><b>{pairing.pairingCode}</b></span><small>Vence: {new Date(pairing.expiresAt).toLocaleTimeString('es-CO')}</small></div>}</Dialog></section>
}
