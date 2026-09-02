import { Ban, QrCode, RefreshCw } from 'lucide-react'
import QRCode from 'qrcode'
import { useEffect, useState } from 'react'
import type { Area, RestaurantTable } from '../../api/areas'
import { Alert } from '../../components/ui/Alert'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { ConfirmDialog } from '../../components/ui/ConfirmDialog'
import { Dialog } from '../../components/ui/Dialog'
import { LoadingState } from '../../components/ui/Spinner'
import { useToast } from '../../components/ui/ToastProvider'
import { useTableQr } from '../../hooks/useTableQr'

interface TableQrDialogProps {
  open: boolean
  area: Area
  table: RestaurantTable
  onClose: () => void
}

type Confirmation = 'regenerate' | 'revoke' | null

export function TableQrDialog({ open, area, table, onClose }: TableQrDialogProps) {
  const qr = useTableQr(table.id)
  const toast = useToast()
  const [imageUrl, setImageUrl] = useState('')
  const [confirmation, setConfirmation] = useState<Confirmation>(null)
  const active = qr.credential?.status === 'active'

  useEffect(() => {
    let cancelled = false
    if (!active || !qr.credential?.publicUrl) {
      const clearId = window.setTimeout(() => {
        if (!cancelled) setImageUrl('')
      }, 0)
      return () => {
        cancelled = true
        window.clearTimeout(clearId)
      }
    }

    void QRCode.toDataURL(qr.credential.publicUrl, {
      errorCorrectionLevel: 'M',
      margin: 2,
      width: 320,
      color: { dark: '#111827', light: '#ffffff' },
    }).then((url) => {
      if (!cancelled) setImageUrl(url)
    })

    return () => {
      cancelled = true
    }
  }, [active, qr.credential?.publicUrl])

  async function generate() {
    try {
      await qr.generate()
      toast.success('QR generado.')
    } catch {
      // The hook exposes the safe backend message in the dialog.
    }
  }

  async function regenerate() {
    try {
      await qr.regenerate()
      toast.success('QR regenerado. El código anterior dejó de funcionar.')
      setConfirmation(null)
    } catch {
      // The hook exposes the safe backend message in the dialog.
    }
  }

  async function revoke() {
    try {
      await qr.revoke()
      toast.success('QR revocado. El código dejó de funcionar.')
      setConfirmation(null)
    } catch {
      // The hook exposes the safe backend message in the dialog.
    }
  }

  return (
    <>
      <Dialog
        open={open}
        onClose={onClose}
        title={`QR de ${table.displayName}`}
        description={`${area.name} · Credencial física revocable`}
        dismissible={!qr.saving}
        footer={<Button variant='outline' onClick={onClose} disabled={qr.saving}>Cerrar</Button>}
      >
        {qr.loading ? (
          <LoadingState label='Consultando QR...' />
        ) : (
          <div className='table-qr'>
            {qr.error && <Alert variant='danger'>{qr.error}</Alert>}

            {!qr.credential || qr.credential.status === 'revoked' ? (
              <div className='table-qr__empty'>
                <QrCode size={48} aria-hidden='true' />
                <h3>No hay un QR activo</h3>
                <p>Genera una credencial opaca para identificar esta mesa.</p>
                <Button icon={<QrCode size={18} />} onClick={() => void generate()} loading={qr.saving}>
                  Generar QR
                </Button>
              </div>
            ) : (
              <>
                <div className='table-qr__identity'>
                  <div>
                    <strong>{area.name}</strong>
                    <span>{table.displayName}</span>
                  </div>
                  <Badge variant='success' icon={null}>Activo · v{qr.credential.version}</Badge>
                </div>

                {qr.credential.publicUrl ? (
                  <div className='table-qr__code'>
                    {imageUrl
                      ? <img src={imageUrl} alt={`QR de ${table.displayName} en ${area.name}`} />
                      : <LoadingState label='Generando imagen QR...' />}
                    <small>Referencia: {qr.credential.publicToken.slice(0, 8)}…</small>
                  </div>
                ) : (
                  <Alert variant='warning'>Configura la URL pública antes de imprimir este QR.</Alert>
                )}

                <div className='table-qr__actions'>
                  <Button
                    variant='outline'
                    icon={<RefreshCw size={18} />}
                    onClick={() => setConfirmation('regenerate')}
                    disabled={qr.saving}
                  >
                    Regenerar
                  </Button>
                  <Button
                    variant='outline'
                    icon={<Ban size={18} />}
                    onClick={() => setConfirmation('revoke')}
                    disabled={qr.saving}
                  >
                    Revocar
                  </Button>
                </div>
              </>
            )}
          </div>
        )}
      </Dialog>

      {confirmation === 'regenerate' && (
        <ConfirmDialog
          open
          title='Regenerar QR'
          description='El QR actual dejará de funcionar inmediatamente y tendrás que reemplazar el código físico.'
          confirmLabel='Regenerar QR'
          onClose={() => setConfirmation(null)}
          onConfirm={() => void regenerate()}
          loading={qr.saving}
        />
      )}
      {confirmation === 'revoke' && (
        <ConfirmDialog
          open
          title='Revocar QR'
          description='El QR actual dejará de identificar esta mesa inmediatamente.'
          confirmLabel='Revocar QR'
          variant='danger'
          onClose={() => setConfirmation(null)}
          onConfirm={() => void revoke()}
          loading={qr.saving}
        />
      )}
    </>
  )
}
