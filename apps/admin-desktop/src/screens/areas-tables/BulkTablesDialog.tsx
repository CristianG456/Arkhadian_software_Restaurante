import { useMemo, useState } from 'react'
import type { RestaurantTable } from '../../api/areas'
import { Alert } from '../../components/ui/Alert'
import { Button } from '../../components/ui/Button'
import { Dialog } from '../../components/ui/Dialog'
import { Input } from '../../components/ui/Input'
import { bulkPreview } from './areasTables.logic'

interface BulkTablesDialogProps {
  open: boolean
  tables: RestaurantTable[]
  saving: boolean
  onClose: () => void
  onSubmit: (from: number, to: number) => Promise<void>
}

export function BulkTablesDialog({ open, tables, saving, onClose, onSubmit }: BulkTablesDialogProps) {
  const [from, setFrom] = useState('1')
  const [to, setTo] = useState('10')
  const preview = useMemo(() => bulkPreview(Number(from), Number(to), tables), [from, to, tables])

  return (
    <Dialog
      open={open}
      onClose={onClose}
      title='Crear varias mesas'
      description='Crea hasta 100 mesas numéricas en una sola operación.'
      dismissible={!saving}
      footer={
        <>
          <Button variant='outline' onClick={onClose} disabled={saving}>Cancelar</Button>
          <Button
            onClick={() => void onSubmit(Number(from), Number(to))}
            loading={saving}
            disabled={!preview.valid || preview.newLabels.length === 0}
          >
            Crear {preview.newLabels.length} mesas
          </Button>
        </>
      }
    >
      <div className='bulk-tables'>
        <div className='bulk-tables__range'>
          <Input label='Desde' type='number' inputMode='numeric' min={1} max={9999} value={from} onChange={(event) => setFrom(event.target.value)} />
          <Input label='Hasta' type='number' inputMode='numeric' min={1} max={9999} value={to} onChange={(event) => setTo(event.target.value)} />
        </div>
        {!preview.valid ? (
          <Alert variant='warning'>Usa un rango válido de máximo 100 mesas.</Alert>
        ) : (
          <div className='bulk-tables__preview' aria-live='polite'>
            <p><strong>{preview.newLabels.length} nuevas</strong> y {preview.existingLabels.length} ya existentes.</p>
            {preview.existingLabels.length > 0 && (
              <Alert variant='info'>No se modificarán: {preview.existingLabels.map((label) => `Mesa ${label}`).join(', ')}.</Alert>
            )}
            <div className='bulk-tables__labels' aria-label='Mesas que se crearán'>
              {preview.newLabels.map((label) => <span key={label}>Mesa {label}</span>)}
            </div>
          </div>
        )}
      </div>
    </Dialog>
  )
}
