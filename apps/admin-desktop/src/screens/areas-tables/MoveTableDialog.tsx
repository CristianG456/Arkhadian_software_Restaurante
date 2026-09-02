import { useState } from 'react'
import type { Area, RestaurantTable } from '../../api/areas'
import { Alert } from '../../components/ui/Alert'
import { Button } from '../../components/ui/Button'
import { Dialog } from '../../components/ui/Dialog'
import { Select } from '../../components/ui/Select'

interface MoveTableDialogProps {
  open: boolean
  table: RestaurantTable
  areas: Area[]
  saving: boolean
  onClose: () => void
  onSubmit: (areaId: string) => Promise<void>
}

export function MoveTableDialog({
  open,
  table,
  areas,
  saving,
  onClose,
  onSubmit,
}: MoveTableDialogProps) {
  const targets = areas.filter((area) => area.id !== table.areaId)
  const [areaId, setAreaId] = useState(targets[0]?.id ?? '')

  return (
    <Dialog
      open={open}
      onClose={onClose}
      title={`Mover ${table.displayName}`}
      description='Cambia la ubicación física configurada de esta mesa. No traslada consumos ni pedidos.'
      size='sm'
      dismissible={!saving}
      footer={
        <>
          <Button variant='outline' onClick={onClose} disabled={saving}>Cancelar</Button>
          <Button
            onClick={() => void onSubmit(areaId)}
            loading={saving}
            disabled={areaId === ''}
          >
            Mover mesa
          </Button>
        </>
      }
    >
      {targets.length === 0 ? (
        <Alert variant='warning'>Crea otra área antes de mover esta mesa.</Alert>
      ) : (
        <Select
          label='Área de destino'
          value={areaId}
          onChange={(event) => setAreaId(event.target.value)}
          options={targets.map((area) => ({ value: area.id, label: area.name }))}
        />
      )}
    </Dialog>
  )
}
